<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceWhatsAppDeliveryAttempt;
use App\Models\User;
use App\Services\Billing\InvoicePdfService;
use App\Services\WhatsApp\InvoiceWhatsAppDeliveryService;
use App\WhatsApp\Contracts\WhatsAppGatewayInterface;
use App\WhatsApp\DTOs\WhatsAppDocumentMessage;
use App\WhatsApp\DTOs\WhatsAppSendResult;
use App\WhatsApp\Exceptions\WhatsAppConfirmedSendFailureException;
use App\WhatsApp\Exceptions\WhatsAppIndeterminateSendException;
use App\WhatsApp\Gateways\MockWhatsAppGateway;
use App\WhatsApp\Exceptions\InvalidWhatsAppPhoneException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

/**
 * Uses MockWhatsAppGateway for every happy-path test (no network, no Meta).
 * For the failure-classification tests, a Mockery double implementing
 * WhatsAppGatewayInterface is bound INTO THE CONTAINER under
 * MockWhatsAppGateway::class -- the frozen provider key/class map entry
 * genuinely stays 'mock_whatsapp' / MockWhatsAppGateway::class throughout
 * (matching the task's "MockWhatsAppGateway only, no Meta" instruction);
 * only the concrete instance behind that class name is substituted, exactly
 * the same technique tests/Feature/PaymentAttemptIdentityTest.php already
 * uses (Mockery::mock(PaymentGatewayInterface::class)) to control gateway
 * behavior in this codebase's own established convention.
 *
 * FROZEN-DOCUMENT-ARTIFACT CONTRACT (Phase 4): Storage::fake() replaces
 * both the 'local' (private, ARTIFACT_DISK) and 'public' disks for every
 * test in this class -- no real filesystem is ever touched, and no network.
 * bindPdfRenderCounter() lets tests prove InvoicePdfService::render() is
 * called exactly the expected number of times (once for a genuinely new
 * claim(), zero times for a duplicate claim() or for any execute() call) --
 * the central guarantee this phase adds on top of the state machine itself.
 */
class InvoiceWhatsAppDeliveryServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['whatsapp.default_provider' => 'mock']);

        // Every test in this class gets an isolated fake filesystem for
        // BOTH disks -- 'local' (InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK,
        // where the frozen PDF artifact actually lives) and 'public' (so
        // tests can positively assert nothing ever lands there / no public
        // URL is ever created). No real disk or network is touched.
        Storage::fake(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function service(): InvoiceWhatsAppDeliveryService
    {
        return app(InvoiceWhatsAppDeliveryService::class);
    }

    // ── 1-4. claim() persists the approved snapshot ─────────────────────────

    public function test_claim_creates_a_pending_attempt(): void
    {
        $invoice = $this->makeInvoice();

        $attempt = $this->service()->claim($invoice);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING, $attempt->status);
        $this->assertSame($invoice->id, $attempt->invoice_id);
        $this->assertNotEmpty($attempt->attempt_uuid);
        $this->assertDatabaseHas('invoice_whatsapp_delivery_attempts', ['id' => $attempt->id, 'status' => 'pending']);
    }

    public function test_claim_snapshots_raw_and_normalized_phone(): void
    {
        $invoice = $this->makeInvoice(['phone' => '+970 59 912 3456']);

        $attempt = $this->service()->claim($invoice);

        $this->assertSame('+970 59 912 3456', $attempt->recipient_snapshot);
        $this->assertSame('970599123456', $attempt->normalized_recipient);
    }

    /**
     * document_hash/document_byte_length are the REAL SHA-256 hex digest
     * and REAL byte length of the ACTUAL frozen PDF bytes persisted to
     * private storage at claim() time -- never a fingerprint of unrelated
     * timestamps. See the service's class docblock ("FROZEN-DOCUMENT-
     * ARTIFACT CONTRACT") for why an earlier timestamp-fingerprint
     * workaround was tried and then removed: it could not prove which
     * exact bytes were requested/sent, since the rendered PDF can depend on
     * data outside Invoice/InvoiceItem entirely (client data, company/
     * general settings, logo, other view dependencies).
     */
    public function test_claim_snapshots_filename_hash_length_and_provider(): void
    {
        $invoice = $this->makeInvoice();
        $pdfService = app(InvoicePdfService::class);
        $expectedFilename = $pdfService->filename($invoice);

        $attempt = $this->service()->claim($invoice);

        $this->assertSame($expectedFilename, $attempt->document_filename);

        $path = $attempt->documentArtifactPath();
        $this->assertTrue(Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->exists($path));

        $storedBytes = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($path);

        $this->assertSame(hash('sha256', $storedBytes), $attempt->document_hash);
        $this->assertSame(strlen($storedBytes), $attempt->document_byte_length);
        // The frozen provider is the config('whatsapp.providers') KEY
        // ("mock"), not the gateway's own ->name() ("mock_whatsapp") --
        // only the key is a valid WhatsAppManager::gatewayFor() argument.
        $this->assertSame('mock', $attempt->provider);
    }

    public function test_claim_stores_the_requesting_user(): void
    {
        $invoice = $this->makeInvoice();
        $admin = User::factory()->create(['super_admin' => true]);

        $attempt = $this->service()->claim($invoice, $admin);

        $this->assertSame($admin->id, $attempt->requested_by_user_id);
    }

    public function test_claim_allows_null_requested_by(): void
    {
        $invoice = $this->makeInvoice();

        $attempt = $this->service()->claim($invoice);

        $this->assertNull($attempt->requested_by_user_id);
    }

    // ── 5-6. Phone failures happen before any attempt row is created ───────

    public function test_missing_client_phone_fails_before_attempt_creation(): void
    {
        $invoice = $this->makeInvoice(['phone' => null]);

        try {
            $this->service()->claim($invoice);
            $this->fail('Expected InvalidWhatsAppPhoneException.');
        } catch (InvalidWhatsAppPhoneException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    public function test_local_phone_with_no_country_code_fails_before_attempt_creation(): void
    {
        $invoice = $this->makeInvoice(['phone' => '0599123456']);

        try {
            $this->service()->claim($invoice);
            $this->fail('Expected InvalidWhatsAppPhoneException.');
        } catch (InvalidWhatsAppPhoneException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    // ── 7-9. Duplicate / historical claim behavior ──────────────────────────

    public function test_duplicate_pending_claim_reuses_the_same_attempt(): void
    {
        $invoice = $this->makeInvoice();

        $first = $this->service()->claim($invoice);
        $second = $this->service()->claim($invoice);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    /**
     * A duplicate claim() for an already-pending attempt must not render a
     * new PDF at all (not even to discard it), and must not touch the
     * artifact already frozen on disk for the first attempt.
     */
    public function test_duplicate_pending_claim_does_not_render_or_touch_the_frozen_artifact(): void
    {
        $invoice = $this->makeInvoice();
        $first = $this->service()->claim($invoice);
        $originalBytes = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($first->documentArtifactPath());

        $renderCount = $this->bindPdfRenderCounter();
        $second = $this->service()->claim($invoice);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(0, $renderCount());
        $this->assertSame(
            $originalBytes,
            Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($first->documentArtifactPath()),
        );
    }

    public function test_duplicate_processing_claim_reuses_the_same_attempt(): void
    {
        $invoice = $this->makeInvoice();
        $first = $this->service()->claim($invoice);
        $first->update(['status' => InvoiceWhatsAppDeliveryAttempt::STATUS_PROCESSING, 'started_at' => now()]);

        $second = $this->service()->claim($invoice);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    /**
     * Same guarantee as the pending case above, for a duplicate claim() of
     * an attempt that is already STATUS_PROCESSING.
     */
    public function test_duplicate_processing_claim_does_not_render_or_touch_the_frozen_artifact(): void
    {
        $invoice = $this->makeInvoice();
        $first = $this->service()->claim($invoice);
        $first->update(['status' => InvoiceWhatsAppDeliveryAttempt::STATUS_PROCESSING, 'started_at' => now()]);
        $originalBytes = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($first->documentArtifactPath());

        $renderCount = $this->bindPdfRenderCounter();
        $second = $this->service()->claim($invoice);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(0, $renderCount());
        $this->assertSame(
            $originalBytes,
            Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($first->documentArtifactPath()),
        );
    }

    public function test_historical_sent_attempt_does_not_block_a_new_claim(): void
    {
        $invoice = $this->makeInvoice();
        $first = $this->service()->claim($invoice);
        $first->update([
            'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_SENT,
            'finished_at' => now(),
            'provider_message_id' => 'wamid.OLD',
        ]);

        $second = $this->service()->claim($invoice);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    // ── Frozen-artifact identity & storage guarantees (Phase 4) ─────────────

    public function test_claim_renders_the_pdf_exactly_once_for_a_new_attempt(): void
    {
        $invoice = $this->makeInvoice();
        $renderCount = $this->bindPdfRenderCounter();

        $this->service()->claim($invoice);

        $this->assertSame(1, $renderCount());
    }

    public function test_document_artifact_path_is_deterministic_from_attempt_uuid(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $this->assertSame(
            'invoice-whatsapp-deliveries/' . $attempt->attempt_uuid . '.pdf',
            $attempt->documentArtifactPath(),
        );
        // The same path is derivable from just the UUID, with no need to
        // ever load the model row -- proving the path needs no extra
        // database column (it is a pure function of attempt_uuid, which
        // already exists as a column).
        $this->assertSame(
            InvoiceWhatsAppDeliveryAttempt::documentArtifactPathFor($attempt->attempt_uuid),
            $attempt->documentArtifactPath(),
        );
    }

    public function test_no_public_storage_url_is_ever_created(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $this->service()->execute($attempt);

        // Nothing was ever written to the 'public' disk (the only disk in
        // this app that is web-servable / has a "url" config key).
        $this->assertSame([], Storage::disk('public')->allFiles());

        // ARTIFACT_DISK itself is 'local', whose config/filesystems.php
        // definition has no "url" key and no storage:link entry -- unlike
        // "public" -- so nothing written there is reachable by a public URL.
        $this->assertSame('local', InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK);
        $this->assertArrayNotHasKey('url', config('filesystems.disks.local', []));
    }

    // ── 10-13. execute(): pending -> processing -> sent ─────────────────────

    public function test_execute_transitions_pending_to_sent(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $result->status);
        $this->assertNotNull($result->started_at);
        $this->assertNotNull($result->finished_at);
    }

    public function test_execute_stores_the_provider_message_id(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $result = $this->service()->execute($attempt);

        $this->assertNotEmpty($result->provider_message_id);
        $this->assertStringStartsWith('mock-', $result->provider_message_id);
    }

    public function test_execute_stores_the_provider_response_safely(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $result = $this->service()->execute($attempt);

        $this->assertIsArray($result->provider_response);
        $this->assertTrue($result->provider_response['mock'] ?? false);
        $this->assertSame($result->normalized_recipient, $result->provider_response['recipient']);
    }

    public function test_execute_sets_finished_at_on_success(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $before = now();
        $result = $this->service()->execute($attempt);

        $this->assertNotNull($result->finished_at);
        $this->assertTrue($result->finished_at->greaterThanOrEqualTo($before->subSecond()));
    }

    /**
     * The whole point of freezing the artifact at claim() time: execute()
     * must NEVER call InvoicePdfService again.
     */
    public function test_execute_does_not_re_render_the_pdf(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $renderCount = $this->bindPdfRenderCounter();
        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $result->status);
        $this->assertSame(0, $renderCount());
    }

    /**
     * The exact bytes frozen at claim() time are what reaches
     * WhatsAppDocumentMessage and therefore the gateway -- not a
     * re-rendered copy, not a re-encoded copy, the SAME bytes.
     */
    public function test_execute_sends_the_exact_frozen_bytes_to_the_gateway(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $frozenBytes = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($attempt->documentArtifactPath());

        $capturedContents = null;
        $fake = Mockery::mock(WhatsAppGatewayInterface::class);
        $fake->shouldReceive('name')->andReturn(MockWhatsAppGateway::GATEWAY_NAME);
        $fake->shouldReceive('sendDocument')->andReturnUsing(function (WhatsAppDocumentMessage $message) use (&$capturedContents) {
            $capturedContents = $message->contents;

            return new WhatsAppSendResult(
                success: true,
                providerMessageId: 'wamid.CAPTURED',
                providerStatus: WhatsAppSendResult::STATUS_SENT,
                raw: ['mock' => true, 'recipient' => $message->recipient],
            );
        });
        $this->app->instance(MockWhatsAppGateway::class, $fake);

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $result->status);
        $this->assertNotNull($capturedContents);
        $this->assertSame($frozenBytes, $capturedContents);
    }

    /**
     * The frozen artifact is the only exact record of what was transmitted
     * -- it must NOT be deleted immediately after a successful send.
     * Retention/deletion policy is a separate, later concern.
     */
    public function test_frozen_artifact_remains_on_disk_after_a_successful_send(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $result->status);
        $this->assertTrue(Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->exists($attempt->documentArtifactPath()));
    }

    // ── Artifact integrity verification in execute() (Phase 4) ──────────────

    /**
     * One deliberate behavior change from the earlier (removed) timestamp-
     * fingerprint contract: an invoice edited AFTER claim() no longer
     * blocks or alters that attempt's send. execute() transmits the exact
     * document that was frozen at claim time, regardless of what happens to
     * the invoice afterward -- that is the entire point of freezing.
     */
    public function test_invoice_changing_after_claim_does_not_block_sending_the_frozen_document(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $frozenBytes = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($attempt->documentArtifactPath());

        // A legitimate content change between claim and execute: marking
        // the invoice paid changes visible rendered content (status pill,
        // payment-state block, "paid date" row) -- but the FROZEN artifact
        // is unaffected, so execute() must still succeed and send it as-is.
        $invoice->update(['status' => 'paid', 'paid_date' => now()]);

        $capturedContents = null;
        $fake = Mockery::mock(WhatsAppGatewayInterface::class);
        $fake->shouldReceive('name')->andReturn(MockWhatsAppGateway::GATEWAY_NAME);
        $fake->shouldReceive('sendDocument')->andReturnUsing(function (WhatsAppDocumentMessage $message) use (&$capturedContents) {
            $capturedContents = $message->contents;

            return new WhatsAppSendResult(true, 'wamid.STILL_SENT', WhatsAppSendResult::STATUS_SENT, ['mock' => true]);
        });
        $this->app->instance(MockWhatsAppGateway::class, $fake);

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $result->status);
        $this->assertSame($frozenBytes, $capturedContents);
    }

    public function test_missing_artifact_is_a_confirmed_failure_before_any_provider_call(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->delete($attempt->documentArtifactPath());

        [$sendCalled] = $this->bindFakeGateway(fn () => $this->fail('sendDocument should not be called.'));

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $result->status);
        $this->assertSame('document_artifact_missing', $result->failure_code);
        $this->assertFalse($sendCalled());
    }

    /**
     * Corrupt the artifact's CONTENT while keeping its byte length the same
     * -- isolates the hash check from the length check (a length change
     * would almost certainly change the hash too, so testing them
     * independently means changing exactly one property at a time).
     */
    public function test_hash_mismatch_is_a_confirmed_failure_before_any_provider_call(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $path = $attempt->documentArtifactPath();
        $original = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($path);

        // Flip the first byte -- same length, different content, so
        // document_byte_length still matches but document_hash cannot.
        $corrupted = chr(ord($original[0]) ^ 0xFF) . substr($original, 1);
        Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->put($path, $corrupted);

        [$sendCalled] = $this->bindFakeGateway(fn () => $this->fail('sendDocument should not be called.'));

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $result->status);
        $this->assertSame('document_artifact_hash_mismatch', $result->failure_code);
        $this->assertFalse($sendCalled());
    }

    /**
     * Corrupt the RECORDED expectation instead of the file, so the hash
     * check (which runs first) passes on the untouched file and the length
     * check is what actually fails -- the only way to isolate it, since any
     * real change to the file's length would also change its hash and be
     * caught by the earlier check first.
     */
    public function test_length_mismatch_is_a_confirmed_failure_before_any_provider_call(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $attempt->update(['document_byte_length' => $attempt->document_byte_length + 1]);

        [$sendCalled] = $this->bindFakeGateway(fn () => $this->fail('sendDocument should not be called.'));

        $result = $this->service()->execute($attempt->fresh());

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $result->status);
        $this->assertSame('document_artifact_length_mismatch', $result->failure_code);
        $this->assertFalse($sendCalled());
    }

    // ── 15-16. Missing/trashed invoice blocks send ──────────────────────────

    public function test_force_deleted_invoice_blocks_send(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $invoice->forceDelete();

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $result->status);
        $this->assertSame('invoice_missing', $result->failure_code);
    }

    public function test_soft_deleted_invoice_blocks_send(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $invoice->delete(); // soft delete only

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $result->status);
        $this->assertSame('invoice_missing', $result->failure_code);
    }

    // ── 17-18. Confirmed provider rejection ─────────────────────────────────

    public function test_confirmed_send_failure_finalizes_as_confirmed_failed(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $this->bindFakeGateway(function () {
            throw new WhatsAppConfirmedSendFailureException('Meta error: rejected', '131026');
        });

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $result->status);
    }

    public function test_provider_error_code_is_preserved_into_failure_code(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $this->bindFakeGateway(function () {
            throw new WhatsAppConfirmedSendFailureException('Meta error: rejected', '131026');
        });

        $result = $this->service()->execute($attempt);

        $this->assertSame('provider_rejected:131026', $result->failure_code);
    }

    public function test_confirmed_send_failure_without_a_provider_code_still_gets_a_clear_failure_code(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $this->bindFakeGateway(function () {
            throw new WhatsAppConfirmedSendFailureException('Meta error: rejected, no code');
        });

        $result = $this->service()->execute($attempt);

        $this->assertSame('provider_rejected', $result->failure_code);
    }

    // ── 19-20, 23. Indeterminate failure, never auto-retried ───────────────

    public function test_indeterminate_send_failure_finalizes_as_indeterminate(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        $this->bindFakeGateway(function () {
            throw new WhatsAppIndeterminateSendException('connection timed out');
        });

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE, $result->status);
        $this->assertSame('send_indeterminate', $result->failure_code);
    }

    public function test_indeterminate_attempt_is_never_automatically_resent_on_a_second_execute_call(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        [$sendCalled, $reset] = $this->bindFakeGateway(function () {
            throw new WhatsAppIndeterminateSendException('connection timed out');
        });

        $first = $this->service()->execute($attempt);
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE, $first->status);

        $reset(); // clear the call flag, then prove a second execute() never invokes sendDocument again
        $second = $this->service()->execute($first);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE, $second->status);
        $this->assertFalse($sendCalled());
    }

    // ── 21-23. execute() on a terminal attempt is a stale/no-op ─────────────

    public function test_execute_on_an_already_sent_attempt_is_a_stale_noop(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $sent = $this->service()->execute($attempt);
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $sent->status);

        [$sendCalled] = $this->bindFakeGateway(fn () => $this->fail('sendDocument should not be called again.'));

        $again = $this->service()->execute($sent);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $again->status);
        $this->assertSame($sent->provider_message_id, $again->provider_message_id);
        $this->assertFalse($sendCalled());
    }

    public function test_execute_on_a_confirmed_failed_attempt_is_a_stale_noop(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $invoice->forceDelete();
        $failed = $this->service()->execute($attempt);
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $failed->status);

        $again = $this->service()->execute($failed);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $again->status);
        $this->assertSame($failed->failure_code, $again->failure_code);
    }

    public function test_execute_on_an_indeterminate_attempt_is_a_stale_noop(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        [$sendCalled, $reset] = $this->bindFakeGateway(function () {
            throw new WhatsAppIndeterminateSendException('connection timed out');
        });
        $indeterminate = $this->service()->execute($attempt);
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE, $indeterminate->status);

        $reset();
        $again = $this->service()->execute($indeterminate);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE, $again->status);
        $this->assertFalse($sendCalled());
    }

    // ── 24. The provider frozen on the attempt is authoritative ─────────────

    public function test_frozen_provider_is_used_even_if_the_default_config_changes_later(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);
        $this->assertSame('mock', $attempt->provider);

        // Break/replace the default AFTER claim -- execute() must not care.
        config(['whatsapp.default_provider' => 'definitely_not_configured']);

        $result = $this->service()->execute($attempt);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $result->status);
    }

    // ── 25. Provider calls never run inside a DB transaction ───────────────

    public function test_execute_refuses_to_send_when_accidentally_invoked_inside_a_transaction(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->service()->claim($invoice);

        [$sendCalled] = $this->bindFakeGateway(fn () => $this->fail('sendDocument must never be reached inside a transaction.'));

        $result = DB::transaction(fn () => $this->service()->execute($attempt));

        // The internal DB::transactionLevel() guard fires before the send
        // attempt begins, so this becomes a safely-finalized failure rather
        // than an uncaught crash or (worse) a real send from inside a
        // transaction.
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $result->status);
        $this->assertSame('unexpected_before_send_attempt', $result->failure_code);
        $this->assertFalse($sendCalled());
    }

    // ── 26. No invoice/payment/order/provisioning mutation ─────────────────

    public function test_full_claim_and_execute_flow_does_not_mutate_the_invoice(): void
    {
        $invoice = $this->makeInvoice();

        $before = [
            'status' => $invoice->status,
            'total_cents' => $invoice->total_cents,
            'paid_date' => $invoice->paid_date,
            'client_id' => $invoice->client_id,
        ];

        $attempt = $this->service()->claim($invoice);
        $this->service()->execute($attempt);

        $fresh = $invoice->fresh();
        $this->assertSame($before['status'], $fresh->status);
        $this->assertSame($before['total_cents'], $fresh->total_cents);
        $this->assertEquals($before['paid_date'], $fresh->paid_date);
        $this->assertSame($before['client_id'], $fresh->client_id);
    }

    // ── DB/filesystem ordering safety (Phase 4) ─────────────────────────────

    /**
     * Simulates "DB row creation fails AFTER the artifact was already
     * written to disk" via a genuine unique-constraint collision (rather
     * than mocking Eloquent internals): Str::createUuidsUsing() pins every
     * subsequent Str::uuid() call to one fixed value, and a decoy row is
     * pre-seeded with that exact attempt_uuid for a DIFFERENT invoice/
     * recipient/status combination -- so it does NOT satisfy claim()'s own
     * "existing pending/processing attempt" reuse check (which matches on
     * invoice_id + normalized_recipient + status), yet the new INSERT still
     * collides on attempt_uuid's unique index, producing a real database
     * failure after the file write already happened.
     *
     * Asserts: no orphan file survives (it is deleted before the exception
     * propagates), no new attempt row was created for the claimed invoice
     * (only the pre-existing decoy remains), and the exception is not
     * silently swallowed.
     */
    public function test_db_failure_after_artifact_write_cleans_up_the_orphan_file_and_leaves_no_broken_row(): void
    {
        $fixedUuid = (string) Str::uuid();

        // Decoy: same attempt_uuid, but a different invoice/recipient/status
        // so it is invisible to claim()'s own duplicate-reuse lookup.
        $decoyInvoice = $this->makeInvoice(['phone' => '+970599000001']);
        InvoiceWhatsAppDeliveryAttempt::query()->create([
            'invoice_id' => $decoyInvoice->id,
            'attempt_uuid' => $fixedUuid,
            'recipient_snapshot' => '+970599000001',
            'normalized_recipient' => '970599000001',
            'document_filename' => 'invoice-DECOY.pdf',
            'document_hash' => str_repeat('0', 64),
            'document_byte_length' => 1,
            'provider' => 'mock',
            'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_SENT,
        ]);

        Str::createUuidsUsing(fn () => \Illuminate\Support\Str::of($fixedUuid));

        try {
            $invoice = $this->makeInvoice(['phone' => '+970599123456']);

            try {
                $this->service()->claim($invoice);
                $this->fail('Expected a unique-constraint violation on attempt_uuid.');
            } catch (\Throwable) {
                $this->addToAssertionCount(1);
            }

            // No orphan file: the artifact written for the failed insert was
            // cleaned up before the exception propagated.
            $expectedPath = InvoiceWhatsAppDeliveryAttempt::documentArtifactPathFor($fixedUuid);
            $this->assertFalse(Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->exists($expectedPath));

            // No usable pending row exists for the invoice that failed to claim.
            $this->assertSame(
                0,
                InvoiceWhatsAppDeliveryAttempt::query()->where('invoice_id', $invoice->id)->count(),
            );
            // Only the original decoy row still exists.
            $this->assertSame(1, InvoiceWhatsAppDeliveryAttempt::query()->count());
        } finally {
            Str::createUuidsNormally();
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Bind a Mockery double implementing WhatsAppGatewayInterface into the
     * container under MockWhatsAppGateway::class -- see the class docblock
     * for why this satisfies "use MockWhatsAppGateway only, no Meta".
     *
     * @return array{0: \Closure(): bool, 1: \Closure(): void} [sendCalled, reset]
     */
    private function bindFakeGateway(\Closure $onSendDocument): array
    {
        $called = false;

        $fake = Mockery::mock(WhatsAppGatewayInterface::class);
        $fake->shouldReceive('name')->andReturn(MockWhatsAppGateway::GATEWAY_NAME);
        $fake->shouldReceive('sendDocument')->andReturnUsing(function () use (&$called, $onSendDocument) {
            $called = true;

            return $onSendDocument();
        });

        $this->app->instance(MockWhatsAppGateway::class, $fake);

        return [
            fn () => $called,
            function () use (&$called) { $called = false; },
        ];
    }

    /**
     * Bind a Mockery partial mock of the REAL InvoicePdfService into the
     * container, counting calls to render() while still delegating to the
     * real implementation (so the actual PDF bytes/behavior are unchanged
     * -- only the call count is observed). Lets tests assert "rendered
     * exactly once" / "never re-rendered" instead of only inferring it
     * indirectly.
     *
     * @return \Closure(): int
     */
    private function bindPdfRenderCounter(): \Closure
    {
        $count = 0;
        $real = app(InvoicePdfService::class);

        $spy = Mockery::mock(InvoicePdfService::class)->makePartial();
        $spy->shouldReceive('render')->andReturnUsing(function (Invoice $invoice) use (&$count, $real) {
            $count++;

            return $real->render($invoice);
        });

        $this->app->instance(InvoicePdfService::class, $spy);

        return function () use (&$count) {
            return $count;
        };
    }

    private function makeInvoice(array $overrides = []): Invoice
    {
        $phone = array_key_exists('phone', $overrides) ? $overrides['phone'] : '+970599123456';

        $client = Client::query()->create([
            'first_name' => 'WhatsApp',
            'last_name' => 'Delivery',
            'email' => Str::lower(Str::random(16)) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'WhatsApp Delivery Service Test',
            'can_login' => true,
            'phone' => $phone,
        ]);

        return Invoice::query()->create([
            'client_id' => $client->id,
            'number' => 'INV-' . strtoupper(Str::random(12)),
            'status' => 'unpaid',
            'subtotal_cents' => 100000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 100000,
            'currency' => 'USD',
            'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
        ]);
    }
}
