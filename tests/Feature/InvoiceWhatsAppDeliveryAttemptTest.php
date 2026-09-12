<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceWhatsAppDeliveryAttempt;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 4 — schema/model-only tests for InvoiceWhatsAppDeliveryAttempt.
 *
 * These tests deliberately stop at persistence, casting, relationships, and
 * DB constraints. None of them assert or imply orchestration behavior:
 * "sent" is never treated as "delivered/read", and no test exercises or
 * expects automatic retry/resend for confirmed_failed or indeterminate
 * attempts -- that logic does not exist yet (a later phase).
 */
class InvoiceWhatsAppDeliveryAttemptTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    // ── 1. Persistence with the approved fields ─────────────────────────────

    public function test_attempt_can_be_persisted_with_the_approved_fields(): void
    {
        $invoice = $this->makeInvoice();
        $admin = $this->makeAdminUser();

        $attempt = $this->makeAttempt([
            'invoice_id' => $invoice->id,
            'attempt_uuid' => (string) Str::uuid(),
            'recipient_snapshot' => '+970 59 912 3456',
            'normalized_recipient' => '970599123456',
            'document_filename' => 'invoice-INV-TEST.pdf',
            'document_hash' => hash('sha256', 'fake-pdf-bytes'),
            'document_byte_length' => 12345,
            'provider' => 'meta',
            'provider_message_id' => 'wamid.TEST123',
            'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_SENT,
            'failure_code' => null,
            'failure_message' => null,
            'provider_response' => ['messages' => [['id' => 'wamid.TEST123']]],
            'requested_by_user_id' => $admin->id,
            'started_at' => now()->subSeconds(5),
            'finished_at' => now(),
        ]);

        $this->assertDatabaseHas('invoice_whatsapp_delivery_attempts', [
            'id' => $attempt->id,
            'invoice_id' => $invoice->id,
            'normalized_recipient' => '970599123456',
            'document_filename' => 'invoice-INV-TEST.pdf',
            'provider' => 'meta',
            'provider_message_id' => 'wamid.TEST123',
            'status' => 'sent',
            'requested_by_user_id' => $admin->id,
        ]);

        $fresh = $attempt->fresh();
        $this->assertSame('+970 59 912 3456', $fresh->recipient_snapshot);
        $this->assertSame(64, strlen($fresh->document_hash));
        $this->assertSame(12345, $fresh->document_byte_length);
    }

    // ── 2. Status constants ──────────────────────────────────────────────────

    public function test_status_constants_have_expected_values(): void
    {
        $this->assertSame('pending', InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING);
        $this->assertSame('processing', InvoiceWhatsAppDeliveryAttempt::STATUS_PROCESSING);
        $this->assertSame('sent', InvoiceWhatsAppDeliveryAttempt::STATUS_SENT);
        $this->assertSame('confirmed_failed', InvoiceWhatsAppDeliveryAttempt::STATUS_CONFIRMED_FAILED);
        $this->assertSame('indeterminate', InvoiceWhatsAppDeliveryAttempt::STATUS_INDETERMINATE);
    }

    // ── 3. provider_response casts to array ─────────────────────────────────

    public function test_provider_response_casts_to_array(): void
    {
        $attempt = $this->makeAttempt([
            'provider_response' => ['media_id' => 'MEDIA123', 'response' => ['messages' => [['id' => 'wamid.X']]]],
        ]);

        $fresh = $attempt->fresh();

        $this->assertIsArray($fresh->provider_response);
        $this->assertSame('MEDIA123', $fresh->provider_response['media_id']);
    }

    public function test_provider_response_is_nullable(): void
    {
        $attempt = $this->makeAttempt(['provider_response' => null]);

        $this->assertNull($attempt->fresh()->provider_response);
    }

    // ── 4. started_at / finished_at cast to datetime ────────────────────────

    public function test_started_at_and_finished_at_cast_to_carbon_instances(): void
    {
        $attempt = $this->makeAttempt([
            'started_at' => '2026-09-12 10:00:00',
            'finished_at' => '2026-09-12 10:00:07',
        ]);

        $fresh = $attempt->fresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->started_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $fresh->finished_at);
        $this->assertSame('2026-09-12 10:00:00', $fresh->started_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-12 10:00:07', $fresh->finished_at->format('Y-m-d H:i:s'));
    }

    public function test_started_at_and_finished_at_are_nullable(): void
    {
        $attempt = $this->makeAttempt(['started_at' => null, 'finished_at' => null]);

        $fresh = $attempt->fresh();
        $this->assertNull($fresh->started_at);
        $this->assertNull($fresh->finished_at);
    }

    // ── 5. invoice() relationship ────────────────────────────────────────────

    public function test_invoice_relationship_works(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->makeAttempt(['invoice_id' => $invoice->id]);

        $this->assertTrue($attempt->invoice->is($invoice));
        $this->assertTrue($invoice->whatsAppDeliveryAttempts->contains($attempt));
    }

    // ── 6. requestedBy() relationship ───────────────────────────────────────

    public function test_requested_by_relationship_works(): void
    {
        $admin = $this->makeAdminUser();
        $attempt = $this->makeAttempt(['requested_by_user_id' => $admin->id]);

        $this->assertTrue($attempt->requestedBy->is($admin));
    }

    // ── 7 & 8. invoice_id / requested_by_user_id can be null ────────────────

    public function test_invoice_id_can_be_null(): void
    {
        $attempt = $this->makeAttempt(['invoice_id' => null]);

        $this->assertNull($attempt->fresh()->invoice_id);
        $this->assertNull($attempt->fresh()->invoice);
    }

    public function test_requested_by_user_id_can_be_null(): void
    {
        $attempt = $this->makeAttempt(['requested_by_user_id' => null]);

        $this->assertNull($attempt->fresh()->requested_by_user_id);
        $this->assertNull($attempt->fresh()->requestedBy);
    }

    // ── 9. Deleting the invoice preserves the attempt, nulls invoice_id ─────

    public function test_deleting_invoice_preserves_attempt_and_nulls_invoice_id(): void
    {
        $invoice = $this->makeInvoice();
        $attempt = $this->makeAttempt(['invoice_id' => $invoice->id]);

        // Invoice uses SoftDeletes -- a plain delete() would only set
        // deleted_at (an UPDATE), which never triggers the FK's
        // nullOnDelete() action. forceDelete() issues the real DELETE this
        // test needs to exercise.
        $invoice->forceDelete();

        $this->assertDatabaseHas('invoice_whatsapp_delivery_attempts', [
            'id' => $attempt->id,
            'invoice_id' => null,
        ]);
        $this->assertNotNull(InvoiceWhatsAppDeliveryAttempt::find($attempt->id));
    }

    // ── 10. Deleting the requesting user preserves the attempt, nulls the FK ─

    public function test_deleting_requesting_user_preserves_attempt_and_nulls_requested_by(): void
    {
        $admin = $this->makeAdminUser();
        $attempt = $this->makeAttempt(['requested_by_user_id' => $admin->id]);

        $admin->delete();

        $this->assertDatabaseHas('invoice_whatsapp_delivery_attempts', [
            'id' => $attempt->id,
            'requested_by_user_id' => null,
        ]);
        $this->assertNotNull(InvoiceWhatsAppDeliveryAttempt::find($attempt->id));
    }

    // ── 11. Duplicate attempt_uuid is rejected by the DB ─────────────────────

    public function test_database_rejects_duplicate_attempt_uuid(): void
    {
        $uuid = (string) Str::uuid();
        $this->makeAttempt(['attempt_uuid' => $uuid]);

        try {
            $this->makeAttempt(['attempt_uuid' => $uuid]);
            $this->fail('The database accepted a duplicate attempt_uuid.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(1, InvoiceWhatsAppDeliveryAttempt::query()->where('attempt_uuid', $uuid)->count());
    }

    // ── 12. Indexes exist ────────────────────────────────────────────────────

    public function test_expected_indexes_exist_on_the_table(): void
    {
        $indexes = Schema::getIndexes('invoice_whatsapp_delivery_attempts');
        $names = array_column($indexes, 'name');

        $this->assertContains('invoice_whatsapp_delivery_attempts_status_idx', $names);
        $this->assertContains('invoice_whatsapp_delivery_attempts_claim_idx', $names);

        $uuidIndex = collect($indexes)->first(
            fn (array $index) => $index['columns'] === ['attempt_uuid'],
        );
        $this->assertNotNull($uuidIndex, 'Expected a unique index covering attempt_uuid.');
        $this->assertTrue($uuidIndex['unique']);

        $claimIndex = collect($indexes)->firstWhere('name', 'invoice_whatsapp_delivery_attempts_claim_idx');
        $this->assertSame(['invoice_id', 'normalized_recipient', 'status'], $claimIndex['columns']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function makeInvoice(): Invoice
    {
        $client = Client::query()->create([
            'first_name' => 'WhatsApp',
            'last_name' => 'Delivery',
            'email' => Str::lower(Str::random(16)) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'WhatsApp Delivery Test',
            'can_login' => true,
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

    private function makeAdminUser(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    private function makeAttempt(array $overrides = []): InvoiceWhatsAppDeliveryAttempt
    {
        return InvoiceWhatsAppDeliveryAttempt::query()->create(array_merge([
            'invoice_id' => null,
            'attempt_uuid' => (string) Str::uuid(),
            'recipient_snapshot' => '+970599123456',
            'normalized_recipient' => '970599123456',
            'document_filename' => 'invoice-INV-TEST.pdf',
            'document_hash' => hash('sha256', Str::random(32)),
            'document_byte_length' => 1024,
            'provider' => 'mock_whatsapp',
            'provider_message_id' => null,
            'status' => InvoiceWhatsAppDeliveryAttempt::STATUS_PENDING,
            'failure_code' => null,
            'failure_message' => null,
            'provider_response' => null,
            'requested_by_user_id' => null,
            'started_at' => null,
            'finished_at' => null,
        ], $overrides));
    }
}
