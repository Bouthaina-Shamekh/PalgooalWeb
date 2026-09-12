{{--
    Server-side PDF rendering of the same approved invoice document as
    print.blade.php. Renders the SAME shared content partial
    (partials/document.blade.php) so the data and sections shown can never
    drift from the browser print view -- only the CSS below differs, because
    the PDF engine (mPDF) does not support CSS flexbox/grid the way a browser
    does. Everywhere the browser stylesheet uses `display: flex`, this
    stylesheet reaches for the closest mPDF-safe equivalent (`display:
    table`/`table-cell`, `inline-block`, or plain block flow) to reproduce
    the same visual layout. The items table needs no rework at all: mPDF
    supports real HTML tables (including automatic <thead> repetition across
    pages) natively.

    No print toolbar, no @media print, no @page CSS here -- this document is
    never opened in a browser. Page size/margins/orientation are configured
    on the PHP side (App\Services\Billing\InvoicePdfService), which is the
    reliable way to control those in mPDF.
--}}
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاتورة {{ $invoice->number }} · Invoice {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            color: #1f2430;
            font-family: sans-serif;
            font-size: 13px;
            line-height: 1.55;
        }

        .doc-header {
            display: table;
            width: 100%;
            padding-bottom: 14px;
            border-bottom: 2px solid #240B36;
            margin-bottom: 18px;
        }
        .doc-header .brand { display: table-cell; width: 50%; vertical-align: top; }
        .doc-header .brand img { max-height: 60px; max-width: 180px; }
        .doc-header .doc-title { display: table-cell; width: 50%; vertical-align: top; text-align: left; }
        .doc-header .doc-title .title-ar { font-size: 23px; font-weight: 700; color: #240B36; }
        .doc-header .doc-title .title-en { font-size: 11px; color: #6b7280; text-transform: uppercase; }
        .doc-header .doc-title .invoice-number { font-size: 14px; margin-top: 4px; }
        .doc-header .doc-title .status-pill {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
        }
        .status-pill.status-paid      { background: #d1fae5; color: #065f46; }
        .status-pill.status-unpaid    { background: #fef3c7; color: #92400e; }
        .status-pill.status-draft     { background: #e5e7eb; color: #374151; }
        .status-pill.status-cancelled { background: #fee2e2; color: #991b1b; }

        .dates-row { margin-bottom: 18px; font-size: 12px; }
        .dates-row .date-item { display: inline-block; width: 23%; vertical-align: top; margin-bottom: 6px; }
        .dates-row .date-item .label { color: #6b7280; margin-bottom: 2px; }
        .dates-row .date-item .value { font-weight: 700; }

        .parties { display: table; width: 100%; border-spacing: 12px 0; margin-bottom: 20px; }
        .parties .party { display: table-cell; width: 50%; border: 1px solid #e5e7eb; padding: 10px 14px; vertical-align: top; }
        .parties .party h3 {
            margin: 0 0 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #ece9ef;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #240B36;
        }
        .parties .party .line { margin-bottom: 3px; }
        .parties .party .line.muted { color: #9ca3af; font-size: 11px; }

        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.items-table th {
            background: #f6f5f8;
            text-align: right;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #240B36;
            padding: 9px 10px;
            border-bottom: 2px solid #240B36;
        }
        table.items-table th.col-num { text-align: center; width: 32px; }
        table.items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #eef0f2;
            vertical-align: top;
            font-size: 12px;
        }
        table.items-table td.col-num { text-align: center; color: #9ca3af; }
        table.items-table .item-desc { font-weight: 700; }
        table.items-table .item-detail { color: #6b7280; font-size: 11px; }
        table.items-table .type-chip {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 10px;
        }
        table.items-table .num { white-space: nowrap; }

        .totals-block { width: 100%; margin-bottom: 20px; text-align: right; }
        .totals-block .totals-box { display: inline-block; width: 60%; min-width: 220px; text-align: right; }
        .totals-block .totals-row { display: table; width: 100%; padding: 5px 0; font-size: 12px; }
        .totals-block .totals-row .lbl { display: table-cell; color: #6b7280; text-align: right; }
        .totals-block .totals-row .num { display: table-cell; text-align: left; }
        .totals-block .totals-row .num.muted-val { color: #6b7280; font-weight: 400; }
        .totals-block .totals-row.grand {
            margin-top: 6px;
            padding-top: 10px;
            border-top: 2px solid #240B36;
            font-size: 15px;
            font-weight: 700;
            color: #240B36;
        }

        .payment-state {
            margin-bottom: 20px;
            padding: 9px 14px;
            border-radius: 6px;
            font-size: 12px;
        }
        .payment-state.is-paid   { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .payment-state.is-unpaid { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .doc-footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #6b7280;
            text-align: center;
        }
        .doc-footer .thanks { margin-bottom: 4px; font-weight: 700; color: #374151; }

        .ltr { direction: ltr; unicode-bidi: isolate; }
    </style>
</head>
<body>
    <div class="invoice-sheet">
        @include('dashboard.management.invoices.partials.document', ['invoice' => $invoice, 'forPdf' => true])
    </div>
</body>
</html>
