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
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}">{{ t('dashboard.Field_Definitions', 'تعريفات الحقول') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Create_Field', 'إنشاء حقل') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Create_Field_Definition', 'إنشاء تعريف حقل جديد') }}</h2>
            </div>
        </div>
    </div>

    <form action="{{ route('dashboard.section_definitions.fields.store', $sectionDefinition) }}" method="POST">
        @csrf

        @include('dashboard.section_definitions.fields.form')
    </form>
</x-dashboard-layout>
