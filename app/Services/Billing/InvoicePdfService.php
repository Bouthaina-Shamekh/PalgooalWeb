<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Renders the approved invoice document (the same shared content partial
 * used by the browser print view) to PDF bytes using mPDF.
 *
 * This service does NOT compute or touch any billing data -- it only reads
 * the already-authoritative stored Invoice/InvoiceItem/Client/Order fields
 * via the existing `dashboard.management.invoices.pdf` Blade view, exactly
 * as InvoiceController::print() does for the browser view. It must never be
 * used to settle invoices, mutate invoice/order state, or send anything.
 */
class InvoicePdfService
{
    /**
     * Render the given invoice to raw PDF bytes.
     */
    public function render(Invoice $invoice): string
    {
        $html = view('dashboard.management.invoices.pdf', ['invoice' => $invoice])->render();

        $mpdf = new Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4-P',
            'margin_left'       => 12,
            'margin_right'      => 12,
            'margin_top'        => 15,
            'margin_bottom'     => 15,
            'margin_header'     => 0,
            'margin_footer'     => 0,
            'default_font_size' => 13,
            'directionality'    => 'rtl',
            'autoScriptToLang'  => true,
            'autoLangToFont'    => true,
            'autoArabic'        => true,
            // No custom tempDir: mPDF manages its own default temp/font-cache
            // directory (created automatically, never a publicly served path).
            // Overriding it would require a directory this environment cannot
            // verify exists/writable ahead of time, so we rely on mPDF's own
            // well-tested default instead of introducing an unverified path.
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    /**
     * Build a safe download filename for the given invoice, e.g.
     * "invoice-INV-2026-0001.pdf". The invoice number is sanitized so it
     * can never inject path separators or unsafe characters into the
     * Content-Disposition header.
     */
    public function filename(Invoice $invoice): string
    {
        $safeNumber = Str::of((string) $invoice->number)
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '-')
            ->trim('-')
            ->value();

        if ($safeNumber === '') {
            $safeNumber = (string) $invoice->id;
        }

        return "invoice-{$safeNumber}.pdf";
    }
}
