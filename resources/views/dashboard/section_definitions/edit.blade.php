<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ __('Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.section_definitions.index') }}">{{ __('Section Definitions') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ __('Edit Definition') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('Edit Section Definition') }}</h2>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded bg-green-100 px-4 py-2 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4 flex justify-end">
        <a href="{{ route('dashboard.section_definitions.fields.index', $sectionDefinition) }}" class="btn btn-light-primary">
            {{ __('Manage Fields') }}
        </a>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">{{ __('Definition Information') }}</h5>
                    <p class="mb-0 text-sm text-slate-500">
                        {{ __('Update definition metadata here, then continue to field definitions when the dynamic schema needs changes. Template and preset resolution remain code-side.') }}
                    </p>
                </div>
                <div class="card-body">
                    <form action="{{ route('dashboard.section_definitions.update', $sectionDefinition) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @include('dashboard.section_definitions.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
