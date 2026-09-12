<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AdminInvoicePrintTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_authorized_admin_can_view_print_invoice(): void
    {
        $this->actingAs(User::factory()->create(['super_admin' => true]));

        $invoice = $this->makeInvoice();

        $response = $this->get(route('dashboard.invoices.print', $invoice));

        $response->assertOk();
        $response->assertSee($invoice->number);
        $response->assertSee('1,000.00');
        $response->assertSee($invoice->currency);
    }

    public function test_non_admin_cannot_view_print_invoice(): void
    {
        $invoice = $this->makeInvoice();

        $nonAdmin = User::factory()->create(['super_admin' => false]);

        $this->actingAs($nonAdmin)
            ->get(route('dashboard.invoices.print', $invoice))
            ->assertForbidden();
    }

    /**
     * The document must never display a "Paid date" for an invoice that is
     * not actually paid, even if paid_date itself is populated (paid_date is
     * only ever written by InvoiceSettlementService::markPaid() together
     * with status='paid' -- status is the authoritative field, so the view
     * must gate on status, not on paid_date presence alone).
     */
    public function test_unpaid_invoice_print_does_not_show_paid_date(): void
    {
        $this->actingAs(User::factory()->create(['super_admin' => true]));

        $invoice = $this->makeInvoice([
            'status'    => 'unpaid',
            'paid_date' => now(),
        ]);

        $response = $this->get(route('dashboard.invoices.print', $invoice));

        $response->assertOk();
        $response->assertDontSee('Paid date');
    }

    public function test_paid_invoice_print_shows_paid_date(): void
    {
        $this->actingAs(User::factory()->create(['super_admin' => true]));

        $invoice = $this->makeInvoice([
            'status'    => 'paid',
            'paid_date' => now(),
        ]);

        $response = $this->get(route('dashboard.invoices.print', $invoice));

        $response->assertOk();
        $response->assertSee('Paid date');
        $response->assertSee($invoice->paid_date->format('Y-m-d'));
    }

    private function makeInvoice(array $overrides = []): Invoice
    {
        $client = Client::query()->create([
            'first_name' => 'Print',
            'last_name' => 'Test',
            'email' => uniqid('print-test-', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Admin Print Test',
        ]);

        return Invoice::query()->create(array_merge([
            'client_id' => $client->id,
            'order_id' => null,
            'number' => 'INV-' . strtoupper(uniqid()),
            'status' => 'unpaid',
            'subtotal_cents' => 100000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 100000,
            'currency' => 'USD',
            'payment_session_status' => Invoice::PAYMENT_SESSION_IDLE,
        ], $overrides));
    }
}
