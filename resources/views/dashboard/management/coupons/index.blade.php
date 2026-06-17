<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Coupons', 'الكوبونات') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Coupons_List', 'قائمة الكوبونات') }}</h2>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('ok'))
        <div class="alert alert-success mb-4 flex items-center gap-2">
            <i class="ti ti-circle-check text-xl"></i>
            <span>{{ session('ok') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-4 flex items-center gap-2">
            <i class="ti ti-alert-circle text-xl"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">

                {{-- Toolbar --}}
                <div class="card-header">
                    <form method="GET" action="{{ route('dashboard.coupons.index') }}"
                          class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3">

                        {{-- Search --}}
                        <div class="relative flex-1 min-w-[180px]">
                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ti ti-search text-base"></i>
                            </span>
                            <input type="text" name="search"
                                   value="{{ $search ?? '' }}"
                                   placeholder="{{ t('dashboard.Search_Coupons', 'بحث عن كوبون…') }}"
                                   class="w-full border rounded-xl pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        {{-- Per page --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'Per page') }}</span>
                            <select name="per_page" onchange="this.form.submit()"
                                    class="border rounded-xl px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                                @foreach([10, 25, 50] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 20) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Submit + Clear --}}
                        <div class="flex gap-2 shrink-0">
                            <button type="submit" class="btn btn-light">
                                <i class="ti ti-search me-1"></i>{{ t('dashboard.Search', 'بحث') }}
                            </button>
                            @if($search)
                                <a href="{{ route('dashboard.coupons.index') }}" class="btn btn-light text-gray-500">
                                    <i class="ti ti-x me-1"></i>{{ t('dashboard.Clear', 'مسح') }}
                                </a>
                            @endif
                        </div>

                        {{-- Add Coupon button --}}
                        <a href="{{ route('dashboard.coupons.create') }}" class="btn btn-primary shrink-0 ms-auto">
                            <i class="ti ti-plus me-1"></i>{{ t('dashboard.Add_Coupon', 'إضافة كوبون') }}
                        </a>
                    </form>
                </div>

                {{-- Table --}}
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ t('dashboard.Coupon_Code_Col', 'الكود') }}</th>
                                    <th>{{ t('dashboard.Coupon_Type_Col', 'النوع') }}</th>
                                    <th>{{ t('dashboard.Coupon_Value_Col', 'القيمة') }}</th>
                                    <th>{{ t('dashboard.Coupon_Used_Col', 'الاستخدام') }}</th>
                                    <th>{{ t('dashboard.Coupon_Expires_Col', 'الانتهاء') }}</th>
                                    <th>{{ t('dashboard.Coupon_Status_Col', 'الحالة') }}</th>
                                    <th class="text-end">{{ t('dashboard.Actions', 'إجراءات') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($coupons as $coupon)
                                    <tr>
                                        {{-- Code --}}
                                        <td>
                                            <span class="font-mono font-semibold tracking-wider text-primary">
                                                {{ $coupon->code }}
                                            </span>
                                        </td>

                                        {{-- Type --}}
                                        <td>
                                            @if($coupon->discount_type === 'percent')
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-700">
                                                    <i class="ti ti-percentage me-1"></i>{{ t('dashboard.Coupon_Type_Percent', 'نسبة مئوية') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700">
                                                    <i class="ti ti-currency-dollar me-1"></i>{{ t('dashboard.Coupon_Type_Fixed', 'مبلغ ثابت') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Value --}}
                                        <td class="font-semibold">
                                            @if($coupon->discount_type === 'percent')
                                                {{ number_format($coupon->discount_value, 0) }}%
                                            @else
                                                ${{ number_format($coupon->discount_value, 2) }}
                                            @endif
                                        </td>

                                        {{-- Used / Max --}}
                                        <td>
                                            <span class="{{ $coupon->max_uses && $coupon->used_count >= $coupon->max_uses ? 'text-danger font-semibold' : '' }}">
                                                {{ $coupon->used_count }}
                                                /
                                                {{ $coupon->max_uses ?? t('dashboard.Coupon_Unlimited', 'بلا حدود') }}
                                            </span>
                                        </td>

                                        {{-- Expires At --}}
                                        <td>
                                            @if($coupon->expires_at)
                                                <span class="{{ $coupon->expires_at->isPast() ? 'text-danger' : 'text-muted' }}">
                                                    {{ $coupon->expires_at->format('Y-m-d') }}
                                                </span>
                                            @else
                                                <span class="text-muted text-xs">{{ t('dashboard.Coupon_No_Expiry', 'لا تاريخ') }}</span>
                                            @endif
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            @if($coupon->is_active)
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-700">
                                                    {{ t('dashboard.Status_Active', 'نشط') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-600">
                                                    {{ t('dashboard.Client_Inactive', 'معطّل') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="text-end">
                                            <a href="{{ route('dashboard.coupons.edit', $coupon) }}"
                                               class="btn btn-icon btn-sm text-primary hover:bg-primary/10"
                                               title="{{ t('dashboard.Edit', 'تعديل') }}">
                                                <i class="ti ti-edit text-base"></i>
                                            </a>

                                            <form action="{{ route('dashboard.coupons.destroy', $coupon) }}"
                                                  method="POST" class="inline-block"
                                                  onsubmit="return confirm('{{ t('dashboard.Confirm_Delete_Coupon', 'هل أنت متأكد من حذف هذا الكوبون؟') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-icon btn-sm text-danger hover:bg-danger/10"
                                                        title="{{ t('dashboard.Delete', 'حذف') }}">
                                                    <i class="ti ti-trash text-base"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-16 text-center">
                                            <div class="flex flex-col items-center gap-3">
                                                <svg class="w-16 h-16 text-gray-300" viewBox="0 0 24 24" fill="none"
                                                     stroke="currentColor" stroke-width="1.2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                                </svg>
                                                @if($search)
                                                    <p class="text-muted font-medium">{{ t('dashboard.No_Coupons_Search', 'لا توجد نتائج مطابقة لبحثك.') }}</p>
                                                    <a href="{{ route('dashboard.coupons.index') }}" class="btn btn-light btn-sm">
                                                        {{ t('dashboard.Clear', 'مسح البحث') }}
                                                    </a>
                                                @else
                                                    <p class="text-muted font-medium">{{ t('dashboard.No_Coupons', 'لا توجد كوبونات بعد.') }}</p>
                                                    <p class="text-sm text-gray-400">{{ t('dashboard.No_Coupons_Desc', 'أضف أول كوبون خصم لتشجيع العملاء على الشراء.') }}</p>
                                                    <a href="{{ route('dashboard.coupons.create') }}" class="btn btn-primary btn-sm">
                                                        <i class="ti ti-plus me-1"></i>{{ t('dashboard.Add_Coupon', 'إضافة كوبون') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Pagination --}}
                @if($coupons->hasPages())
                    <div class="card-footer">
                        {{ $coupons->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-dashboard-layout>
