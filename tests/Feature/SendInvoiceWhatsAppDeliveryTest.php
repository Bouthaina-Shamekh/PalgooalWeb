<?php

namespace Tests\Feature;

use App\Jobs\SendInvoiceWhatsAppDelivery;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceWhatsAppDeliveryAttempt;
use App\Services\WhatsApp\InvoiceWhatsAppDeliveryService;
use App\WhatsApp\Contracts\WhatsAppGatewayInterface;
use App\WhatsApp\DTOs\WhatsAppDocumentMessage;
use App\WhatsApp\DTOs\WhatsAppSendResult;
use App\WhatsApp\Exceptions\WhatsAppIndeterminateSendException;
use App\WhatsApp\Gateways\MockWhatsAppGateway;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

/**
 * Tests the queue Job layer that executes an already-claimed
 * InvoiceWhatsAppDeliveryAttempt. Does NOT re-test the service's own
 * state-machine/artifact-integrity behavior (that is
 * InvoiceWhatsAppDeliveryServiceTest's job) -- these tests only prove the
 * job is a thin, correct wrapper: it loads by id, defers entirely to the
 * service, never retries automatically, and never carries PDF bytes in its
 * own payload.
 *
 * Uses MockWhatsAppGateway (config('whatsapp.default_provider') = 'mock')
 * for every test -- no real network I/O is possible: WhatsAppManager fails
 * closed with no silent fallback provider (see its own docblock), so as
 * long as this config value is 'mock', the only gateway that can ever be
 * resolved is MockWhatsAppGateway (or, where substituted, a Mockery double
 * bound under that same class name) -- never a real Meta Cloud API call.
 */
