{{--
    Shared invoice document content -- used by BOTH the browser print view
    (print.blade.php) and the server-side PDF view (pdf.blade.php), so the
    two presentations can never drift apart on what data/sections they show.

    This partial owns the DATA (the data-preparation block below -- written
    as "at" sign + "php", split here so Blade's own raw-PHP-block extractor
    does not misread this sentence as the start of a real code block) and
    the semantic markup/class names (doc-header, dates-row, parties, items-table,
    totals-block, payment-state, doc-footer). It deliberately owns NONE of
    the presentation: no <html>/<head>/<style>, no page-size setup, and no
    browser-only toolbar. Each host template supplies its own <style> block
    that gives these same class names the CSS their renderer can actually
    handle (flexbox in the browser; table/inline-block for the PDF engine,
    which does not support flexbox) -- see print.blade.php and pdf.blade.php
    for exactly what differs and why.
--}}
@php
    // Bilingual (Arabic-first) status labels for the printed/PDF document --
    // mirrors the same wording already used on the admin invoice show page
    // and the client-facing invoice views, plus an English counterpart
    // since this is a formal document that may be handed to non-Arabic-
    // reading parties. This app ships no lang/ files, so Laravel's __()
    // would otherwise just echo the raw English status back.
    $statusLabels = [
        'draft'     => ['ar' => 'مسودة',       'en' => 'Draft'],
        'unpaid'    => ['ar' => 'غير مدفوعة',   'en' => 'Unpaid'],
        'paid'      => ['ar' => 'مدفوعة',       'en' => 'Paid'],
        'cancelled' => ['ar' => 'ملغاة',        'en' => 'Cancelled'],
    ];
    $statusLabel   = $statusLabels[$invoice->status]['ar'] ?? ucfirst($invoice->status);
    $statusLabelEn = $statusLabels[$invoice->status]['en'] ?? ucfirst($invoice->status);

    $typeLabels     = config('invoices.item_types', []);
    $order          = $invoice->order;                 // null when this invoice is not order-backed
    $winningAttempt = $invoice->paymentAttempt;         // null for admin_manual settlement or unpaid invoices

    // Company identity -- read only from the project's existing GeneralSetting record.
    // $settings is normally shared to every view by AppServiceProvider's
    // view()->composer('*', ...), which fires for this partial too (each
    // @include(...) call makes a new View instance through the same Factory,
    // so the '*' composer runs for it independently of the host template).
    // The line below is a defensive guarantee, not a workaround: it makes this
    // partial correct on its own terms even if it is ever rendered through a
    // path that bypasses that composer (e.g. a future unit test that renders
    // this partial directly), instead of silently depending on global app
    // wiring it does not own. Nothing here is invented: a field that isn't
    // configured is simply omitted below.
    $settings ??= null;
    $companyName    = $settings?->resolved_site_title ?: config('app.name', 'PalGoals');
    $contactInfo    = $settings?->resolved_contact_info ?? [];
    $companyEmail   = filled($contactInfo['email'] ?? null) ? $contactInfo['email'] : null;
    $companyPhone   = filled($contactInfo['phone'] ?? null) ? $contactInfo['phone'] : null;
    $companyAddress = filled($contactInfo['address'] ?? null) ? $contactInfo['address'] : null;
    // No "website" field exists anywhere in GeneralSetting/contact_info in this project
    // (confirmed by inspection) -- intentionally left out rather than guessed from APP_URL,
    // which is a local/dev environment value, not a real published company website.

    // Logo -- same resolution convention already used by the admin dashboard's own
    // <head> partial (resources/views/dashboard/layouts/partials/head.blade.php),
    // preferring the Media-relation-aware resolved*Path() helpers (ADR-005 Wave 1)
    // and the public-facing site logo over the admin-panel-only logo for a
    // client-facing document, with the same existing static asset as final fallback.
    //
    // Resolved to an ABSOLUTE FILESYSTEM PATH (not a URL) when possible, so the
    // PDF renderer can read the image straight off disk instead of being asked
    // to fetch the running application over HTTP (STEP 9: prefer safe local
    // filesystem resolution over remote HTTP loading for the PDF).
    $logoPath     = $settings?->resolvedLogoPath() ?: $settings?->resolvedAdminLogoPath();
    $logoDiskPath = null;
    if (filled($logoPath) && !\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', '//'])) {
        $relative = ltrim(preg_replace('#^storage/#', '', $logoPath), '/');
        $candidate = storage_path('app/public/' . $relative);
        if (is_file($candidate)) {
            $logoDiskPath = $candidate;
        }
    }
    $logoHref = filled($logoPath)
        ? (\Illuminate\Support\Str::startsWith($logoPath, ['http://', 'https://', '//'])
            ? $logoPath
            : asset('storage/' . ltrim(preg_replace('#^storage/#', '', $logoPath), '/')))
        : asset('assets/tamplate/images/logo.svg');
    // PDF-only fallback + SVG guard. Proven by direct testing against mPDF's
    // own image processor (Mpdf\Image\ImageProcessor::getImage() /
    // Mpdf\Image\Svg::ImageSVG()): mPDF's built-in SVG-to-PDF converter does
    // not understand a <pattern> fill backed by an embedded raster <image>
    // (the exact shape of both assets/tamplate/images/logo.svg and most
    // real-world exported brand SVGs) -- it silently emits the vector path
    // with NO paint operator at all (`n` instead of `f`/color), so the shape
    // draws zero pixels with no error, warning, or exception anywhere. A
    // trivial single-color SVG (plain <rect fill="#hex">) renders fine, so
    // this is specifically about pattern/embedded-image SVGs, not SVG as a
    // format in general.
    //
    // logo-pdf.png is a real raster PNG extracted from that exact same
    // logo.svg's own embedded <image> data (so it is the identical brand
    // mark, not a redesign) -- mPDF renders plain PNG/JPEG natively via GD
    // with no such limitation. It is used ONLY on the PDF branch below; the
    // browser-print branch above ($logoHref) is completely untouched and
    // still serves the original logo.svg exactly as before.
    //
    // Any configured admin logo could theoretically be an SVG with the same
    // unsupported construct, so the disk candidate is only handed to mPDF
    // when it is NOT an .svg file; an SVG-only configured logo falls back to
    // this same safe PDF raster (browser print is unaffected either way,
    // since it never uses $logoDiskPath).
    $logoStaticFallbackForPdf = public_path('assets/tamplate/images/logo-pdf.png');
    $logoDiskPathForPdf = ($logoDiskPath && !\Illuminate\Support\Str::endsWith(strtolower($logoDiskPath), '.svg'))
        ? $logoDiskPath
        : null;
    // What the <img> src should be for THIS render: the PDF engine gets a
    // local disk path when one resolved (fastest, no HTTP round-trip, works
    // even if the app has no outbound network access); the browser view
    // always gets the normal public URL. $forPdf is passed in by pdf.blade.php.
    $logoSrc = (isset($forPdf) && $forPdf)
        ? ($logoDiskPathForPdf ?? (is_file($logoStaticFallbackForPdf) ? $logoStaticFallbackForPdf : $logoHref))
        : $logoHref;
