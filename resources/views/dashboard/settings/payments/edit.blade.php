<x-dashboard-layout>

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
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard.settings.payments.index') }}">{{ t('dashboard.Payment_Gateways', 'بوابات الدفع') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $gateway->name }}</li>
                    </ol>
                </nav>
                <h5 class="page-header-title">
                    {{ t('dashboard.Edit_Gateway', 'تعديل بوابة الدفع') }}: {{ $gateway->name }}
                </h5>
            </div>
        </div>
    </div>
</div>

{{-- Flash / Validation Errors --}}
@if($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('dashboard.settings.payments.update', $gateway) }}">
    @csrf

<div class="grid grid-cols-12 gap-6">

    {{-- Main Form --}}
    <div class="col-span-12 xl:col-span-8">

        {{-- Section 1: Basic Info --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                      style="width:24px;height:24px;font-size:12px;">١</span>
                <h5 class="mb-0">{{ t('dashboard.Gateway_Info', 'معلومات البوابة') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Name --}}
                    <div class="col-12">
                        <label class="form-label">{{ t('dashboard.Gateway_Name', 'اسم البوابة') }}</label>
                        <input type="text"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $gateway->name) }}"
                               placeholder="Lahza" />
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Driver (read-only) --}}
                    <div class="col-12">
                        <label class="form-label">{{ t('dashboard.Driver', 'المفتاح (driver)') }}</label>
                        <input type="text"
                               class="form-control font-mono bg-light"
                               value="{{ $gateway->driver }}"
                               dir="ltr"
                               readonly />
                        <div class="form-text">{{ t('dashboard.Driver_Hint', 'المفتاح لا يمكن تغييره — يجب أن يطابق قيمة PAYMENT_GATEWAY في .env') }}</div>
                    </div>

                    {{-- Mode --}}
                    <div class="col-12">
                        <label class="form-label">{{ t('dashboard.Gateway_Mode', 'وضع التشغيل') }}</label>
                        <div class="d-flex gap-4 mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_sandbox"
                                       value="sandbox"
                                       {{ old('mode', $gateway->mode) === 'sandbox' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mode_sandbox">
                                    <i class="ti ti-flask text-warning me-1"></i>
                                    {{ t('dashboard.Sandbox', 'اختبار (Sandbox)') }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_live"
                                       value="live"
                                       {{ old('mode', $gateway->mode) === 'live' ? 'checked' : '' }}>
                                <label class="form-check-label" for="mode_live">
                                    <i class="ti ti-bolt text-danger me-1"></i>
                                    {{ t('dashboard.Live', 'إنتاج (Live)') }}
                                </label>
                            </div>
                        </div>
                        <div class="form-text text-danger small">
                            {{ t('dashboard.Live_Warning', '⚠ وضع الإنتاج يُجري مدفوعات حقيقية. تأكد من صحة المفاتيح قبل التفعيل.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: API Keys --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                      style="width:24px;height:24px;font-size:12px;">٢</span>
                <h5 class="mb-0">{{ t('dashboard.API_Keys', 'مفاتيح API') }}</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info small mb-3">
                    <i class="ti ti-info-circle me-1"></i>
                    {{ t('dashboard.Keys_Leave_Empty', 'اتركها فارغة للإبقاء على المفاتيح الحالية. أدخل قيمة جديدة فقط إذا أردت تغييرها.') }}
                </div>
                <div class="row g-3">
                    {{-- Public Key --}}
                    <div class="col-12">
                        <label class="form-label">{{ t('dashboard.Public_Key', 'المفتاح العام (Public Key)') }}</label>
                        <input type="text"
                               name="public_key"
                               class="form-control font-mono @error('public_key') is-invalid @enderror"
                               dir="ltr"
                               autocomplete="off"
                               placeholder="{{ $gateway->getRawOriginal('public_key') ? '••••••••••••••••' : t('dashboard.Not_Set', 'غير مُكوَّن') }}" />
                        @error('public_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Secret Key --}}
                    <div class="col-12">
                        <label class="form-label">{{ t('dashboard.Secret_Key', 'المفتاح السري (Secret Key)') }}</label>
                        <div class="input-group">
                            <input type="password"
                                   name="secret_key"
                                   id="secret_key"
                                   class="form-control font-mono @error('secret_key') is-invalid @enderror"
                                   dir="ltr"
                                   autocomplete="new-password"
                                   placeholder="{{ $gateway->getRawOriginal('secret_key') ? '••••••••••••••••' : t('dashboard.Not_Set', 'غير مُكوَّن') }}" />
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="toggleVisibility('secret_key', this)">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                        @error('secret_key')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Webhook Secret --}}
                    <div class="col-12">
                        <label class="form-label">{{ t('dashboard.Webhook_Secret', 'مفتاح التحقق من الـ Webhook') }}</label>
                        <div class="input-group">
                            <input type="password"
                                   name="webhook_secret"
                                   id="webhook_secret"
                                   class="form-control font-mono @error('webhook_secret') is-invalid @enderror"
                                   dir="ltr"
                                   autocomplete="new-password"
                                   placeholder="{{ $gateway->getRawOriginal('webhook_secret') ? '••••••••••••••••' : t('dashboard.Not_Set', 'غير مُكوَّن') }}" />
                            <button type="button" class="btn btn-outline-secondary"
                                    onclick="toggleVisibility('webhook_secret', this)">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                        @error('webhook_secret')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            {{ t('dashboard.Webhook_Url_Hint', 'Webhook URL:') }}
                            <code dir="ltr" class="ms-1">{{ url('/payment/webhook/' . $gateway->driver) }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Sidebar --}}
    <div class="col-span-12 xl:col-span-4">
        <div class="card sticky top-6">
            <div class="card-header">
                <h5 class="mb-0">{{ t('dashboard.Actions', 'الإجراءات') }}</h5>
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-device-floppy me-1"></i>
                    {{ t('dashboard.Save_Gateway', 'حفظ الإعدادات') }}
                </button>
                <a href="{{ route('dashboard.settings.payments.index') }}" class="btn btn-light w-100">
                    {{ t('dashboard.Cancel', 'إلغاء') }}
                </a>
            </div>

            <div class="card-body border-top">
                <div class="fw-semibold small mb-2">{{ t('dashboard.Current_Status', 'الحالة الحالية') }}</div>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ t('dashboard.Active', 'نشطة') }}</span>
                        @if($gateway->is_active)
                            <span class="badge bg-success">{{ t('dashboard.Yes', 'نعم') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ t('dashboard.No', 'لا') }}</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ t('dashboard.Mode', 'الوضع') }}</span>
                        @if($gateway->isLive())
                            <span class="badge bg-danger">{{ t('dashboard.Live', 'إنتاج') }}</span>
                        @else
                            <span class="badge bg-warning text-dark">{{ t('dashboard.Sandbox', 'اختبار') }}</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ t('dashboard.Secret_Key', 'Secret Key') }}</span>
                        @if($gateway->getRawOriginal('secret_key'))
                            <span class="text-success small"><i class="ti ti-check"></i></span>
                        @else
                            <span class="text-danger small"><i class="ti ti-x"></i></span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ t('dashboard.Webhook_Secret', 'Webhook Secret') }}</span>
                        @if($gateway->getRawOriginal('webhook_secret'))
                            <span class="text-success small"><i class="ti ti-check"></i></span>
                        @else
                            <span class="text-danger small"><i class="ti ti-x"></i></span>
                        @endif
                    </div>
                </div>
            </div>

            @if(!$gateway->is_active)
            <div class="card-body border-top">
                <form method="POST"
                      action="{{ route('dashboard.settings.payments.activate', $gateway) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="ti ti-power me-1"></i>
                        {{ t('dashboard.Activate_This_Gateway', 'تفعيل هذه البوابة') }}
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

</div>
</form>

<script>
function toggleVisibility(fieldId, btn) {
    var field = document.getElementById(fieldId);
    if (!field) return;
    var isPassword = field.type === 'password';
    field.type = isPassword ? 'text' : 'password';
    var icon = btn.querySelector('i');
    if (icon) {
        icon.className = isPassword ? 'ti ti-eye-off' : 'ti ti-eye';
    }
}
</script>

</x-dashboard-layout>
