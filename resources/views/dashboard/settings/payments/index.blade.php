<x-dashboard-layout>

@php
    $activeGateway = $gateways->firstWhere('is_active', true);
@endphp

{{-- Page Header --}}
<div class="page-header">
    <div class="page-block">
        <div class="row align-items-center">
            <div class="col-auto">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ t('dashboard.Payment_Gateways', 'بوابات الدفع') }}</li>
                    </ol>
                </nav>
                <h5 class="page-header-title">{{ t('dashboard.Payment_Gateways', 'بوابات الدفع') }}</h5>
            </div>
        </div>
    </div>
</div>

{{-- Flash Messages --}}
@if(session('ok'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('ok') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Active Gateway Banner --}}
@if($activeGateway)
    <div class="alert mb-4 d-flex align-items-center gap-3"
         style="background:#f0fdf4;border:1px solid #bbf7d0;">
        <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle"
             style="width:40px;height:40px;background:#dcfce7;">
            <i class="ti ti-shield-check text-success" style="font-size:18px;"></i>
        </div>
        <div>
            <div class="fw-semibold text-success">{{ t('dashboard.Active_Gateway', 'البوابة النشطة') }}: {{ $activeGateway->name }}</div>
            <div class="text-muted small">
                {{ $activeGateway->isLive()
                    ? t('dashboard.Live_Mode', 'وضع الإنتاج — مدفوعات حقيقية')
                    : t('dashboard.Sandbox_Mode', 'وضع الاختبار — مدفوعات تجريبية') }}
            </div>
        </div>
    </div>
@else
    <div class="alert alert-warning mb-4 d-flex align-items-center gap-3">
        <i class="ti ti-alert-triangle" style="font-size:20px;"></i>
        <span>{{ t('dashboard.No_Active_Gateway', 'لا توجد بوابة دفع نشطة. المدفوعات معطّلة للعملاء.') }}</span>
    </div>
@endif

{{-- Gateways Table --}}
<div class="card table-card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ t('dashboard.Gateway_Name', 'البوابة') }}</th>
                    <th>{{ t('dashboard.Driver', 'المفتاح') }}</th>
                    <th>{{ t('dashboard.Mode', 'الوضع') }}</th>
                    <th>{{ t('dashboard.Keys_Configured', 'المفاتيح') }}</th>
                    <th>{{ t('dashboard.Status', 'الحالة') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($gateways as $gw)
                    <tr>
                        <td class="fw-semibold">{{ $gw->name }}</td>
                        <td>
                            <code class="text-muted small">{{ $gw->driver }}</code>
                        </td>
                        <td>
                            @if($gw->isLive())
                                <span class="badge bg-danger-subtle text-danger">
                                    <i class="ti ti-bolt me-1"></i>{{ t('dashboard.Live', 'إنتاج') }}
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="ti ti-flask me-1"></i>{{ t('dashboard.Sandbox', 'اختبار') }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @php
                                $hasKeys = !empty($gw->getRawOriginal('secret_key'));
                            @endphp
                            @if($hasKeys)
                                <span class="text-success small"><i class="ti ti-check"></i> {{ t('dashboard.Keys_Set', 'مُكوَّنة') }}</span>
                            @else
                                <span class="text-muted small"><i class="ti ti-x"></i> {{ t('dashboard.Keys_Missing', 'غير مكوَّنة') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($gw->is_active)
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-green-100 text-green-700">
                                    {{ t('dashboard.Status_Active', 'نشطة') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500">
                                    {{ t('dashboard.Inactive', 'غير نشطة') }}
                                </span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                {{-- Edit --}}
                                <a href="{{ route('dashboard.settings.payments.edit', $gw) }}"
                                   class="btn btn-light btn-sm"
                                   title="{{ t('dashboard.Edit', 'تعديل') }}">
                                    <i class="ti ti-pencil"></i>
                                </a>

                                {{-- Activate --}}
                                @if(!$gw->is_active)
                                    <form method="POST"
                                          action="{{ route('dashboard.settings.payments.activate', $gw) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm"
                                                title="{{ t('dashboard.Activate', 'تفعيل') }}">
                                            <i class="ti ti-power"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST"
                                          action="{{ route('dashboard.settings.payments.deactivate', $gw) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm"
                                                title="{{ t('dashboard.Deactivate', 'إيقاف') }}">
                                            <i class="ti ti-power-off"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            {{ t('dashboard.No_Gateways', 'لا توجد بوابات دفع مُسجَّلة بعد. شغّل seeder لإضافة البوابة الافتراضية.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Security Note --}}
<div class="card mt-3" style="border:1px solid #e2e8f0;">
    <div class="card-body py-3">
        <div class="d-flex gap-3 align-items-start">
            <i class="ti ti-lock text-primary mt-1" style="font-size:18px;"></i>
            <div>
                <div class="fw-semibold text-sm mb-1">{{ t('dashboard.Keys_Security_Title', 'أمان مفاتيح API') }}</div>
                <div class="text-muted small">
                    {{ t('dashboard.Keys_Security_Desc', 'جميع المفاتيح مُشفَّرة في قاعدة البيانات باستخدام AES-256 (APP_KEY). لا تظهر في السجلات ولا في الردود.') }}
                </div>
            </div>
        </div>
    </div>
</div>

</x-dashboard-layout>