@endphp

{{-- Header: logo (already carries the brand wordmark), document title, invoice number, status --}}
<div class="doc-header">
    <div class="brand">
        <img src="{{ $logoSrc }}" alt="{{ $companyName }}">
    </div>
    <div class="doc-title">
        <div class="title-ar">فاتورة</div>
        <div class="title-en">INVOICE</div>
        <div class="invoice-number ltr" dir="ltr">#{{ $invoice->number }}</div>
        <span class="status-pill status-{{ $invoice->status }}">{{ $statusLabel }} / {{ $statusLabelEn }}</span>
    </div>
</div>

<div class="dates-row">
    <div class="date-item">
        <div class="label">تاريخ الإصدار / Issue date</div>
        <div class="value">{{ $invoice->created_at?->format('Y-m-d') ?? '—' }}</div>
    </div>
    <div class="date-item">
        <div class="label">تاريخ الاستحقاق / Due date</div>
        <div class="value">{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '—' }}</div>
    </div>
    {{-- paid_date is a settlement-derived field (InvoiceSettlementService::markPaid()
         writes it only together with status='paid', atomically, in the one place that
         ever sets it to a non-null value). status is the authoritative field, so this
         document -- like the payment-state block below -- gates display on status, not
         on paid_date presence alone, and stays correct even if paid_date were ever
         populated without a genuinely paid status. --}}
    @if ($invoice->status === 'paid' && $invoice->paid_date)
        <div class="date-item">
            <div class="label">تاريخ الدفع / Paid date</div>
            <div class="value">{{ $invoice->paid_date->format('Y-m-d') }}</div>
        </div>
    @endif
    @if ($order)
        <div class="date-item">
            <div class="label">رقم الطلب / Order #</div>
            <div class="value ltr" dir="ltr">{{ $order->order_number }}</div>
        </div>
    @endif
</div>

