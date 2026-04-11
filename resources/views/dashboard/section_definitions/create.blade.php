<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ __('Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.section_definitions.index') }}">{{ __('Section Definitions') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ __('Create Definition') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('Create Section Definition') }}</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">{{ __('Definition Information') }}</h5>
                    <p class="mb-0 text-sm text-slate-500">
                        {{ __('This form manages the definition record only. Nested field builders and runtime rendering will be handled separately.') }}
                    </p>
                </div>
                <div class="card-body">
                    <form action="{{ route('dashboard.section_definitions.store') }}" method="POST">
                        @csrf

                        @include('dashboard.section_definitions.form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