class SendInvoiceWhatsAppDeliveryTest extends TestCase
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

        Storage::fake(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── a-c. Happy path: pending -> sent, exact frozen bytes used ───────────

    public function test_job_executes_a_pending_attempt_and_marks_it_sent(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->claim($invoice);

        $job = new SendInvoiceWhatsAppDelivery($attempt->id);
        $job->handle(app(InvoiceWhatsAppDeliveryService::class));

        $fresh = $attempt->fresh();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $fresh->status);
    }

    public function test_job_persists_the_provider_message_id(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->claim($invoice);

        (new SendInvoiceWhatsAppDelivery($attempt->id))->handle(app(InvoiceWhatsAppDeliveryService::class));

        $fresh = $attempt->fresh();
        $this->assertNotEmpty($fresh->provider_message_id);
        $this->assertStringStartsWith('mock-', $fresh->provider_message_id);
    }

    /**
     * Proves the job does not introduce its own document-handling path: the
     * exact bytes frozen at claim() time (and nothing re-rendered or
     * re-encoded) are what reaches the gateway when driven through the job.
     */
    public function test_job_sends_the_exact_frozen_artifact_bytes_through_the_service(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->claim($invoice);
        $frozenBytes = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($attempt->documentArtifactPath());

        $capturedContents = null;
        $this->bindFakeGateway(function (WhatsAppDocumentMessage $message) use (&$capturedContents) {
            $capturedContents = $message->contents;

            return new WhatsAppSendResult(true, 'wamid.JOB_TEST', WhatsAppSendResult::STATUS_SENT, ['mock' => true]);
        });

        (new SendInvoiceWhatsAppDelivery($attempt->id))->handle(app(InvoiceWhatsAppDeliveryService::class));

        $this->assertNotNull($capturedContents);
        $this->assertSame($frozenBytes, $capturedContents);
    }

    /**
     * End-to-end sanity check that the job resolves InvoiceWhatsAppDeliveryService
     * through the container correctly when actually dispatched (dispatchSync
     * runs handle() through the container immediately, with no real queue
     * connection needed) -- not just when handle() is called directly.
     */
    public function test_dispatch_sync_resolves_the_service_and_executes_the_attempt(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->claim($invoice);

        SendInvoiceWhatsAppDelivery::dispatchSync($attempt->id);

        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $attempt->fresh()->status);
    }

    // ── d-f. Duplicate/stale job execution is a no-op ───────────────────────

    public function test_running_the_job_again_on_an_already_sent_attempt_does_not_resend(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->claim($invoice);

        (new SendInvoiceWhatsAppDelivery($attempt->id))->handle(app(InvoiceWhatsAppDeliveryService::class));
        $sent = $attempt->fresh();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $sent->status);

        [$sendCalled] = $this->bindFakeGateway(fn () => $this->fail('sendDocument must not be called for a duplicate job run on a sent attempt.'));

        // Simulates a duplicate queued job for the same attempt id (e.g. a
        // worker retry after a lost ack, or an accidental double-dispatch).
        (new SendInvoiceWhatsAppDelivery($attempt->id))->handle(app(InvoiceWhatsAppDeliveryService::class));

        $again = $attempt->fresh();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_SENT, $again->status);
        $this->assertSame($sent->provider_message_id, $again->provider_message_id);
        $this->assertFalse($sendCalled());
        // No second attempt row was created either.
        $this->assertSame(1, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    public function test_job_on_a_confirmed_failed_attempt_is_a_noop(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->claim($invoice);
        $invoice->forceDelete(); // drives execute() to confirmed_failed / invoice_missing

        (new SendInvoiceWhatsAppDelivery($attempt->id))->handle(app(InvoiceWhatsAppDeliveryService::class));
        $failed = $attempt->fresh();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $failed->status);

        [$sendCalled] = $this->bindFakeGateway(fn () => $this->fail('sendDocument must not be called for a job run on a confirmed_failed attempt.'));

        (new SendInvoiceWhatsAppDelivery($attempt->id))->handle(app(InvoiceWhatsAppDeliveryService::class));

        $again = $attempt->fresh();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED, $again->status);
        $this->assertSame($failed->failure_code, $again->failure_code);
        $this->assertFalse($sendCalled());
        $this->assertSame(1, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    public function test_job_on_an_indeterminate_attempt_is_a_noop(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->claim($invoice);

        [, $reset] = $this->bindFakeGateway(function () {
            throw new WhatsAppIndeterminateSendException('connection timed out');
        });

        (new SendInvoiceWhatsAppDelivery($attempt->id))->handle(app(InvoiceWhatsAppDeliveryService::class));
        $indeterminate = $attempt->fresh();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE, $indeterminate->status);

        $reset();
        [$sendCalled] = $this->bindFakeGateway(fn () => $this->fail('sendDocument must never be called again for an indeterminate attempt -- it may already have reached the provider.'));

        (new SendInvoiceWhatsAppDelivery($attempt->id))->handle(app(InvoiceWhatsAppDeliveryService::class));

        $again = $attempt->fresh();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE, $again->status);
        $this->assertFalse($sendCalled());
        $this->assertSame(1, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    // ── g. Missing attempt id ────────────────────────────────────────────────

    public function test_job_with_a_missing_attempt_id_is_a_safe_noop(): void
    {
        [$sendCalled] = $this->bindFakeGateway(fn () => $this->fail('sendDocument must not be called when the attempt row does not exist.'));

        // No exception, no retry-inducing throw -- just returns quietly.
        (new SendInvoiceWhatsAppDelivery(999999999))->handle(app(InvoiceWhatsAppDeliveryService::class));

        $this->assertFalse($sendCalled());
        $this->assertSame(0, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    // ── h. Retry policy ──────────────────────────────────────────────────────

    public function test_job_has_tries_set_to_one(): void
    {
        $job = new SendInvoiceWhatsAppDelivery(1);

        $this->assertSame(1, $job->tries);
    }

    public function test_job_declares_no_backoff_or_retry_until(): void
    {
        $job = new SendInvoiceWhatsAppDelivery(1);

        // Neither method exists on the job -- Laravel only consults them if
        // present, so their absence is itself the "no automatic backoff /
        // no extended retry window" guarantee alongside tries = 1.
        $this->assertFalse(method_exists($job, 'backoff'));
        $this->assertFalse(method_exists($job, 'retryUntil'));
    }

    // ── i. No PDF bytes travel through the job's own payload ───────────────

    public function test_job_payload_contains_only_the_attempt_id_never_pdf_bytes(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->claim($invoice);
        $frozenBytes = Storage::disk(InvoiceWhatsAppDeliveryAttempt::ARTIFACT_DISK)->get($attempt->documentArtifactPath());

        $job = new SendInvoiceWhatsAppDelivery($attempt->id);
        $reflection = new ReflectionClass($job);

        // 1. The constructor/business payload accepts only `attemptId`.
        // Checked against the constructor's own declared parameters, not
        // the object's full property list -- PHP reflection reports every
        // trait-composed property (Illuminate\Bus\Queueable,
        // InteractsWithQueue, SerializesModels: job, connection, queue,
        // delay, middleware, chained*, messageGroup, deduplicator,
        // afterCommit, ...) as "declared on" the class that uses the trait,
        // not on the trait itself, so filtering getProperties() by
        // getDeclaringClass() can never isolate just our own business
        // payload -- it also catches `tries`, which we genuinely do declare
        // ourselves. The constructor parameter list has no such ambiguity.
        $constructorParams = array_map(
            fn (\ReflectionParameter $p) => $p->getName(),
            $reflection->getConstructor()->getParameters(),
        );
        $this->assertSame(['attemptId'], $constructorParams);
        $this->assertSame($attempt->id, $job->attemptId);

        // 2. The job does not store the attempt/invoice models, PDF bytes,
        // document contents, or a gateway/provider/service instance under
        // any property name. Laravel's own queue metadata (tries included)
        // is explicitly allowed and deliberately NOT enumerated/asserted
        // against here -- only these specific, named business properties
        // are checked for absence.
        $allPropertyNames = array_map(
            fn (\ReflectionProperty $p) => $p->getName(),
            $reflection->getProperties(),
        );
        foreach ([
            'attempt', 'invoiceWhatsAppDeliveryAttempt',
            'invoice', 'client',
            'pdf', 'pdfBytes', 'bytes', 'contents', 'document', 'documentBytes', 'documentContents',
            'gateway', 'provider', 'whatsapp',
            'service', 'invoiceWhatsAppDeliveryService', 'pdfService',
        ] as $forbidden) {
            $this->assertNotContains(
                $forbidden,
                $allPropertyNames,
                "Job must not carry a '{$forbidden}' property -- only attemptId plus Laravel's own queue metadata are allowed.",
            );
        }

        // 3. Laravel queue metadata properties (including `tries`, which we
        // declare ourselves) are allowed and intentionally not asserted
        // against above.

        // 4-6. Serialized-job proof, unchanged and not weakened: the
        // payload that would actually be written to the queue connection
        // contains neither the PDF's magic header nor a meaningful
        // substring of the real frozen bytes.
        $serialized = serialize($job);
        $this->assertStringNotContainsString('%PDF', $serialized);
        $this->assertStringNotContainsString(substr($frozenBytes, 0, 32), $serialized);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function claim(Invoice $invoice): InvoiceWhatsAppDeliveryAttempt
    {
        return app(InvoiceWhatsAppDeliveryService::class)->claim($invoice);
    }

    /**
     * Bind a Mockery double implementing WhatsAppGatewayInterface into the
     * container under MockWhatsAppGateway::class -- identical technique to
     * InvoiceWhatsAppDeliveryServiceTest::bindFakeGateway(), reused here so
     * job tests can control/observe the provider call without any real
     * network I/O (WhatsAppManager only ever resolves this class name when
     * config('whatsapp.default_provider') === 'mock', set in setUp()).
     *
     * @return array{0: \Closure(): bool, 1: \Closure(): void} [sendCalled, reset]
     */
    private function bindFakeGateway(\Closure $onSendDocument): array
    {
        $called = false;

        $fake = Mockery::mock(WhatsAppGatewayInterface::class);
        $fake->shouldReceive('name')->andReturn(MockWhatsAppGateway::GATEWAY_NAME);
        $fake->shouldReceive('sendDocument')->andReturnUsing(function (WhatsAppDocumentMessage $message) use (&$called, $onSendDocument) {
            $called = true;

            return $onSendDocument($message);
        });

        $this->app->instance(MockWhatsAppGateway::class, $fake);

        return [
            fn () => $called,
            function () use (&$called) { $called = false; },
        ];
    }

    private function makeInvoice(array $overrides = []): Invoice
    {
        $phone = array_key_exists('phone', $overrides) ? $overrides['phone'] : '+970599123456';

        $client = Client::query()->create([
            'first_name' => 'WhatsApp',
            'last_name' => 'Job',
            'email' => Str::lower(Str::random(16)) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'WhatsApp Delivery Job Test',
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