{{-- Company + Bill To --}}
<div class="parties">
    <div class="party">
        <h3>من / From</h3>
        <div class="line" style="font-weight:600">{{ $companyName }}</div>
        @if ($companyAddress)
            <div class="line">{{ $companyAddress }}</div>
        @endif
        @if ($companyEmail)
            <div class="line ltr" dir="ltr">{{ $companyEmail }}</div>
        @endif
        @if ($companyPhone)
            <div class="line ltr" dir="ltr">{{ $companyPhone }}</div>
        @endif
    </div>
    <div class="party">
        <h3>إلى / Bill To</h3>
        @if ($invoice->client)
            <div class="line" style="font-weight:600">
                {{ trim(($invoice->client->first_name ?? '') . ' ' . ($invoice->client->last_name ?? '')) ?: '—' }}
            </div>
            @if (filled($invoice->client->email))
                <div class="line ltr" dir="ltr">{{ $invoice->client->email }}</div>
            @endif
            @if (filled($invoice->client->phone))
                <div class="line ltr" dir="ltr">{{ $invoice->client->phone }}</div>
            @endif
        @else
            <div class="line muted">لا تتوفر بيانات عميل لهذه الفاتورة.</div>
        @endif
    </div>
</div>

{{-- Items --}}
@if ($invoice->items && $invoice->items->count())
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th>الوصف / Description</th>
                <th>النوع / Type</th>
                <th>الكمية / Qty</th>
                <th>سعر الوحدة / Unit Price</th>
                <th>الإجمالي / Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $index => $item)
                @php
                    $itemTypeLabel = $typeLabels[$item->item_type] ?? ucfirst($item->item_type);
                    $relatedDetail = null;

                    if ($item->item_type === 'subscription' && $item->subscription) {
                        $planName = optional($item->subscription->plan)->name;
                        $subscriptionDomain = $item->subscription->domain_name;
                        if ($planName) {
                            $relatedDetail = 'خطة الاستضافة: ' . $planName;
                        } elseif ($subscriptionDomain) {
                            $relatedDetail = 'النطاق المرتبط: ' . $subscriptionDomain;
                        }
                    } elseif ($item->item_type === 'domain' && $item->domain) {
                        $relatedDetail = 'اسم النطاق: ' . $item->domain->domain_name;
                    }
                @endphp
                <tr>
                    <td class="col-num">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-desc">{{ filled($item->description) ? $item->description : '—' }}</div>
                        @if ($relatedDetail)
                            <div class="item-detail ltr" dir="ltr">{{ $relatedDetail }}</div>
                        @endif
                    </td>
                    <td><span class="type-chip">{{ $itemTypeLabel }}</span></td>
                    <td>{{ $item->qty }}</td>
                    <td class="num">{{ number_format($item->unit_price_cents / 100, 2) }} {{ $invoice->currency }}</td>
                    <td class="num" style="font-weight:600">{{ number_format($item->total_cents / 100, 2) }} {{ $invoice->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="line muted" style="margin-bottom:18px">لا توجد عناصر متاحة في الفاتورة.</div>
@endif

{{-- Financial summary -- stored fields only, no recalculation --}}
<div class="totals-block">
    <div class="totals-box">
        <div class="totals-row">
            <span class="lbl">الإجمالي الفرعي / Subtotal</span>
            <span class="num muted-val">{{ number_format(($invoice->subtotal_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}</span>
        </div>
        <div class="totals-row">
            <span class="lbl">الخصم / Discount</span>
            <span class="num muted-val">{{ number_format(($invoice->discount_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}</span>
        </div>
        <div class="totals-row">
            <span class="lbl">الضريبة / Tax</span>
            <span class="num muted-val">{{ number_format(($invoice->tax_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}</span>
        </div>
        <div class="totals-row grand">
            <span>الإجمالي المستحق / Total Amount</span>
            <span class="num">{{ number_format(($invoice->total_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}</span>
        </div>
    </div>
</div>

{{-- Payment state --}}
@if ($invoice->status === 'paid')
    <div class="payment-state is-paid">
        <strong>تم الدفع / Paid</strong>
        @if ($invoice->paid_date)
            — بتاريخ {{ $invoice->paid_date->format('Y-m-d') }}
        @endif
    </div>
@else
    <div class="payment-state is-unpaid">
        <strong>{{ $statusLabel }} / {{ $statusLabelEn }}</strong>
    </div>
@endif

{{-- Footer -- only fields backed by existing project data --}}
<div class="doc-footer">
    <div class="thanks">شكراً لتعاملكم معنا / Thank you for your business</div>
    <div>
        {{ $companyName }}
        @if ($companyEmail)
            · <span class="ltr" dir="ltr">{{ $companyEmail }}</span>
        @endif
        @if ($companyPhone)
            · <span class="ltr" dir="ltr">{{ $companyPhone }}</span>
        @endif
    </div>
</div>
