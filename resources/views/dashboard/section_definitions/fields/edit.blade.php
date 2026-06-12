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
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Edit_Field', 'تعديل الحقل') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Edit_Field_Definition', 'تعديل تعريف الحقل') }}</h2>
            </div>
        </div>
    </div>

    <form action="{{ route('dashboard.section_definitions.fields.update', [$sectionDefinition, $field]) }}" method="POST">
        @csrf
        @method('PUT')

        @include('dashboard.section_definitions.fields.form')
    </form>

    <form id="delete-field-form"
          action="{{ route('dashboard.section_definitions.fields.destroy', [$sectionDefinition, $field]) }}"
          method="POST"
          class="hidden"
          onsubmit="return confirm('{{ t('dashboard.Confirm_Delete_Field', 'حذف هذا الحقل؟') }}');">
        @csrf
        @method('DELETE')
    </form>
</x-dashboard-layout>
