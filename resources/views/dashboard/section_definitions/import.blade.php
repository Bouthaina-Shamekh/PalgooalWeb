<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ __('Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.section_definitions.index') }}">{{ __('Section Definitions') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ __('Import') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('Import Section Definitions') }}</h2>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded bg-red-100 px-4 py-2 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded bg-red-100 px-4 py-2 text-red-800">
            <ul class="mb-0 list-disc ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 lg:col-span-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">{{ __('Upload JSON') }}</h5>
                    <p class="mb-0 text-sm text-slate-500">
                        {{ __('This imports definition metadata only. Page content, tenant content, media files, templates catalog, portfolios, and reviews are not imported.') }}
                    </p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.section_definitions.import.preview') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="definitions_json" class="form-label">{{ __('Section Definitions JSON') }}</label>
                            <input id="definitions_json" type="file" name="definitions_json" class="form-control" accept=".json,application/json" required>
                            <p class="mt-2 text-xs text-slate-500">
                                {{ __('The file is validated first and a preview summary is shown before anything is saved.') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Preview Import') }}
                            </button>
                            <a href="{{ route('dashboard.section_definitions.index') }}" class="btn btn-light">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Import Scope') }}</h5>
                </div>
                <div class="card-body text-sm text-slate-600">
                    <ul class="mb-0 list-disc space-y-2 ps-5">
                        <li>{{ __('Includes section definitions, template keys, fields, repeater schemas, options, flags, and ordering.') }}</li>
                        <li>{{ __('Matches existing records by section_key and template_key, never by database ID.') }}</li>
                        <li>{{ __('Runs inside a database transaction when you apply the preview.') }}</li>
                        <li>{{ __('Local fields not present in the JSON are preserved during updates.') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
