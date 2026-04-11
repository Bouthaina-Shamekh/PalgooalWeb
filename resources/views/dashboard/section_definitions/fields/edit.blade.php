<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ __('Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.section_definitions.index') }}">{{ __('Section Definitions') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}">{{ __('Field Definitions') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ __('Edit Field') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('Edit Field Definition') }}</h2>
            </div>
        </div>
    </div>

    <form action="{{ route('dashboard.section_definitions.fields.update', [$sectionDefinition, $field]) }}" method="POST">
        @csrf
        @method('PUT')

        @include('dashboard.section_definitions.fields.form')
    </form>

    <form id="delete-field-form" action="{{ route('dashboard.section_definitions.fields.destroy', [$sectionDefinition, $field]) }}" method="POST" class="hidden" onsubmit="return confirm('{{ __('Delete this field definition?') }}');">
        @csrf
        @method('DELETE')
    </form>
</x-dashboard-layout>
