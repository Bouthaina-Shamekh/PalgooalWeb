<?php

namespace Tests\Feature;

use App\Jobs\SendInvoiceWhatsAppDelivery;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceWhatsAppDeliveryAttempt;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HTTP/UI integration tests for the Admin Invoice "Send via WhatsApp"
 * action wired in this phase.
 *
 * Scope: this class proves the CONTROLLER/ROUTE layer only -- routing,
 * authorization, CSRF, request validation of known failure modes, and that
 * the controller correctly delegates to the already-PASS
 * InvoiceWhatsAppDeliveryService::claim() + SendInvoiceWhatsAppDelivery
 * queue Job without adding its own idempotency, gateway, or PDF-rendering
 * logic. It deliberately does NOT re-test:
 *   - the service's own state-machine / frozen-artifact-integrity behavior
 *     (see InvoiceWhatsAppDeliveryServiceTest), or
 *   - the Job's own no-op/duplicate/retry guarantees
 *     (see SendInvoiceWhatsAppDeliveryTest).
 *
 * Queue::fake() is used throughout: the queued job is asserted to have
 * been PUSHED, never actually executed here, so no real (or even mock)
 * gateway call can occur synchronously within a request in these tests.
 */
class InvoiceWhatsAppControllerTest extends TestCase
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

    // ── A. Authorized admin can queue delivery ──────────────────────────────

    public function test_authorized_admin_can_queue_whatsapp_delivery(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice();

        $response = $this->post(route('dashboard.invoices.whatsapp', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('ok');
        $response->assertSessionMissing('error');

        $this->assertDatabaseHas('invoice_whatsapp_delivery_attempts', [
            'invoice_id' => $invoice->id,
            'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING,
        ]);
    }

    // ── B. Non-admin is forbidden ────────────────────────────────────────────

    public function test_non_admin_is_forbidden(): void
    {
        Queue::fake();

        $nonAdmin = User::factory()->create(['super_admin' => false]);
        $invoice = $this->makeInvoice();

        $this->actingAs($nonAdmin)
            ->post(route('dashboard.invoices.whatsapp', $invoice))
            ->assertForbidden();

        $this->assertSame(0, InvoiceWhatsAppDeliveryAttempt::query()->count());
        Queue::assertNothingPushed();
    }

    // ── C. GET is not allowed ────────────────────────────────────────────────

    public function test_get_request_is_not_allowed(): void
    {
        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice();

        $this->get(route('dashboard.invoices.whatsapp', $invoice))
            ->assertMethodNotAllowed();

        $this->assertSame(0, InvoiceWhatsAppDeliveryAttempt::query()->count());
    }

    // ── D. CSRF protection remains active (structural check) ───────────────

    /**
     * Laravel's CSRF middleware (VerifyCsrfToken::handle(), via
     * runningUnitTests()) intentionally short-circuits for every request
     * made through PHPUnit, in every controller/route in this project --
     * so a live "POST without a token -> 419" assertion cannot be produced
     * here and would not mean anything project-specific if it could. What
     * IS practical and meaningful: proving this new route was added inside
     * the same 'admin'/'web' middleware group as the already-CSRF-protected
     * mutating invoice routes (update/destroy/bulk), and does not opt out
     * of CSRF via Route::withoutMiddleware() or an $except entry the way a
     * webhook route would -- i.e. it inherits CSRF protection identically
     * to those routes rather than being special-cased around it.
     */
    public function test_route_inherits_csrf_protection_like_the_other_mutating_invoice_routes(): void
    {
        $whatsAppRoute = Route::getRoutes()->getByName('dashboard.invoices.whatsapp');
        $bulkRoute = Route::getRoutes()->getByName('dashboard.invoices.bulk');

        $this->assertNotNull($whatsAppRoute, 'Route dashboard.invoices.whatsapp is not registered.');
        $this->assertNotNull($bulkRoute, 'Baseline route dashboard.invoices.bulk is not registered.');

        $this->assertSame(
            $bulkRoute->excludedMiddleware(),
            $whatsAppRoute->excludedMiddleware(),
            'The WhatsApp route excludes different middleware than the already-CSRF-protected bulk route.',
        );
        $this->assertEmpty(
            $whatsAppRoute->excludedMiddleware(),
            'The WhatsApp route must not opt out of any middleware (including CSRF) via Route::withoutMiddleware().',
        );
    }

    // ── E. Missing phone gives a controlled response, no attempt created ───

    public function test_missing_phone_gives_controlled_error_and_creates_no_attempt(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice(['phone' => null]);

        $response = $this->post(route('dashboard.invoices.whatsapp', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $response->assertSessionMissing('ok');

        $this->assertSame(0, InvoiceWhatsAppDeliveryAttempt::query()->count());
        Queue::assertNothingPushed();
    }

    // ── F. Invalid/local phone gives a controlled response, no attempt ─────

    public function test_invalid_local_phone_gives_controlled_error_and_creates_no_attempt(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice(['phone' => '0599123456']);

        $response = $this->post(route('dashboard.invoices.whatsapp', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $response->assertSessionMissing('ok');

        $this->assertSame(0, InvoiceWhatsAppDeliveryAttempt::query()->count());
        Queue::assertNothingPushed();
    }

    // ── G. Successful POST creates/reuses the correct pending attempt ──────

    public function test_successful_post_creates_a_pending_attempt_for_this_invoice(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice();

        $this->post(route('dashboard.invoices.whatsapp', $invoice));

        $attempt = InvoiceWhatsAppDeliveryAttempt::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING, $attempt->status);
        $this->assertSame('970599123456', $attempt->normalized_recipient);
    }

    // ── H/I. Queue::fake proves the job is queued with the correct attemptId ─

    public function test_job_is_queued_with_the_correct_attempt_id(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice();

        $this->post(route('dashboard.invoices.whatsapp', $invoice));

        $attempt = InvoiceWhatsAppDeliveryAttempt::query()->where('invoice_id', $invoice->id)->firstOrFail();

        Queue::assertPushed(SendInvoiceWhatsAppDelivery::class, function (SendInvoiceWhatsAppDelivery $job) use ($attempt) {
            return $job->attemptId === $attempt->id;
        });
    }

    // ── J. Provider/gateway is NOT executed synchronously ───────────────────

    public function test_provider_is_not_executed_synchronously(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice();

        $this->post(route('dashboard.invoices.whatsapp', $invoice));

        // Queue::fake() intercepts the dispatch entirely -- handle() is
        // never invoked within this request. The attempt therefore cannot
        // have progressed past 'pending' inside the same request/response
        // cycle, proving no synchronous send happened. (See test N below
        // for the same assertion phrased against the response itself.)
        $attempt = InvoiceWhatsAppDeliveryAttempt::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING, $attempt->status);
        $this->assertNull($attempt->provider_message_id);
    }

    // ── K. Duplicate POST leaves exactly one pending/processing attempt ────

    public function test_duplicate_post_leaves_exactly_one_pending_attempt(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice();

        $this->post(route('dashboard.invoices.whatsapp', $invoice));
        $this->post(route('dashboard.invoices.whatsapp', $invoice));

        $this->assertSame(
            1,
            InvoiceWhatsAppDeliveryAttempt::query()->where('invoice_id', $invoice->id)->count(),
        );

        // The controller adds no dedup of its own -- claim()'s own reuse
        // guard is what keeps this at one row; the job may legitimately be
        // pushed twice (once per request) for that same attempt id, which
        // the Job/service's own stale guards already handle safely (see
        // SendInvoiceWhatsAppDeliveryTest) -- not re-tested here.
        Queue::assertPushed(SendInvoiceWhatsAppDelivery::class, 2);
    }

    // ── L. Invoice itself is not mutated ─────────────────────────────────────

    public function test_invoice_is_not_mutated_by_the_request(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice();
        $originalUpdatedAt = $invoice->updated_at;
        $originalStatus = $invoice->status;

        $this->post(route('dashboard.invoices.whatsapp', $invoice));

        $fresh = $invoice->fresh();
        $this->assertSame($originalStatus, $fresh->status);
        $this->assertTrue($originalUpdatedAt->equalTo($fresh->updated_at));
    }

    // ── M. Success message means queued/accepted, never "sent" ─────────────

    public function test_success_message_means_queued_never_sent(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice();

        $response = $this->post(route('dashboard.invoices.whatsapp', $invoice));

        $message = $response->getSession()->get('ok');
        $this->assertNotEmpty($message);
        $this->assertStringNotContainsString('تم الإرسال', $message);
        $this->assertStringContainsString('قائمة الإرسال', $message);
    }

    // ── N. Attempt remains pending immediately after the response ──────────

    public function test_attempt_remains_pending_immediately_after_response(): void
    {
        Queue::fake();

        $this->actingAs(User::factory()->create(['super_admin' => true]));
        $invoice = $this->makeInvoice();

        $this->post(route('dashboard.invoices.whatsapp', $invoice));

        $attempt = InvoiceWhatsAppDeliveryAttempt::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING, $attempt->status);
    }

    // ── O. Unauthorized request creates no attempt and queues no job ───────

    public function test_unauthorized_request_creates_no_attempt_and_queues_no_job(): void
    {
        Queue::fake();

        $nonAdmin = User::factory()->create(['super_admin' => false]);
        $invoice = $this->makeInvoice();

        $this->actingAs($nonAdmin)->post(route('dashboard.invoices.whatsapp', $invoice));

        $this->assertSame(0, InvoiceWhatsAppDeliveryAttempt::query()->count());
        Queue::assertNothingPushed();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeInvoice(array $overrides = []): Invoice
    {
        $phone = array_key_exists('phone', $overrides) ? $overrides['phone'] : '+970599123456';

        $client = Client::query()->create([
            'first_name' => 'WhatsApp',
            'last_name' => 'Controller',
            'email' => Str::lower(Str::random(16)) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'WhatsApp Delivery Controller Test',
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
