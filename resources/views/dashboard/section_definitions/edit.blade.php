<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.section_definitions.index') }}">{{ t('dashboard.Section_Definitions', 'تعريفات الأقسام') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Edit_Definition', 'تعديل التعريف') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Edit_Section_Definition', 'تعديل تعريف القسم') }}</h2>
            </div>
        </div>
    </div>

    @if (session('ok'))
        <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <form action="{{ route('dashboard.section_definitions.update', $sectionDefinition) }}" method="POST" id="section-def-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-12 gap-6">

            {{-- ١ — الفورم الرئيسي --}}
            <div class="col-span-12 xl:col-span-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-1">{{ t('dashboard.Definition_Information', 'معلومات التعريف') }}</h5>
                        <p class="mb-0 text-sm text-slate-500">
                            {{ t('dashboard.Def_Workflow_Desc', 'اختر Dynamic لوضع المحرر، أدخل Category و Template Key مستقرَّين، احفظ التعريف، ثم انتقل لتعريفات الحقول.') }}
                        </p>
                    </div>
                    <div class="card-body">
                        @include('dashboard.section_definitions.form')
                    </div>
                </div>
            </div>

            {{-- ٢ — الشريط الجانبي --}}
            <div class="col-span-12 xl:col-span-4">
                <div class="card sticky top-6">
                    <div class="card-header">
                        <h5 class="mb-0">{{ t('dashboard.Actions', 'الإجراءات') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-sm text-slate-500">
                            {{ t('dashboard.Def_Sidebar_Hint', 'بعد الحفظ يمكنك إدارة الحقول لضبط المخطط الديناميكي للقسم.') }}
                        </p>
                    </div>
                    <div class="card-footer d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            {{ t('dashboard.Update_Definition', 'حفظ التعديلات') }}
                        </button>
                        <button type="submit" name="after_save" value="fields" class="btn btn-light">
                            <i class="ti ti-layout-list me-1"></i>
                            {{ t('dashboard.Update_And_Manage_Fields', 'حفظ وإدارة الحقول') }}
                        </button>
                        <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}" class="btn btn-light">
                            <i class="ti ti-list-details me-1"></i>
                            {{ t('dashboard.Manage_Fields', 'إدارة الحقول') }}
                        </a>
                        <a href="{{ route('dashboard.section_definitions.index') }}" class="btn btn-light">
                            {{ t('dashboard.Cancel', 'إلغاء') }}
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</x-dashboard-layout>
