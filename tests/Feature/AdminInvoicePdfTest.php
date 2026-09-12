<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Services\Billing\InvoicePdfService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AdminInvoicePdfTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_authorized_admin_can_download_invoice_pdf(): void
    {
        $this->actingAs(User::factory()->create(['super_admin' => true]));

        $invoice = $this->makeInvoice();

        $response = $this->get(route('dashboard.invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);

        // Test the semantic contract (attachment + the exact safe filename
        // InvoicePdfService::filename() generates), not one specific quoting
        // serialization. A Content-Disposition filename is only required to
        // be wrapped in quotes when it contains characters outside the RFC
        // 2616 "token" charset (see Symfony's HeaderUtils::quote()); the
        // filename here is built exclusively from [A-Za-z0-9._-], which is
        // entirely token-safe, so Laravel/Symfony correctly leave it
        // unquoted (e.g. `attachment; filename=invoice-INV-....pdf`). Quoted
        // (`filename="...pdf"`) is equally valid and must keep passing too.
        $expectedFilename = app(InvoicePdfService::class)->filename($invoice);

        $this->assertStringStartsWith('attachment', $disposition);
        $this->assertMatchesRegularExpression(
            '/filename\*?=(?:UTF-8\'\')?"?'.preg_quote($expectedFilename, '/').'"?/i',
            $disposition
        );
    }

    public function test_non_admin_cannot_download_invoice_pdf(): void
    {
        $invoice = $this->makeInvoice();

        $nonAdmin = User::factory()->create(['super_admin' => false]);

        $this->actingAs($nonAdmin)
            ->get(route('dashboard.invoices.pdf', $invoice))
            ->assertForbidden();
    }

    public function test_pdf_generation_does_not_mutate_invoice_or_order_state(): void
    {
        $this->actingAs(User::factory()->create(['super_admin' => true]));

        $invoice = $this->makeInvoice();
        $order = $invoice->order;

        $beforeInvoice = [
            'status' => $invoice->status,
            'paid_date' => $invoice->paid_date,
            'subtotal_cents' => $invoice->subtotal_cents,
            'discount_cents' => $invoice->discount_cents,
            'tax_cents' => $invoice->tax_cents,
            'total_cents' => $invoice->total_cents,
        ];
        $beforeOrderStatus = $order->status;

        $this->get(route('dashboard.invoices.pdf', $invoice))->assertOk();

        $invoice->refresh();
        $order->refresh();

        $this->assertSame($beforeInvoice['status'], $invoice->status);
        $this->assertEquals($beforeInvoice['paid_date'], $invoice->paid_date);
        $this->assertSame($beforeInvoice['subtotal_cents'], $invoice->subtotal_cents);
        $this->assertSame($beforeInvoice['discount_cents'], $invoice->discount_cents);
        $this->assertSame($beforeInvoice['tax_cents'], $invoice->tax_cents);
        $this->assertSame($beforeInvoice['total_cents'], $invoice->total_cents);
        $this->assertSame($beforeOrderStatus, $order->status);
    }

    private function makeInvoice(): Invoice
    {
        $client = Client::query()->create([
            'first_name' => 'Pdf',
            'last_name' => 'Test',
            'email' => uniqid('pdf-test-', true) . '@example.test',
            'password' => bcrypt('secret-password'),
            'company_name' => 'Admin PDF Test',
        ]);

        $order = Order::query()->create([
            'client_id' => $client->id,
            'status' => Order::STATUS_PENDING,
            'type' => 'subscription',
        ]);

        return Invoice::query()->create([
            'client_id' => $client->id,
            'order_id' => $order->id,
            'number' => 'INV-' . strtoupper(uniqid()),
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
