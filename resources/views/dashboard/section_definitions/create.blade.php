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
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Create_Definition', 'إنشاء تعريف') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Create_Section_Definition', 'إنشاء تعريف قسم') }}</h2>
            </div>
        </div>
    </div>

    <form action="{{ route('dashboard.section_definitions.store') }}" method="POST" id="section-def-form">
        @csrf

        <div class="grid grid-cols-12 gap-6">

            {{-- ١ — الفورم الرئيسي --}}
            <div class="col-span-12 xl:col-span-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-1">{{ t('dashboard.Definition_Information', 'معلومات التعريف') }}</h5>
                        <p class="mb-0 text-sm text-slate-500">
                            {{ t('dashboard.Def_Create_Sidebar_Hint', 'احفظ التعريف أولاً ثم أضف الحقول الديناميكية.') }}
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
                            {{ t('dashboard.Def_Workflow_Title', 'أدخل الاسم والمفتاح ووضع المحرر، ثم احفظ للانتقال إلى إدارة الحقول.') }}
                        </p>
                    </div>
                    <div class="card-footer d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>
                            {{ t('dashboard.Create_Definition_Continue', 'إنشاء التعريف ومتابعة') }}
                        </button>
                        <a href="{{ route('dashboard.section_definitions.index') }}" class="btn btn-light">
                            {{ t('dashboard.Cancel', 'إلغاء') }}
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</x-dashboard-layout>
