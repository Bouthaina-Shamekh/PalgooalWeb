@php
    // Arabic status presentation labels -- mirrors the exact wording already
    // used across the client-facing invoice views (resources/views/client/invoices.blade.php,
    // resources/views/client/invoices/checkout.blade.php, resources/views/client/index.blade.php)
    // so the same status reads identically everywhere in the app. This app ships no lang/
    // files, so Laravel's __() would otherwise just echo the raw English status back.
    $statusLabels = [
        'draft'     => 'مسودة',
        'unpaid'    => 'غير مدفوعة',
        'paid'      => 'مدفوعة',
        'cancelled' => 'ملغاة',
    ];
    $statusLabel = $statusLabels[$invoice->status] ?? ucfirst($invoice->status);

    $typeLabels = config('invoices.item_types', []);

    $order = $invoice->order; // null when this invoice is not order-backed
    $winningAttempt = $invoice->paymentAttempt; // null for admin_manual settlement or unpaid invoices
@endphp
<x-dashboard-layout>
    {{-- Page-scoped grid override for the invoice/customer information card.
         public/assets/dashboard/css/style.css defines an UNLAYERED, unconditional
         .grid-cols-1 rule (grid-template-columns: repeat(1, ...)) that -- per the
         CSS Cascade Layers spec -- always wins over Tailwind's own layered
         responsive grid-cols utilities (@layer utilities), regardless of the
         @media match or specificity. That silently pinned this card's fields to a
         single column at every viewport width. This override is scoped to a
         dedicated class used only on this page, so it can't affect any other view. --}}
    <style>
        .invoice-info-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            column-gap: 1.5rem;
            row-gap: 0.75rem;
        }
        @media (min-width: 768px) {
            .invoice-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (min-width: 1024px) {
            .invoice-info-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            }
        }
    </style>
    {{-- [ page header ] start --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.invoices.index') }}">{{ __('Invoices') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">تفاصيل الفاتورة</li>
            </ul>
            <div class="page-header-title flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-medium text-gray-500 mb-1">تفاصيل الفاتورة</div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="mb-0 font-mono" dir="ltr">#{{ $invoice->number }}</h2>
                        <x-dashboard.status-badge :status="$invoice->status" :label="$statusLabel" />
                    </div>
                </div>
                <a href="{{ route('dashboard.invoices.index') }}"
                   class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-primary-500">
                    <i class="ti ti-arrow-right"></i>
                    العودة إلى قائمة الفواتير
                </a>
            </div>
        </div>
    </div>
    {{-- [ page header ] end --}}

    @if (session('ok'))
        <x-dashboard.alert type="success" class="mb-4">
            {{ session('ok') }}
        </x-dashboard.alert>
    @endif

    @if (session('error'))
        <x-dashboard.alert type="error" class="mb-4">
            {{ session('error') }}
        </x-dashboard.alert>
    @endif

    <div class="grid grid-cols-12 gap-x-6 gap-y-4 items-start">

        {{-- Action area -- reserved for future functionality, not implemented in this phase --}}
        <div class="col-span-12">
            <div class="card">
                <div class="card-body p-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs font-medium text-gray-500">
                        إجراءات الفاتورة
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('dashboard.invoices.print', $invoice) }}"
                           target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-secondary inline-flex items-center gap-1.5">
                            <i class="ti ti-printer"></i>
                            طباعة الفاتورة
                        </a>
                        <a href="{{ route('dashboard.invoices.pdf', $invoice) }}"
                           class="btn btn-sm btn-outline-secondary inline-flex items-center gap-1.5">
                            <i class="ti ti-file-download"></i>
                            تحميل PDF
                        </a>
                        <form method="POST" action="{{ route('dashboard.invoices.whatsapp', $invoice) }}" class="inline-flex">
                            @csrf
                            <button type="submit"
                                    onclick="this.disabled=true;"
                                    class="btn btn-sm btn-link-secondary inline-flex items-center gap-1.5">
                                <i class="ti ti-brand-whatsapp"></i>
                                إرسال عبر واتساب
                            </button>
                        </form>
                        <button type="button" disabled
                                title="سيتم تفعيل هذا الإجراء لاحقًا"
                                class="btn btn-sm btn-link-secondary opacity-60 cursor-not-allowed inline-flex items-center gap-1.5">
                            <i class="ti ti-mail"></i>
                            إرسال بالبريد
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Invoice information + customer information -- one compact card with
             two clearly separated logical sections, replacing the previous two
             independently-sized cards whose mismatched content height made the
             shorter one look oversized. --}}
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">معلومات الفاتورة</h5>
                </div>
                <div class="card-body p-4 md:p-5">
                    {{-- A. Invoice information --}}
                    <dl class="invoice-info-grid">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 mb-1">رقم الفاتورة</dt>
                            <dd class="font-mono text-sm text-gray-800" dir="ltr">{{ $invoice->number }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 mb-1">حالة الفاتورة</dt>
                            <dd><x-dashboard.status-badge :status="$invoice->status" :label="$statusLabel" /></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 mb-1">العملة</dt>
                            <dd class="text-sm text-gray-800">{{ $invoice->currency }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 mb-1">تاريخ الإنشاء</dt>
                            <dd class="text-sm text-gray-800">{{ $invoice->created_at?->format('Y-m-d') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 mb-1">تاريخ الاستحقاق</dt>
                            <dd class="text-sm text-gray-800">{{ $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '—' }}</dd>
                        </div>
                        @if ($invoice->paid_date)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 mb-1">تاريخ الدفع</dt>
                                <dd class="text-sm text-gray-800">{{ $invoice->paid_date->format('Y-m-d') }}</dd>
                            </div>
                        @endif
                        @if ($order)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 mb-1">رقم الطلب</dt>
                                <dd class="text-sm text-gray-800">
                                    <a href="{{ route('dashboard.orders.show', $order->id) }}" class="font-mono text-primary hover:underline" dir="ltr">
                                        {{ $order->order_number }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>

                    {{-- B. Customer information -- separated with a subtle divider and
                         section heading rather than a nested card. --}}
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <div class="text-xs font-semibold text-gray-500 mb-3 flex items-center gap-1.5">
                            <i class="ti ti-user text-gray-400"></i>
                            بيانات العميل
                        </div>
                        @if ($invoice->client)
                            <dl class="invoice-info-grid">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 mb-1">الاسم</dt>
                                    <dd class="text-sm text-gray-800">
                                        {{ trim(($invoice->client->first_name ?? '') . ' ' . ($invoice->client->last_name ?? '')) ?: '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 mb-1">البريد الإلكتروني</dt>
                                    <dd class="text-sm text-gray-800">{{ $invoice->client->email ?? '—' }}</dd>
                                </div>
                                @if (filled($invoice->client->phone))
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 mb-1">رقم الجوال</dt>
                                        <dd class="text-sm text-gray-800">{{ $invoice->client->phone }}</dd>
                                    </div>
                                @endif
                            </dl>
                        @else
                            <div class="text-sm text-gray-400">لا تتوفر بيانات عميل لهذه الفاتورة.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Invoice items + financial summary -- kept in a single cohesive card so the
             totals read as a direct continuation of the items table instead of a
             separate, unbalanced card. --}}
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">عناصر الفاتورة</h5>
                </div>
                <div class="card-body p-4 md:p-5">
                    @if ($invoice->items && $invoice->items->count())
                        <div class="table-responsive">
                            <table class="table text-end align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-start px-3 py-2.5">الوصف</th>
                                        <th class="px-3 py-2.5">النوع</th>
                                        <th class="px-3 py-2.5">الكمية</th>
                                        <th class="px-3 py-2.5">سعر الوحدة</th>
                                        <th class="px-3 py-2.5">الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($invoice->items as $item)
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
                                            <td class="text-start px-3 py-2.5">
                                                <div class="text-gray-800">{{ filled($item->description) ? $item->description : '—' }}</div>
                                                @if ($relatedDetail)
                                                    <div class="text-xs text-gray-500 mt-0.5">{{ $relatedDetail }}</div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700">
                                                    {{ $itemTypeLabel }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2.5">{{ $item->qty }}</td>
                                            <td class="whitespace-nowrap px-3 py-2.5">{{ number_format($item->unit_price_cents / 100, 2) }} {{ $invoice->currency }}</td>
                                            <td class="whitespace-nowrap font-medium text-gray-800 px-3 py-2.5">{{ number_format($item->total_cents / 100, 2) }} {{ $invoice->currency }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Financial summary --}}
                        <div class="mt-4 pt-4 border-t border-gray-100 flex justify-end">
                            <div class="w-full lg:w-2/5">
                                <div class="text-xs font-medium text-gray-500 mb-2">الملخص المالي</div>
                                <div class="flex items-center justify-between py-1 text-sm">
                                    <span class="text-gray-500">الإجمالي الفرعي</span>
                                    <span class="text-gray-800">{{ number_format(($invoice->subtotal_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1 text-sm">
                                    <span class="text-gray-500">الخصم</span>
                                    <span class="text-gray-800">{{ number_format(($invoice->discount_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1 text-sm">
                                    <span class="text-gray-500">الضريبة</span>
                                    <span class="text-gray-800">{{ number_format(($invoice->tax_cents ?? 0) / 100, 2) }} {{ $invoice->currency }}</span>
                                </div>
                                <div class="flex items-center justify-between pt-2.5 mt-1.5 border-t-2 border-gray-200">
                                    <span class="text-sm font-semibold text-gray-800">الإجمالي المستحق</span>
                                    <span class="text-xl font-bold text-primary">
                                        {{ number_format(($invoice->total_cents ?? 0) / 100, 2) }}
                                        <span class="text-xs font-medium text-gray-500">{{ $invoice->currency }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-sm text-gray-400">لا توجد عناصر متاحة في الفاتورة.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Paid state panel --}}
        @if ($invoice->status === 'paid')
            <div class="col-span-12">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-green-100 text-green-700">
                                <i class="ti ti-circle-check text-xl"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-green-800">تم تسجيل الدفع لهذه الفاتورة</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    @if ($invoice->paid_date)
                                        بتاريخ {{ $invoice->paid_date->format('Y-m-d') }}
                                    @else
                                        الفاتورة مسجّلة كمدفوعة في النظام.
                                    @endif
                                    @if ($winningAttempt && filled($winningAttempt->gateway))
                                        — طريقة الدفع: <span class="font-medium text-gray-700">{{ $winningAttempt->gateway }}</span>
                                    @endif
                                    @if ($winningAttempt && filled($winningAttempt->gateway_transaction_id))
                                        — مرجع العملية: <span class="font-mono text-gray-700">{{ $winningAttempt->gateway_transaction_id }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-dashboard-layout>
