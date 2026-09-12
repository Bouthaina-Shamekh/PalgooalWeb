<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة {{ $invoice->number }} · Invoice {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            background: #e9ecef;
            color: #1f2430;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.55;
        }

        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 10px;
            background: #1f2430;
        }
        .print-toolbar button,
        .print-toolbar a {
            font-family: inherit;
            font-size: 13px;
            border: 0;
            border-radius: 6px;
            padding: 8px 18px;
            cursor: pointer;
            text-decoration: none;
        }
        .print-toolbar button { background: #3b82f6; color: #fff; }
        .print-toolbar a { background: #4b5563; color: #fff; }

        /*
         * Short-invoice A4 balance: .invoice-sheet is a column flexbox with a
         * minimum height equal to one full page. .doc-footer (its last child)
         * has margin-top:auto, so on a short invoice the footer is pushed down
         * to sit naturally near the bottom of the page instead of leaving a
         * large empty gap right after the totals/payment block. This is a
         * minimum, not a cap: an invoice whose content is taller than one page
         * simply overflows past it in normal document flow (no fixed height,
         * no absolute positioning), so the browser paginates it across
         * multiple pages exactly as it would without this rule, and the
         * footer is never forced to overlap earlier content.
         */
        .invoice-sheet {
            display: flex;
            flex-direction: column;
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            padding: 14mm 12mm;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
        }

        .doc-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 14px;
            border-bottom: 2px solid #240B36;
            margin-bottom: 18px;
        }
        .doc-header .brand { display: flex; align-items: center; gap: 12px; }
        .doc-header .brand img { max-height: 60px; max-width: 180px; object-fit: contain; }
        .doc-header .doc-title { text-align: left; }
        .doc-header .doc-title .title-ar { font-size: 23px; font-weight: 800; color: #240B36; letter-spacing: .01em; }
        .doc-header .doc-title .title-en { font-size: 11px; color: #6b7280; letter-spacing: .08em; text-transform: uppercase; }
        .doc-header .doc-title .invoice-number { font-family: 'Courier New', monospace; font-size: 14px; margin-top: 4px; }
        .doc-header .doc-title .status-pill {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pill.status-paid      { background: #d1fae5; color: #065f46; }
        .status-pill.status-unpaid    { background: #fef3c7; color: #92400e; }
        .status-pill.status-draft     { background: #e5e7eb; color: #374151; }
        .status-pill.status-cancelled { background: #fee2e2; color: #991b1b; }

        .dates-row {
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            margin-bottom: 18px;
            font-size: 12px;
        }
        .dates-row .date-item .label { color: #6b7280; margin-bottom: 2px; }
        .dates-row .date-item .value { font-weight: 600; }

        .parties {
            display: flex;
            gap: 24px;
            margin-bottom: 20px;
        }
        .parties .party { flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; }
        .parties .party h3 {
            margin: 0 0 8px;
            padding-bottom: 6px;
            border-bottom: 1px solid #ece9ef;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #240B36;
        }
        .parties .party .line { margin-bottom: 3px; }
        .parties .party .line.muted { color: #9ca3af; font-size: 11px; }

        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        table.items-table thead { display: table-header-group; }
        table.items-table th {
            background: #f6f5f8;
            text-align: right;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
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
        table.items-table tr { break-inside: avoid; page-break-inside: avoid; }
        table.items-table .item-desc { font-weight: 600; }
        table.items-table .item-detail { color: #6b7280; font-size: 11px; margin-top: 2px; }
        table.items-table .type-chip {
            display: inline-block;
            background: #f3f4f6;
            color: #374151;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 10px;
        }
        table.items-table .num { font-family: 'Courier New', monospace; white-space: nowrap; }

        .totals-block {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .totals-block .totals-box { width: 60%; min-width: 260px; }
        .totals-block .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 12px;
        }
        .totals-block .totals-row .lbl { color: #6b7280; }
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
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .payment-state.is-paid    { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .payment-state.is-unpaid  { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        .doc-footer {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #6b7280;
            text-align: center;
        }
        .doc-footer .thanks { margin-bottom: 4px; font-weight: 600; color: #374151; }

        .ltr { direction: ltr; unicode-bidi: isolate; display: inline-block; }

        @media print {
            .no-print { display: none !important; }
            html, body { background: #fff; }
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .invoice-sheet {
                width: auto;
                /* One page's content area: 297mm page height minus the 15mm
                   top + 15mm bottom @page margins below. Minimum only -- see
                   the .invoice-sheet flex comment above. */
                min-height: 267mm;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }

        @page {
            size: A4 portrait;
            margin: 15mm 12mm;
        }
    </style>
</head>
<body>

    {{-- On-screen-only print toolbar -- disappears entirely under @media print. --}}
    <div class="no-print print-toolbar">
        <button type="button" onclick="window.print()">طباعة</button>
        <a href="{{ route('dashboard.invoices.show', $invoice) }}">العودة إلى الفاتورة</a>
    </div>

    <main class="invoice-sheet">
        @include('dashboard.management.invoices.partials.document', ['invoice' => $invoice])
    </main>

</body>
</html>
