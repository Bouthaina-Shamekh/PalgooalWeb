<?php

namespace App\Services\WhatsApp;

use App\Models\Invoice;
use App\Models\InvoiceWhatsAppDeliveryAttempt;
use App\Models\User;
use App\Services\Billing\InvoicePdfService;
use App\WhatsApp\DTOs\WhatsAppDocumentMessage;
use App\WhatsApp\DTOs\WhatsAppSendResult;
use App\WhatsApp\Exceptions\WhatsAppConfigurationException;
use App\WhatsApp\Exceptions\WhatsAppConfirmedSendFailureException;
use App\WhatsApp\Exceptions\WhatsAppIndeterminateSendException;
use App\WhatsApp\Support\WhatsAppPhoneNormalizer;
use App\WhatsApp\WhatsAppManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Claims and executes a single invoice-WhatsApp-delivery attempt.
 *
 * Durable state machine (InvoiceWhatsAppDeliveryAttempt::STATUS_*):
 *
 *   claim()   pending
 *   execute() pending -> processing -> sent | confirmed_failed | indeterminate
 *
 * Modeled directly on App\Services\Payments\PaymentSessionStarter: short,
 * separate DB::transaction() blocks around each state write, with the slow
 * and/or network-touching work (the provider call) always running BETWEEN
 * transactions, never inside one. See attemptSend()'s
 * DB::transactionLevel() guard, which mirrors PaymentSessionStarter::start()'s
 * identical guard before calling a gateway's createSession().
 *
 * FROZEN-DOCUMENT-ARTIFACT CONTRACT (Phase 4):
 *
 * Earlier in this service's life, document_hash was computed as a
 * fingerprint of invoice/invoice-item timestamps rather than a hash of the
 * actual rendered PDF -- a workaround for InvoicePdfService::render() not
 * being byte-deterministic across renders of unchanged content (mPDF embeds
 * a random per-render file identifier). That workaround was correctly
 * rejected: a timestamp fingerprint cannot prove which exact PDF bytes were
 * requested or sent, and the rendered PDF can depend on data outside
 * Invoice/InvoiceItem entirely (client data, company/general settings,
 * logo, other view dependencies) that no timestamp captures.
 *
 * The fix is to stop re-rendering at all after claim() and instead FREEZE
 * the exact generated artifact:
 *
 *   - claim() renders InvoicePdfService exactly once for a genuinely NEW
 *     attempt, writes those exact bytes to a private disk at a path
 *     derived only from the new attempt_uuid
 *     (InvoiceWhatsAppDeliveryAttempt::documentArtifactPath()), and records
 *     document_hash = sha256(those bytes) and document_byte_length =
 *     strlen(those bytes). document_hash is therefore, once again, exactly
 *     what its column name says: the real SHA-256 of the real frozen PDF.
 *   - execute() NEVER calls InvoicePdfService again. It loads the frozen
 *     bytes from disk, re-verifies (exists / sha256 / byte length) that the
 *     artifact on disk still matches what was recorded at claim time, and
 *     sends exactly those bytes. A duplicate claim() for an
 *     already-pending/processing attempt returns the existing attempt
 *     untouched and renders nothing.
 *
 * One deliberate behavior change this implies: an invoice edited AFTER
 * claim() no longer blocks or alters that attempt's send -- the whole point
 * of freezing is that execute() transmits the exact document that was
 * frozen at claim time, regardless of what happens to the invoice
 * afterward. (A caller who wants to send an UPDATED document simply calls
 * claim() again once the prior attempt reaches a terminal status.)
 *
 * No queue/job exists yet -- execute() is a plain synchronous method call.
 * A future job only needs to load the attempt and call execute(); all the
 * state-machine safety lives here.
 *
 * Known race (documented, not invented away by a schema change this phase):
 * claim() locks the INVOICE row (Invoice::query()->lockForUpdate()) before
 * checking for an existing in-flight attempt, which is strictly stronger
 * than locking the attempts table alone -- a ->lockForUpdate() query that
 * matches zero rows locks nothing, so two concurrent claim() calls racing
 * to be the FIRST attempt for a given (invoice, recipient) could otherwise
 * both observe "no existing attempt" and both insert one. Locking the
 * always-present invoice row first closes this for concurrent claim() calls
 * specifically (InnoDB locking reads see the latest committed data, not a
 * stale snapshot, once the lock is acquired -- the second transaction's
 * attempts-table lookup only proceeds after the first has committed).
 * MySQL still has no partial/filtered unique index to enforce "at most one
 * in-flight attempt per (invoice, recipient)" at the schema level -- this
 * mirrors the exact same accepted limitation invoices.payment_session_status
 * / payment_session_attempt_id claiming already lives with today. If a
 * future caller ever creates attempts through some OTHER path that does not
 * also lock the invoice row first, this protection would not apply to that
 * path; that is a hardening note for whoever builds the next layer
 * (controller/job), not a gap in this service itself.
 *
 * Deliberate exception to the class's own "slow work never inside a
 * transaction" rule: for a genuinely NEW attempt, PDF rendering and the
 * artifact file write DO happen while the invoice row lock is held (see
 * claim()). This is intentional, not an oversight: rendering is local,
 * CPU/disk-bound work with no external network dependency and no
 * unbounded-wait risk, unlike a provider HTTP call -- so holding the lock
 * for its (short, bounded) duration is an acceptable, simple way to
 * guarantee that at most one render/write ever happens per new attempt,
 * with zero risk of two concurrent claims both rendering and only one
 * "winning". The alternative (render outside the lock, then reconcile
 * under the lock) would avoid holding the lock slightly longer, at the cost
 * of a real chance of wasted/orphaned renders under concurrent claims for
 * the same (invoice, recipient) -- a worse trade for a rare, cheap
 * operation.
 */
