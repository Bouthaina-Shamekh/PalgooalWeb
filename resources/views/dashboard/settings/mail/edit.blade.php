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
                        <li class="breadcrumb-item active">{{ t('dashboard.Mail_Settings', 'إعدادات البريد') }}</li>
                    </ol>
                </nav>
                <h5 class="page-header-title">{{ t('dashboard.Mail_Settings', 'إعدادات البريد') }}</h5>
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
@if(session('mail_test_ok'))
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        {{ session('mail_test_ok') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('mail_test_error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        {{ session('mail_test_error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Validation Errors --}}
@if($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('dashboard.settings.mail.update') }}">
    @csrf
    @method('PUT')

<div class="grid grid-cols-12 gap-6">

    {{-- Main Form --}}
    <div class="col-span-12 xl:col-span-8">

        {{-- Section 1: Status --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                      style="width:24px;height:24px;font-size:12px;">١</span>
                <h5 class="mb-0">{{ t('dashboard.Mail_Status', 'الحالة') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch"
                           name="enabled" id="enabled" value="1"
                           {{ old('enabled', $mailSetting->enabled) ? 'checked' : '' }}>
                    <label class="form-check-label" for="enabled">
                        {{ t('dashboard.Mail_Enabled', 'استخدام إعدادات البريد هذه بدلاً من .env') }}
                    </label>
                </div>
                <div class="form-text">
                    {{ t('dashboard.Mail_Enabled_Hint', 'عند الإيقاف، يستمر الموقع باستخدام إعدادات البريد من ملف .env كما هي.') }}
                </div>
            </div>
        </div>

        {{-- Section 2: SMTP Configuration --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                      style="width:24px;height:24px;font-size:12px;">٢</span>
                <h5 class="mb-0">{{ t('dashboard.SMTP_Configuration', 'إعدادات SMTP') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Mailer --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">{{ t('dashboard.Mailer', 'طريقة الإرسال (Mailer)') }}</label>
                        <select name="mailer" class="form-select @error('mailer') is-invalid @enderror">
                            <option value="smtp" {{ old('mailer', $mailSetting->mailer) === 'smtp' ? 'selected' : '' }}>
                                SMTP
                            </option>
                        </select>
                        <div class="form-text">{{ t('dashboard.Mailer_Hint', 'المرحلة الحالية تدعم SMTP فقط.') }}</div>
                        @error('mailer')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Encryption --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">{{ t('dashboard.Encryption', 'التشفير (Encryption)') }}</label>
                        <select name="encryption" class="form-select @error('encryption') is-invalid @enderror">
                            <option value="" {{ old('encryption', $mailSetting->encryption) === null || old('encryption', $mailSetting->encryption) === '' ? 'selected' : '' }}>
                                {{ t('dashboard.Encryption_Auto', 'تلقائي (بحسب المنفذ)') }}
                            </option>
                            <option value="smtp" {{ old('encryption', $mailSetting->encryption) === 'smtp' ? 'selected' : '' }}>
                                TLS / STARTTLS ({{ t('dashboard.Recommended_Port', 'المنفذ المعتاد') }} 587)
                            </option>
                            <option value="smtps" {{ old('encryption', $mailSetting->encryption) === 'smtps' ? 'selected' : '' }}>
                                SSL ({{ t('dashboard.Implicit_TLS', 'تشفير مباشر') }}, {{ t('dashboard.Port', 'المنفذ') }} 465)
                            </option>
                        </select>
                        @error('encryption')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Host --}}
                    <div class="col-12 col-md-8">
                        <label class="form-label">{{ t('dashboard.SMTP_Host', 'الخادم (Host)') }}</label>
                        <input type="text" name="host" dir="ltr"
                               class="form-control font-mono @error('host') is-invalid @enderror"
                               value="{{ old('host', $mailSetting->host) }}"
                               placeholder="smtp.example.com" />
                        @error('host')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Port --}}
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ t('dashboard.Port', 'المنفذ (Port)') }}</label>
                        <input type="number" name="port" dir="ltr" min="1" max="65535"
                               class="form-control @error('port') is-invalid @enderror"
                               value="{{ old('port', $mailSetting->port) }}"
                               placeholder="587" />
                        @error('port')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Username --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">{{ t('dashboard.SMTP_Username', 'اسم المستخدم') }}</label>
                        <input type="text" name="username" dir="ltr" autocomplete="off"
                               class="form-control @error('username') is-invalid @enderror"
                               value="{{ old('username', $mailSetting->username) }}" />
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="col-12 col-md-6">
                        <label class="form-label">{{ t('dashboard.SMTP_Password', 'كلمة المرور') }}</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" dir="ltr"
                                   autocomplete="new-password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="{{ $mailSetting->exists && $mailSetting->getRawOriginal('password') ? '••••••••••••••••' : t('dashboard.Not_Set', 'غير مُكوَّن') }}" />
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleVisibility('password', this)">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            {{ t('dashboard.Password_Leave_Empty', 'اتركها فارغة للإبقاء على كلمة المرور الحالية. أدخل قيمة جديدة فقط إذا أردت تغييرها.') }}
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: From --}}
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                      style="width:24px;height:24px;font-size:12px;">٣</span>
                <h5 class="mb-0">{{ t('dashboard.Mail_From', 'المرسل') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">{{ t('dashboard.From_Address', 'البريد الإلكتروني للمرسل') }}</label>
                        <input type="email" name="from_address" dir="ltr"
                               class="form-control @error('from_address') is-invalid @enderror"
                               value="{{ old('from_address', $mailSetting->from_address) }}"
                               placeholder="no-reply@example.com" />
                        @error('from_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">{{ t('dashboard.From_Name', 'اسم المرسل') }}</label>
                        <input type="text" name="from_name"
                               class="form-control @error('from_name') is-invalid @enderror"
                               value="{{ old('from_name', $mailSetting->from_name) }}" />
                        @error('from_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                    {{ t('dashboard.Save_Mail_Settings', 'حفظ إعدادات البريد') }}
                </button>
            </div>

            <div class="card-body border-top">
                <div class="fw-semibold small mb-2">{{ t('dashboard.Current_Status', 'الحالة الحالية') }}</div>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ t('dashboard.Enabled', 'مفعّل') }}</span>
                        @if($mailSetting->enabled)
                            <span class="badge bg-success">{{ t('dashboard.Yes', 'نعم') }}</span>
                        @else
                            <span class="badge bg-secondary">{{ t('dashboard.No', 'لا') }}</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">{{ t('dashboard.SMTP_Password', 'كلمة المرور') }}</span>
                        @if($mailSetting->exists && $mailSetting->getRawOriginal('password'))
                            <span class="text-success small"><i class="ti ti-check"></i> {{ t('dashboard.Configured', 'مُكوَّنة') }}</span>
                        @else
                            <span class="text-danger small"><i class="ti ti-x"></i> {{ t('dashboard.Not_Set', 'غير مُكوَّنة') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
</form>

{{-- Send Test Email (Phase 2) -- deliberately a separate form/section from
     the settings form above: it only ever submits a recipient address,
     never SMTP credentials, and never touches the saved settings. --}}
<div class="grid grid-cols-12 gap-6 mt-2">
    <div class="col-span-12 xl:col-span-8">
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="ti ti-send"></i>
                <h5 class="mb-0">{{ t('dashboard.Send_Test_Email', 'إرسال بريد تجريبي') }}</h5>
            </div>
            <div class="card-body">
                <div class="form-text mb-3">
                    {{ t('dashboard.Send_Test_Email_Hint', 'يستخدم هذا الإرسال إعدادات البريد المحفوظة أعلاه فقط. احفظ الإعدادات أولاً إذا قمت بتعديلها.') }}
                </div>
                <form method="POST" action="{{ route('dashboard.settings.mail.test') }}" class="row g-3 align-items-start">
                    @csrf
                    <div class="col-12 col-md-8">
                        <label class="form-label">{{ t('dashboard.Test_Recipient', 'البريد الإلكتروني للمستلم') }}</label>
                        <input type="email" name="test_email" dir="ltr" required
                               class="form-control @error('test_email') is-invalid @enderror"
                               value="{{ old('test_email') }}"
                               placeholder="you@example.com" />
                        @error('test_email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end h-100">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="ti ti-send me-1"></i>
                            {{ t('dashboard.Send_Test_Email_Button', 'إرسال بريد تجريبي') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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
