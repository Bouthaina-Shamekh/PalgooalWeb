<x-dashboard-layout :title="t('dashboard.Dashboard', 'لوحة التحكم')">

    {{-- ══════════════════════════════════════════════
         PAGE HEADER
    ══════════════════════════════════════════════ --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item active" aria-current="page">
                    {{ t('dashboard.Home', 'الرئيسية') }}
                </li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Dashboard', 'لوحة التحكم') }}</h2>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         KPI STAT CARDS
    ══════════════════════════════════════════════ --}}
    <div class="grid grid-cols-12 gap-4 mb-6">

        {{-- Clients --}}
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="card h-full" style="border-top: 3px solid #4f46e5;">
                <div class="card-body flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">{{ t('dashboard.Total_Clients', 'إجمالي العملاء') }}</p>
                            <p class="text-3xl font-black text-gray-900 mb-0 leading-none">{{ number_format($stats['clients_total']) }}</p>
                        </div>
                        <div class="flex-shrink-0 flex items-center justify-center rounded-xl text-indigo-600" style="width:48px;height:48px;background:#eef2ff;">
                            <i class="ti ti-users" style="font-size:22px;"></i>
                        </div>
                    </div>
                    @if($stats['clients_this_month'] > 0)
                        <p class="text-xs text-green-600 flex items-center gap-1 mb-0">
                            <i class="ti ti-trending-up"></i>
                            +{{ $stats['clients_this_month'] }} {{ t('dashboard.This_Month', 'هذا الشهر') }}
                        </p>
                    @else
                        <p class="text-xs text-gray-400 mb-0">{{ t('dashboard.No_New_This_Month', 'لا جديد هذا الشهر') }}</p>
                    @endif
                </div>
                <div class="card-footer bg-transparent pt-0 pb-3 px-4">
                    <a href="{{ route('dashboard.clients') }}" class="btn btn-light btn-sm w-full text-sm">
                        {{ t('dashboard.View_All', 'عرض الكل') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Active Subscriptions --}}
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="card h-full" style="border-top: 3px solid #16a34a;">
                <div class="card-body flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">{{ t('dashboard.Active_Subscriptions', 'الاشتراكات النشطة') }}</p>
                            <p class="text-3xl font-black text-gray-900 mb-0 leading-none">{{ number_format($stats['subs_active']) }}</p>
                        </div>
                        <div class="flex-shrink-0 flex items-center justify-center rounded-xl text-green-600" style="width:48px;height:48px;background:#dcfce7;">
                            <i class="ti ti-circle-check" style="font-size:22px;"></i>
                        </div>
                    </div>
                    @if($stats['subs_pending'] > 0)
                        <p class="text-xs text-amber-600 flex items-center gap-1 mb-0">
                            <i class="ti ti-clock"></i>
                            {{ $stats['subs_pending'] }} {{ t('dashboard.Pending', 'قيد الانتظار') }}
                        </p>
                    @else
                        <p class="text-xs text-gray-400 mb-0">
                            {{ t('dashboard.Total_Subs', 'الإجمالي') }}: {{ $stats['subs_total'] }}
                        </p>
                    @endif
                </div>
                <div class="card-footer bg-transparent pt-0 pb-3 px-4">
                    <a href="{{ route('dashboard.subscriptions.index') }}" class="btn btn-light btn-sm w-full text-sm">
                        {{ t('dashboard.View_All', 'عرض الكل') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="card h-full" style="border-top: 3px solid #d97706;">
                <div class="card-body flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">{{ t('dashboard.Paid_Revenue', 'الإيرادات المحصّلة') }}</p>
                            <p class="text-3xl font-black text-gray-900 mb-0 leading-none">${{ number_format($stats['revenue_paid'], 0) }}</p>
                        </div>
                        <div class="flex-shrink-0 flex items-center justify-center rounded-xl text-amber-600" style="width:48px;height:48px;background:#fef3c7;">
                            <i class="ti ti-coin-dollar" style="font-size:22px;"></i>
                        </div>
                    </div>
                    @if($stats['revenue_unpaid'] > 0)
                        <p class="text-xs text-red-500 flex items-center gap-1 mb-0">
                            <i class="ti ti-alert-circle"></i>
                            ${{ number_format($stats['revenue_unpaid'], 0) }} {{ t('dashboard.Unpaid', 'غير مدفوع') }}
                        </p>
                    @else
                        <p class="text-xs text-green-600 flex items-center gap-1 mb-0">
                            <i class="ti ti-circle-check"></i>
                            {{ t('dashboard.All_Paid', 'كل الفواتير مسدّدة') }}
                        </p>
                    @endif
                </div>
                <div class="card-footer bg-transparent pt-0 pb-3 px-4">
                    <a href="#" class="btn btn-light btn-sm w-full text-sm">
                        {{ t('dashboard.View_Invoices', 'عرض الفواتير') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Plans & Templates --}}
        <div class="col-span-12 sm:col-span-6 xl:col-span-3">
            <div class="card h-full" style="border-top: 3px solid #0891b2;">
                <div class="card-body flex flex-col gap-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <p class="text-sm text-gray-500 mb-1">{{ t('dashboard.Templates_And_Plans', 'القوالب والباقات') }}</p>
                            <p class="text-3xl font-black text-gray-900 mb-0 leading-none">{{ number_format($stats['templates_total']) }}</p>
                        </div>
                        <div class="flex-shrink-0 flex items-center justify-center rounded-xl text-cyan-600" style="width:48px;height:48px;background:#cffafe;">
                            <i class="ti ti-template" style="font-size:22px;"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 flex items-center gap-1 mb-0">
                        <i class="ti ti-package"></i>
                        {{ $stats['plans_active'] }} {{ t('dashboard.Active_Plans', 'باقة نشطة') }}
                    </p>
                </div>
                <div class="card-footer bg-transparent pt-0 pb-3 px-4">
                    <a href="{{ route('dashboard.templates.index') }}" class="btn btn-light btn-sm w-full text-sm">
                        {{ t('dashboard.View_Templates', 'عرض القوالب') }}
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════
         RECENT DATA
    ══════════════════════════════════════════════ --}}
    <div class="grid grid-cols-12 gap-4 mb-6">

        {{-- Recent Subscriptions --}}
        <div class="col-span-12 xl:col-span-7">
            <div class="card table-card h-full">
                <div class="card-header flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-world text-primary text-lg"></i>
                        <h6 class="mb-0 font-bold text-sm">{{ t('dashboard.Recent_Subscriptions', 'آخر الاشتراكات') }}</h6>
                    </div>
                    <a href="{{ route('dashboard.subscriptions.index') }}" class="btn btn-light btn-sm text-xs">
                        {{ t('dashboard.View_All', 'عرض الكل') }}
                    </a>
                </div>

                @if($recentSubscriptions->isEmpty())
                    <div class="card-body flex flex-col items-center justify-center py-16 text-gray-400">
                        <i class="ti ti-world-off" style="font-size:48px;opacity:.3;"></i>
                        <p class="mt-3 text-sm">{{ t('dashboard.No_Subscriptions_Yet', 'لا توجد اشتراكات بعد') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-right ps-4 text-xs font-semibold text-gray-500">{{ t('dashboard.Client', 'العميل') }}</th>
                                    <th class="text-right text-xs font-semibold text-gray-500">{{ t('dashboard.Plan', 'الباقة') }}</th>
                                    <th class="text-right text-xs font-semibold text-gray-500">{{ t('dashboard.Status', 'الحالة') }}</th>
                                    <th class="text-right pe-4 text-xs font-semibold text-gray-500">{{ t('dashboard.Date', 'التاريخ') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentSubscriptions as $sub)
                                    @php
                                        $badgeClass = match($sub->status) {
                                            'active'    => 'bg-green-100 text-green-700',
                                            'pending'   => 'bg-amber-100 text-amber-700',
                                            'suspended' => 'bg-red-100 text-red-700',
                                            default     => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <p class="font-semibold text-gray-800 text-sm mb-0">
                                                {{ $sub->client?->first_name }} {{ $sub->client?->last_name }}
                                            </p>
                                            <p class="text-gray-400 text-xs mb-0">{{ $sub->client?->email }}</p>
                                        </td>
                                        <td>
                                            <p class="text-gray-700 text-sm mb-0">{{ $sub->plan?->name ?? '—' }}</p>
                                            @if($sub->price)
                                                <p class="text-gray-400 text-xs mb-0">${{ number_format($sub->price, 2) }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                                {{ t('dashboard.Status_' . ucfirst($sub->status), $sub->status) }}
                                            </span>
                                        </td>
                                        <td class="pe-4 text-gray-400 text-xs">
                                            {{ $sub->created_at?->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Clients --}}
        <div class="col-span-12 xl:col-span-5">
            <div class="card h-full">
                <div class="card-header flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-users text-primary text-lg"></i>
                        <h6 class="mb-0 font-bold text-sm">{{ t('dashboard.Recent_Clients', 'آخر العملاء') }}</h6>
                    </div>
                    <a href="{{ route('dashboard.clients') }}" class="btn btn-light btn-sm text-xs">
                        {{ t('dashboard.View_All', 'عرض الكل') }}
                    </a>
                </div>

                @if($recentClients->isEmpty())
                    <div class="card-body flex flex-col items-center justify-center py-16 text-gray-400">
                        <i class="ti ti-user-off" style="font-size:48px;opacity:.3;"></i>
                        <p class="mt-3 text-sm">{{ t('dashboard.No_Clients_Yet', 'لا يوجد عملاء بعد') }}</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100">
                        @foreach($recentClients as $client)
                            <div class="flex items-center gap-3 px-4 py-3">
                                {{-- Avatar initial --}}
                                <div class="flex-shrink-0 flex items-center justify-center rounded-full font-bold text-white text-sm"
                                     style="width:36px;height:36px;background:#4f46e5;line-height:1;">
                                    {{ strtoupper(mb_substr($client->first_name ?? 'U', 0, 1)) }}
                                </div>
                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-800 text-sm mb-0 truncate">
                                        {{ $client->first_name }} {{ $client->last_name }}
                                    </p>
                                    <p class="text-gray-400 text-xs mb-0 truncate">{{ $client->email }}</p>
                                </div>
                                {{-- Status + time --}}
                                <div class="flex-shrink-0 text-right">
                                    @if($client->status === 'active')
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700">
                                            {{ t('dashboard.Status_Active', 'نشط') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500">
                                            {{ t('dashboard.Client_Inactive', 'غير نشط') }}
                                        </span>
                                    @endif
                                    <p class="text-gray-400 text-xs mt-1 mb-0">{{ $client->created_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ══════════════════════════════════════════════
         QUICK ACTIONS
    ══════════════════════════════════════════════ --}}
    <div class="card">
        <div class="card-header flex items-center gap-2">
            <i class="ti ti-bolt text-primary text-lg"></i>
            <h6 class="mb-0 font-bold text-sm">{{ t('dashboard.Quick_Actions', 'إجراءات سريعة') }}</h6>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-12 gap-3">

                @php
                    $actions = [
                        ['route' => route('dashboard.clients.create'),          'icon' => 'ti-user-plus',    'color' => '#4f46e5', 'bg' => '#eef2ff', 'label' => t('dashboard.Add_Client', 'إضافة عميل')],
                        ['route' => route('dashboard.subscriptions.create'),    'icon' => 'ti-world-plus',   'color' => '#16a34a', 'bg' => '#dcfce7', 'label' => t('dashboard.Add_Subscription', 'إضافة اشتراك')],
                        ['route' => route('dashboard.templates.index'),         'icon' => 'ti-template',     'color' => '#0891b2', 'bg' => '#cffafe', 'label' => t('dashboard.Templates', 'القوالب')],
                        ['route' => route('dashboard.plans.index'),             'icon' => 'ti-package',      'color' => '#d97706', 'bg' => '#fef3c7', 'label' => t('dashboard.Plans', 'الباقات')],
                        ['route' => route('dashboard.testimonials.index'),      'icon' => 'ti-star',         'color' => '#7c3aed', 'bg' => '#ede9fe', 'label' => t('dashboard.Testimonials', 'الشهادات')],
                        ['route' => route('dashboard.portfolios.index'),        'icon' => 'ti-photo',        'color' => '#0e7490', 'bg' => '#e0f2fe', 'label' => t('dashboard.Portfolios', 'المحافظ')],
                    ];
                @endphp

                @foreach($actions as $action)
                    <div class="col-span-6 sm:col-span-4 lg:col-span-2">
                        <a href="{{ $action['route'] }}"
                           class="flex flex-col items-center gap-2 p-4 rounded-xl border border-dashed border-gray-200 text-center no-underline text-gray-700 hover:border-gray-400 hover:bg-gray-50 transition-all"
                           style="text-decoration:none;">
                            <div class="flex items-center justify-center rounded-xl" style="width:44px;height:44px;background:{{ $action['bg'] }};color:{{ $action['color'] }};">
                                <i class="ti {{ $action['icon'] }}" style="font-size:20px;"></i>
                            </div>
                            <span class="text-xs font-semibold text-gray-700 leading-snug">{{ $action['label'] }}</span>
                        </a>
                    </div>
                @endforeach

            </div>
        </div>
    </div>

</x-dashboard-layout>