class InvoiceWhatsAppDeliveryService
{
    public function __construct(
        protected WhatsAppManager $whatsapp,
        protected InvoicePdfService $pdfService,
    ) {}

    /**
     * Claim one delivery attempt for $invoice.
     *
     * If an attempt is already pending/processing for the same (invoice,
     * normalized recipient), that existing attempt is returned as-is: its
     * frozen PDF artifact and document identity (document_hash/
     * document_byte_length/document_filename) are never touched, and
     * InvoicePdfService is never invoked for this case.
     *
     * Only for a genuinely NEW attempt does this method render the PDF
     * (exactly once), write those exact bytes to a private disk at a path
     * derived from the new attempt_uuid, and record their real SHA-256 hash
     * and byte length. No provider/network call happens here -- only
     * WhatsAppManager::gatewayFor()'s local config resolution.
     *
     * Filesystem writes are not transactional with the database: the
     * artifact is written to disk BEFORE the attempt row is created, and if
     * that write fails, no row is ever created (so no pending row can point
     * at a missing artifact). If the row creation fails AFTER a successful
     * write (e.g. an unexpected DB error), the just-written artifact is
     * deleted before the exception is rethrown, so no orphan file survives
     * a failed claim().
     *
     * @throws \App\WhatsApp\Exceptions\InvalidWhatsAppPhoneException when the
     *         client has no phone, or it cannot be confidently normalized
     *         (see WhatsAppPhoneNormalizer's fail-closed policy). No attempt
     *         row is created and no PDF is rendered in this case.
     * @throws WhatsAppConfigurationException when no WhatsApp provider is
     *         configured. No attempt row is created and no PDF is rendered
     *         in this case.
     * @throws \RuntimeException when the frozen PDF artifact could not be
     *         written to the private disk. No attempt row is created.
     */
    public function claim(Invoice $invoice, ?User $requestedBy = null): InvoiceWhatsAppDeliveryAttempt
    {
        // Steps 1-3: resolve + fail-closed-normalize the recipient phone.
        // WhatsAppPhoneNormalizer::normalize(null) already throws a clear
        // InvalidWhatsAppPhoneException for a missing/empty/local/malformed
        // phone (and for no client at all, since $invoice->client?->phone is
        // then null too) -- no separate empty-check is needed here.
        $rawPhone = $invoice->client?->phone;
        $normalizedRecipient = WhatsAppPhoneNormalizer::normalize($rawPhone);

        // Step 4: resolve the provider. No network I/O happens here --
        // WhatsAppManager only reads config and instantiates the mapped
        // class. Freeze the CONFIG KEY (e.g. "mock"), not the gateway's own
        // ->name() (e.g. "mock_whatsapp") -- the two can differ, and only
        // the key is a valid WhatsAppManager::gatewayFor() argument later in
        // execute(). gatewayFor() here also validates the key resolves to a
        // real class before any attempt row is created.
        $providerKey = $this->whatsapp->defaultProviderKey();
        $this->whatsapp->gatewayFor($providerKey);
        $provider = $providerKey;

        $disk = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK);

