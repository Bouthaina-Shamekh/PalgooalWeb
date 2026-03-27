<x-client-layout>
    @php
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: request()->getScheme();
        $hasFilters = $search !== '' || $status !== 'all';

        $statusMeta = static fn (?string $value) => match ((string) $value) {
            'active' => ['label' => 'نشط', 'class' => 'bg-emerald-500/10 text-emerald-600'],
            'pending' => ['label' => 'معلق', 'class' => 'bg-amber-500/10 text-amber-600'],
            'suspended' => ['label' => 'موقوف', 'class' => 'bg-slate-500/10 text-slate-600'],
            'cancelled' => ['label' => 'ملغي', 'class' => 'bg-red-500/10 text-red-600'],
            default => ['label' => $value ? ucfirst($value) : 'غير محدد', 'class' => 'bg-gray-200 text-gray-700'],
        };

        $provisioningMeta = static fn (?string $value) => match ((string) $value) {
            'active' => ['label' => 'جاهز', 'class' => 'bg-emerald-500/10 text-emerald-600'],
            'pending' => ['label' => 'بانتظار البدء', 'class' => 'bg-amber-500/10 text-amber-600'],
            'provisioning' => ['label' => 'قيد التجهيز', 'class' => 'bg-sky-500/10 text-sky-600'],
            'failed' => ['label' => 'فشل التجهيز', 'class' => 'bg-red-500/10 text-red-600'],
            default => ['label' => $value ? ucfirst($value) : 'غير معروف', 'class' => 'bg-gray-200 text-gray-700'],
        };
    @endphp

    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('client.home') }}">الرئيسية</a></li>
                <li class="breadcrumb-item" aria-current="page">الاشتراكات</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">إدارة اشتراكاتك</h2>
            </div>
            <p class="mt-2 text-sm text-gray-500">
                تابع حالة التجهيز، تواريخ التجديد، والوصول السريع لكل موقع من مكان واحد.
            </p>
        </div>
    </div>

    @if (session('ok'))
        <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif

    @if (session('connection_result'))
        <div class="alert alert-info mb-4">{!! session('connection_result') !!}</div>
    @endif

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card h-full">
                <div class="card-body">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="mb-2 text-sm text-gray-500">إجمالي الاشتراكات</p>
                            <h3 class="mb-0">{{ number_format($subscriptionStats['total'] ?? 0) }}</h3>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <i class="ti ti-stack-2 text-xl leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card h-full">
                <div class="card-body">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="mb-2 text-sm text-gray-500">الاشتراكات النشطة</p>
                            <h3 class="mb-0">{{ number_format($subscriptionStats['active'] ?? 0) }}</h3>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600">
                            <i class="ti ti-circle-check text-xl leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card h-full">
                <div class="card-body">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="mb-2 text-sm text-gray-500">بانتظار التجهيز</p>
                            <h3 class="mb-0">{{ number_format($subscriptionStats['provisioning'] ?? 0) }}</h3>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-sky-500/10 text-sky-600">
                            <i class="ti ti-loader-2 text-xl leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card h-full">
                <div class="card-body">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="mb-2 text-sm text-gray-500">تجديد خلال 7 أيام</p>
                            <h3 class="mb-0">{{ number_format($subscriptionStats['renewing_soon'] ?? 0) }}</h3>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-amber-500/10 text-amber-600">
                            <i class="ti ti-calendar-time text-xl leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card">
                <div class="card-body space-y-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h5 class="mb-1">ابحث وصفِّ النتائج</h5>
                            <p class="text-sm text-gray-500">
                                ابحث بالدومين أو الخطة أو اسم القالب، ثم صفِّ حسب حالة الاشتراك.
                            </p>
                        </div>
                        @if ($hasFilters)
                            <a href="{{ route('client.subscriptions') }}"
                                class="btn btn-sm btn-light-danger inline-flex items-center gap-2">
                                <i class="ti ti-x text-base leading-none"></i>
                                مسح الفلاتر
                            </a>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('client.subscriptions') }}" class="grid grid-cols-12 gap-3">
                        <div class="col-span-12 lg:col-span-8">
                            <label for="subscriptions-search" class="mb-2 block text-sm font-medium text-gray-700">
                                البحث
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 right-4 inline-flex items-center text-gray-400">
                                    <i class="ti ti-search text-lg leading-none"></i>
                                </span>
                                <input id="subscriptions-search" name="q" value="{{ $search }}"
                                    placeholder="مثال: mysite.com أو اسم الخطة"
                                    class="w-full rounded-xl border border-gray-200 bg-white py-3 pr-11 pl-4 text-sm outline-none transition focus:border-primary/40 focus:ring-4 focus:ring-primary/10" />
                            </div>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label for="subscriptions-status" class="mb-2 block text-sm font-medium text-gray-700">
                                الحالة
                            </label>
                            <div class="flex gap-2">
                                <select id="subscriptions-status" name="status"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-primary/40 focus:ring-4 focus:ring-primary/10">
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary whitespace-nowrap">
                                    تطبيق
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($statusOptions as $value => $label)
                            <a href="{{ route('client.subscriptions', array_filter(['q' => $search ?: null, 'status' => $value !== 'all' ? $value : null])) }}"
                                class="inline-flex items-center rounded-full border px-3 py-1.5 text-sm transition {{ $status === $value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-gray-200 bg-white text-gray-600 hover:border-primary/30 hover:text-primary' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            @if ($subscriptions->count() === 0 && ! $hasFilters)
                <div class="card">
                    <div class="card-body py-16 text-center">
                        <span class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <i class="ti ti-package text-3xl leading-none"></i>
                        </span>
                        <h4 class="mb-2">لا توجد اشتراكات بعد</h4>
                        <p class="mx-auto max-w-2xl text-sm text-gray-500">
                            عندما تُنشئ أول اشتراك سيظهر هنا مع حالة التجهيز، الدومين المرتبط، وروابط الوصول السريع.
                        </p>
                    </div>
                </div>
            @elseif ($subscriptions->count() === 0)
                <div class="card">
                    <div class="card-body py-16 text-center">
                        <span class="mx-auto mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 text-amber-600">
                            <i class="ti ti-search-off text-3xl leading-none"></i>
                        </span>
                        <h4 class="mb-2">لا توجد نتائج مطابقة</h4>
                        <p class="mx-auto max-w-2xl text-sm text-gray-500">
                            جرّب تغيير كلمة البحث أو إزالة التصفية الحالية لعرض كل اشتراكاتك.
                        </p>
                        <div class="mt-5">
                            <a href="{{ route('client.subscriptions') }}" class="btn btn-light-primary">عرض جميع الاشتراكات</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-gray-500">
                        عرض
                        <span class="font-semibold text-gray-700">{{ $subscriptions->firstItem() }}</span>
                        إلى
                        <span class="font-semibold text-gray-700">{{ $subscriptions->lastItem() }}</span>
                        من
                        <span class="font-semibold text-gray-700">{{ $subscriptions->total() }}</span>
                        اشتراك
                    </p>
                    @if ($hasFilters)
                        <p class="text-sm text-primary">
                            النتائج المعروضة مفلترة حسب المدخلات الحالية.
                        </p>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 lg:hidden">
                    @foreach ($subscriptions as $sub)
                        @php
                            $statusBadge = $statusMeta($sub->status);
                            $provisioningBadge = $provisioningMeta($sub->provisioning_status);
                            $planName = $sub->plan?->translation()?->name ?? $sub->plan?->name ?? 'بدون خطة';
                            $templateName = $sub->template?->translation()?->name ?? $sub->template?->name;
                            $siteUrl = $sub->domain_name
                                ? (\Illuminate\Support\Str::startsWith($sub->domain_name, ['http://', 'https://'])
                                    ? $sub->domain_name
                                    : $scheme . '://' . ltrim($sub->domain_name, '/'))
                                : null;
                            $nextDueLabel = $sub->next_due_date?->format('Y-m-d') ?? 'غير محدد';
                        @endphp

                        <div class="card overflow-hidden">
                            <div class="card-body space-y-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="mb-1 flex items-center gap-2">
                                            <h5 class="mb-0">#{{ $sub->id }}</h5>
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge['class'] }}">
                                                {{ $statusBadge['label'] }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500">{{ $planName }}</p>
                                    </div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $provisioningBadge['class'] }}">
                                        {{ $provisioningBadge['label'] }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div class="rounded-xl bg-gray-50 px-3 py-3">
                                        <p class="mb-1 text-xs text-gray-400">الدومين</p>
                                        <p class="font-medium break-all text-gray-700">{{ $sub->domain_name ?: 'سيُولّد تلقائياً' }}</p>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 px-3 py-3">
                                        <p class="mb-1 text-xs text-gray-400">التجديد القادم</p>
                                        <p class="font-medium text-gray-700">{{ $nextDueLabel }}</p>
                                    </div>
                                </div>

                                @if ($templateName)
                                    <div class="rounded-xl border border-dashed border-gray-200 px-3 py-3 text-sm">
                                        <p class="mb-1 text-xs text-gray-400">القالب المختار</p>
                                        <p class="font-medium text-gray-700">{{ $templateName }}</p>
                                    </div>
                                @endif

                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('client.subscriptions.show', $sub) }}"
                                        class="btn btn-light-primary inline-flex items-center gap-2">
                                        <i class="ti ti-settings text-base leading-none"></i>
                                        إدارة
                                    </a>
                                    @if ($siteUrl)
                                        <a href="{{ $siteUrl }}" target="_blank" rel="noopener"
                                            class="btn btn-light-success inline-flex items-center gap-2">
                                            <i class="ti ti-external-link text-base leading-none"></i>
                                            فتح الموقع
                                        </a>
                                    @endif
                                    @if (app()->environment('local'))
                                        <a href="{{ route('tenant.preview', $sub) }}" target="_blank"
                                            class="btn btn-light-secondary inline-flex items-center gap-2">
                                            <i class="ti ti-eye text-base leading-none"></i>
                                            معاينة محلية
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="card hidden lg:block">
                    <div class="card-body pt-3">
                        <div class="table-responsive">
                            <table class="table table-hover w-full align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-right">الاشتراك</th>
                                        <th class="text-right">الدومين</th>
                                        <th class="text-right">الحالة</th>
                                        <th class="text-right">التجهيز</th>
                                        <th class="text-right">التجديد القادم</th>
                                        <th class="text-right">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subscriptions as $sub)
                                        @php
                                            $statusBadge = $statusMeta($sub->status);
                                            $provisioningBadge = $provisioningMeta($sub->provisioning_status);
                                            $planName = $sub->plan?->translation()?->name ?? $sub->plan?->name ?? 'بدون خطة';
                                            $templateName = $sub->template?->translation()?->name ?? $sub->template?->name;
                                            $siteUrl = $sub->domain_name
                                                ? (\Illuminate\Support\Str::startsWith($sub->domain_name, ['http://', 'https://'])
                                                    ? $sub->domain_name
                                                    : $scheme . '://' . ltrim($sub->domain_name, '/'))
                                                : null;
                                            $isOverdue = $sub->next_due_date && $sub->next_due_date->lt(now()->startOfDay());
                                            $isDueSoon = $sub->next_due_date && ! $isOverdue && $sub->next_due_date->diffInDays(now()->startOfDay()) <= 7;
                                        @endphp

                                        <tr>
                                            <td>
                                                <div class="min-w-[15rem]">
                                                    <div class="mb-1 flex items-center gap-2">
                                                        <span class="font-semibold text-gray-800">#{{ $sub->id }}</span>
                                                        <span class="text-sm text-gray-500">{{ $planName }}</span>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        @if ($templateName)
                                                            <span>القالب: {{ $templateName }}</span>
                                                        @else
                                                            <span>بدون قالب مخصص</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="min-w-[14rem]">
                                                    <div class="font-medium text-gray-800 break-all">
                                                        {{ $sub->domain_name ?: 'سيُولّد تلقائياً' }}
                                                    </div>
                                                    @if ($sub->username || $sub->cpanel_username)
                                                        <div class="mt-1 text-xs text-gray-500">
                                                            المستخدم:
                                                            {{ $sub->cpanel_username ?: $sub->username }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusBadge['class'] }}">
                                                    {{ $statusBadge['label'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="space-y-2">
                                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $provisioningBadge['class'] }}">
                                                        {{ $provisioningBadge['label'] }}
                                                    </span>
                                                    @if ($sub->last_sync_message)
                                                        <div class="max-w-[14rem] truncate text-xs text-gray-500" title="{{ $sub->last_sync_message }}">
                                                            {{ $sub->last_sync_message }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap">
                                                <div class="font-medium text-gray-800">
                                                    {{ $sub->next_due_date?->format('Y-m-d') ?? 'غير محدد' }}
                                                </div>
                                                <div class="text-xs {{ $isOverdue ? 'text-red-600' : ($isDueSoon ? 'text-amber-600' : 'text-gray-500') }}">
                                                    @if (! $sub->next_due_date)
                                                        بانتظار التحديد
                                                    @elseif ($isOverdue)
                                                        متأخر عن التجديد
                                                    @elseif ($sub->next_due_date->isToday())
                                                        يستحق اليوم
                                                    @elseif ($isDueSoon)
                                                        خلال {{ $sub->next_due_date->diffInDays(now()->startOfDay()) }} أيام
                                                    @else
                                                        مجدول
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="whitespace-nowrap">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <a href="{{ route('client.subscriptions.show', $sub) }}"
                                                        class="inline-flex items-center gap-2 rounded-lg bg-primary/10 px-3 py-2 text-sm font-semibold text-primary transition hover:bg-primary/20">
                                                        <i class="ti ti-settings text-base leading-none"></i>
                                                        إدارة
                                                    </a>
                                                    @if ($siteUrl)
                                                        <a href="{{ $siteUrl }}" target="_blank" rel="noopener"
                                                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-500/10 px-3 py-2 text-sm font-semibold text-emerald-600 transition hover:bg-emerald-500/20">
                                                            <i class="ti ti-external-link text-base leading-none"></i>
                                                            فتح
                                                        </a>
                                                    @endif
                                                    @if (app()->environment('local'))
                                                        <a href="{{ route('tenant.preview', $sub) }}" target="_blank"
                                                            class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-600 transition hover:bg-gray-200">
                                                            <i class="ti ti-eye text-base leading-none"></i>
                                                            معاينة
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    {{ $subscriptions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-client-layout>
