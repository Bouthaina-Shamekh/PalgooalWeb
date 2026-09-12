<?php

namespace App\Jobs;

use App\Models\InvoiceWhatsAppDeliveryAttempt;
use App\Services\WhatsApp\InvoiceWhatsAppDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued execution of one already-claimed InvoiceWhatsAppDeliveryAttempt.
 *
 * This job is a thin queue wrapper around
 * InvoiceWhatsAppDeliveryService::execute() -- it contains no provider
 * logic, no PDF rendering logic, and no state-machine duplication. All of
 * that safety (pending -> processing -> sent | confirmed_failed |
 * indeterminate, stale/duplicate-execute no-ops, frozen-artifact
 * verification, the provider call itself) already lives in the service and
 * is unchanged by this job's existence -- this job's only job is to load
 * the attempt by id and hand it to the service.
 *
 * The job receives ONLY the attempt's integer id, never PDF bytes or a
 * rendered document: InvoiceWhatsAppDeliveryAttempt::execute() reloads the
 * frozen artifact itself from private storage (see
 * InvoiceWhatsAppDeliveryService's "FROZEN-DOCUMENT-ARTIFACT CONTRACT"
 * docblock), so nothing document-shaped ever needs to travel through the
 * queue payload/serialization.
 *
 * NO AUTOMATIC RETRY ($tries = 1, no backoff, no retryUntil): the service
 * is the sole authority on classifying an outcome as confirmed_failed
 * (safe to retry with a fresh claim() later) vs indeterminate (the send MAY
 * have already reached the provider -- must never be automatically
 * retried/resent, per InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE's
 * own docblock). Letting Laravel's queue layer retry this job on failure
 * would risk exactly the duplicate-send scenario that distinction exists to
 * prevent. If execute() itself throws (it does not under normal operation --
 * every provider/config/artifact-integrity failure path already returns a
 * finalized attempt rather than throwing), $tries = 1 ensures the job is
 * marked failed once rather than silently retried.
 */
class SendInvoiceWhatsAppDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * No automatic retry -- see the class docblock's "NO AUTOMATIC RETRY"
     * section. Deliberately no $backoff/$retryUntil either: there is
     * nothing to back off into, since a second automatic attempt is never
     * safe here.
     */
    public $tries = 1;

    public function __construct(
        public int $attemptId,
    ) {
        $this->onQueue('whatsapp');
    }

    /**
     * Reload the attempt fresh (never trust a serialized copy of mutable
     * state across the queue boundary) and hand it to the service. If the
     * row no longer exists, this is a safe no-op -- there is nothing to
     * retry into existence, so this deliberately returns rather than
     * throwing (throwing here would only burn this job's one try to
     * discover the same "still missing" result again).
     */
    public function handle(InvoiceWhatsAppDeliveryService $service): void
    {
        $attempt = InvoiceWhatsAppDeliveryAttempt::query()->find($this->attemptId);

        if ($attempt === null) {
            return;
        }

        // Duplicate/stale execution (already sent/confirmed_failed/
        // indeterminate, or already processing) is entirely the service's
        // own concern -- execute() already no-ops safely for all of those
        // cases (see its docblock), so this job never needs to check
        // status itself before calling it.
        $service->execute($attempt);
    }

    /**
     * Dispatch this job for an already-claimed attempt, deferred until the
     * current database transaction commits.
     *
     * Not called from anywhere yet (no controller/route wiring exists for
     * this phase) -- provided so that whichever future caller triggers a
     * send does so correctly: InvoiceWhatsAppDeliveryService::claim()
     * writes the frozen PDF artifact to disk and creates the attempt row
     * inside its own transaction before returning, so by the time a caller
     * has an attempt to pass here, that transaction has already committed.
     * ->afterCommit() is still the right call (not a redundant one): it
     * protects the case where the caller itself wraps claim() + this
     * dispatch inside its OWN outer transaction, ensuring the job never
     * reaches a queue worker before the attempt row (and its artifact) are
     * actually durable and visible to it -- the exact hazard
     * App\Services\Billing\OrderActivationService::activate() already
     * guards against identically for ProvisionSubscription::dispatch(...)
     * ->afterCommit().
     */
    public static function dispatchForAttempt(InvoiceWhatsAppDeliveryAttempt $attempt): PendingDispatch
    {
        return static::dispatch($attempt->id)->afterCommit();
    }
}