        return DB::transaction(function () use ($invoice, $rawPhone, $normalizedRecipient, $provider, $requestedBy, $disk) {
            // Concurrency gate: lock the INVOICE row itself, not the
            // (possibly not-yet-existing) attempt row. This is the same
            // mechanism PaymentSessionStarter::claim() uses and for the same
            // reason: locking a row that may not exist yet (via
            // ->lockForUpdate() on a query that matches zero rows) locks
            // nothing at all, so two concurrent requests could both see "no
            // existing attempt" and both insert one -- MySQL has no
            // partial/filtered unique index to catch that after the fact.
            // Locking the always-present invoice row first serializes every
            // claim() call for this invoice (InnoDB's locking-read semantics
            // guarantee the second transaction's subsequent SELECT sees the
            // first transaction's committed insert, not a stale snapshot) --
            // see the class-level docblock's "Known race" note for what this
            // does NOT cover.
            $lockedInvoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            $existing = InvoiceWhatsAppDeliveryAttempt::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->where('normalized_recipient', $normalizedRecipient)
                ->whereIn('status', [
                    InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING,
                    InvoiceWhatsAppDeliveryAttempt::STATUS_PROCESSING,
                ])
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                // Reuse -- do NOT create a second in-flight attempt, do NOT
                // overwrite its frozen artifact, and do NOT render a new
                // PDF at all: the whole point of freezing at claim time is
                // that an existing active claim's document identity is
                // already fixed. Terminal attempts (sent/confirmed_failed/
                // indeterminate) are deliberately excluded from this check:
                // they never block a new claim here. A resend confirmation
                // UX is a future concern, not this service's job.
                return $existing;
            }

            // Only a genuinely NEW attempt ever reaches this point, so
            // InvoicePdfService::render() runs exactly once per new
            // attempt -- never for a duplicate/reused claim above.
            $attemptUuid = (string) Str::uuid();
            $pdfBytes = $this->pdfService->render($lockedInvoice);
            $filename = $this->pdfService->filename($lockedInvoice);
            $hash = hash('sha256', $pdfBytes);
            $byteLength = strlen($pdfBytes);
            $path = InvoiceWhatsAppDeliveryAttempt::documentArtifactPathFor($attemptUuid);

            // Write the artifact BEFORE creating the row: the filesystem is
            // not covered by the DB transaction, so a row must never be
            // able to exist pointing at bytes that were never actually
            // persisted. $disk->put() returns false on failure rather than
            // throwing (config/filesystems.php sets 'throw' => false for
            // this disk) -- checked explicitly rather than assumed.
            $written = $disk->put($path, $pdfBytes);

            if ($written === false) {
                throw new \RuntimeException(
                    "Failed to write the frozen invoice PDF artifact to private storage at [{$path}]; " .
                    'refusing to create a WhatsApp delivery attempt without a persisted document.',
                );
            }

            try {
                return InvoiceWhatsAppDeliveryAttempt::query()->create([
                    'invoice_id' => $lockedInvoice->id,
                    'attempt_uuid' => $attemptUuid,
                    'recipient_snapshot' => (string) $rawPhone,
                    'normalized_recipient' => $normalizedRecipient,
                    'document_filename' => $filename,
                    'document_hash' => $hash,
                    'document_byte_length' => $byteLength,
                    'provider' => $provider,
                    'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING,
                    'requested_by_user_id' => $requestedBy?->id,
                ]);
            } catch (\Throwable $e) {
                // The DB write failed after the artifact was already
                // written to disk (filesystem writes are not transactional
                // with the database) -- delete the now-orphaned file before
                // rethrowing, so it never lingers unreferenced by any row.
                // DB::transaction() rolls back the (never-committed) insert
                // attempt on its own once this exception propagates out of
                // the closure.
                $disk->delete($path);

                throw $e;
            }
        });
    }

    /**
     * Execute a previously-claimed attempt: pending -> processing -> a
     * terminal status. Safe to call more than once for the same attempt --
     * every call after the first is a no-op that returns the attempt's
     * current (already terminal, or still-processing-elsewhere) state
     * unchanged.
     */
    public function execute(InvoiceWhatsAppDeliveryAttempt $attempt): InvoiceWhatsAppDeliveryAttempt
    {
        $processing = $this->markProcessing($attempt);

        if ($processing === null) {
            // Stale/no-op: this attempt was not STATUS_PENDING at lock time
            // (already processing elsewhere, or already terminal). Never
            // re-send, never overwrite whatever is already there.
            return $attempt->fresh() ?? $attempt;
        }

        // Reload the invoice with a fresh, unscoped-by-any-stale-relation
        // query. Invoice uses SoftDeletes, whose global scope automatically
        // excludes a soft-deleted row from this query -- so this single
        // find() correctly treats BOTH a force-deleted invoice (invoice_id
        // already nulled by the FK's nullOnDelete) AND a soft-deleted one
        // (row still referenced, but hidden by the scope) as "missing",
        // per the approved design: a trashed invoice is not sent to. This
        // check is independent of the frozen-document-artifact contract
        // below -- it is a business rule about the invoice's own lifecycle,
        // not about document identity, so it is unaffected by no longer
        // re-rendering the PDF.
        $invoice = $processing->invoice_id !== null
            ? Invoice::query()->find($processing->invoice_id)
            : null;

        if ($invoice === null) {
            return $this->finalizeConfirmedFailed(
                $processing,
                'invoice_missing',
                'The invoice for this delivery attempt no longer exists or is not available (deleted or trashed).',
            );
        }

        return $this->attemptSend($processing);
    }

    /**
     * Atomically transition pending -> processing. Returns null (a stale/
     * no-op signal to execute()) if the attempt is not currently pending.
     */
    protected function markProcessing(InvoiceWhatsAppDeliveryAttempt $attempt): ?InvoiceWhatsAppDeliveryAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $locked = InvoiceWhatsAppDeliveryAttempt::query()->lockForUpdate()->find($attempt->id);

            if ($locked === null || $locked->status !== InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING) {
                return null;
            }

            $locked->update([
                'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_PROCESSING,
                'started_at' => now(),
            ]);

            return $locked;
        });
    }

    /**
     * Load the FROZEN PDF artifact from private storage, verify it still
     * matches the identity recorded at claim time, build the
     * provider-agnostic message from those exact bytes, and send it
     * through the attempt's FROZEN provider (never whatever is newly
     * configured as default).
     *
     * InvoicePdfService is deliberately NEVER called from this method (or
     * anywhere else in execute()'s path) -- see the class docblock's
     * "FROZEN-DOCUMENT-ARTIFACT CONTRACT" section. The three artifact
     * integrity checks below (exists / hash / byte length) are all
     * pre-provider failures: if any of them fails, no gateway/provider call
     * of any kind is made.
     *
     * Failure-classification boundary: $sendAttempted flips to true
     * immediately before (and only before) the one call that can possibly
     * reach a real provider (WhatsAppGatewayInterface::sendDocument()).
     * Every known exception type from that call already carries its own
     * correct classification (WhatsAppConfigurationException and
     * WhatsAppConfirmedSendFailureException are always confirmed;
     * WhatsAppIndeterminateSendException is always indeterminate). For any
     * OTHER \Throwable this method cannot recognize, $sendAttempted is the
     * deciding signal: not yet attempted -> confirmed_failed (nothing could
     * possibly have reached the provider); already attempting -> ambiguous
     * -> indeterminate (ADR-007/PaymentSessionStarter's same reasoning:
     * never risk a duplicate delivery over an unrecognized failure shape).
     */
    protected function attemptSend(InvoiceWhatsAppDeliveryAttempt $attempt): InvoiceWhatsAppDeliveryAttempt
    {
        $sendAttempted = false;

        try {
            $disk = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK);
            $path = $attempt->documentArtifactPath();

            if (! $disk->exists($path)) {
                // Pre-provider failure: nothing was sent, so this is
                // confirmed_failed, not indeterminate.
                return $this->finalizeConfirmedFailed(
                    $attempt,
                    'document_artifact_missing',
                    'The frozen invoice PDF artifact for this delivery attempt could not be found in ' .
                    'private storage; refusing to send. Nothing was transmitted to the provider.',
                );
            }

            $pdfBytes = $disk->get($path);
            $actualHash = hash('sha256', $pdfBytes);

            if (! hash_equals($attempt->document_hash, $actualHash)) {
                return $this->finalizeConfirmedFailed(
                    $attempt,
                    'document_artifact_hash_mismatch',
                    "The frozen invoice PDF artifact's SHA-256 hash no longer matches the value recorded " .
                    'at claim time; refusing to send a document that may have been altered or corrupted. ' .
                    'Nothing was transmitted to the provider.',
                );
            }

            $actualByteLength = strlen($pdfBytes);

            if ($actualByteLength !== $attempt->document_byte_length) {
                return $this->finalizeConfirmedFailed(
                    $attempt,
                    'document_artifact_length_mismatch',
                    "The frozen invoice PDF artifact's byte length no longer matches the value recorded " .
                    'at claim time; refusing to send a document that may have been altered or corrupted. ' .
                    'Nothing was transmitted to the provider.',
                );
            }

            $gateway = $this->whatsapp->gatewayFor($attempt->provider);

            $message = new WhatsAppDocumentMessage(
                recipient: $attempt->normalized_recipient,
                filename: $attempt->document_filename,
                mimeType: 'application/pdf',
                contents: $pdfBytes,
                // No caption contract is approved for invoice delivery yet
                // -- null rather than inventing business copy.
                caption: null,
            );

            if (DB::transactionLevel() !== 0) {
                // Mirrors PaymentSessionStarter::start()'s identical guard:
                // provider calls must never run inside a DB transaction (a
                // slow/network call would hold row locks for its entire
                // duration). This should be structurally unreachable given
                // execute()'s call shape -- it exists to fail loudly if a
                // future refactor accidentally nests this call.
                throw new \LogicException('WhatsApp provider calls must run outside database transactions.');
            }

            $sendAttempted = true;
            $result = $gateway->sendDocument($message);

            return $this->finalizeSent($attempt, $result);
        } catch (WhatsAppConfigurationException $e) {
            // Always pre-network-contact by that exception's own contract.
            return $this->finalizeConfirmedFailed($attempt, 'config_missing', $e->getMessage());
        } catch (WhatsAppConfirmedSendFailureException $e) {
            $code = 'provider_rejected' . ($e->providerErrorCode() !== null ? ':' . $e->providerErrorCode() : '');

            return $this->finalizeConfirmedFailed($attempt, $code, $e->getMessage());
        } catch (WhatsAppIndeterminateSendException $e) {
            return $this->finalizeIndeterminate($attempt, 'send_indeterminate', $e->getMessage());
        } catch (\Throwable $e) {
            return $sendAttempted
                ? $this->finalizeIndeterminate($attempt, 'unexpected_after_send_attempt', $e->getMessage())
                : $this->finalizeConfirmedFailed($attempt, 'unexpected_before_send_attempt', $e->getMessage());
        }
    }

    protected function finalizeSent(InvoiceWhatsAppDeliveryAttempt $attempt, WhatsAppSendResult $result): InvoiceWhatsAppDeliveryAttempt
    {
        return $this->finalize($attempt, function (InvoiceWhatsAppDeliveryAttempt $locked) use ($result) {
            $locked->update([
                'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_SENT,
                'provider_message_id' => $result->providerMessageId,
                'provider_response' => $result->raw,
                'failure_code' => null,
                'failure_message' => null,
                'finished_at' => now(),
            ]);
        });
    }

    protected function finalizeConfirmedFailed(InvoiceWhatsAppDeliveryAttempt $attempt, string $failureCode, string $failureMessage): InvoiceWhatsAppDeliveryAttempt
    {
        return $this->finalize($attempt, function (InvoiceWhatsAppDeliveryAttempt $locked) use ($failureCode, $failureMessage) {
            $locked->update([
                'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED,
                'failure_code' => $failureCode,
                'failure_message' => $failureMessage,
                'finished_at' => now(),
            ]);
        });
    }

    protected function finalizeIndeterminate(InvoiceWhatsAppDeliveryAttempt $attempt, string $failureCode, string $failureMessage): InvoiceWhatsAppDeliveryAttempt
    {
        return $this->finalize($attempt, function (InvoiceWhatsAppDeliveryAttempt $locked) use ($failureCode, $failureMessage) {
            $locked->update([
                'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE,
                'failure_code' => $failureCode,
                'failure_message' => $failureMessage,
                'finished_at' => now(),
            ]);
        });
    }

    /**
     * Shared finalize plumbing: lock the attempt row, and only write if it
     * is still STATUS_PROCESSING. If another worker/process already
     * finalized it (or it somehow never reached processing), this is a
     * no-op that returns the current persisted state -- a terminal status
     * is never overwritten.
     */
    protected function finalize(InvoiceWhatsAppDeliveryAttempt $attempt, \Closure $writer): InvoiceWhatsAppDeliveryAttempt
    {
        return DB::transaction(function () use ($attempt, $writer) {
            $locked = InvoiceWhatsAppDeliveryAttempt::query()->lockForUpdate()->find($attempt->id);

            if ($locked === null) {
                return $attempt;
            }

            if ($locked->status !== InvoiceWhatsAppDeliveryAttempt::STATUS_PROCESSING) {
                return $locked;
            }

            $writer($locked);

            return $locked->fresh();
        });
    }
}
