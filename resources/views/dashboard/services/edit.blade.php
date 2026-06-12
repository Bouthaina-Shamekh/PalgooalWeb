<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.services.index') }}">{{ t('dashboard.Services_List', 'Services') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Edit_Service', 'Edit Service') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">
                    {{ t('dashboard.Edit_Service', 'Edit Service') }}
                    @php
                        $trans = $service->translations->firstWhere('locale', app()->getLocale())
                               ?? $service->translations->first();
                    @endphp
                    @if ($trans?->title)
                        <span class="text-gray-400 font-normal text-lg">— {{ $trans->title }}</span>
                    @endif
                </h2>
            </div>
        </div>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-6">

        {{-- ═══ FORM (col-span-8) ═══════════════════════════════════════ --}}
        <div class="col-span-12 xl:col-span-8">
            <form action="{{ route('dashboard.services.update', $service->id) }}" method="POST"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('dashboard.services._form')
            </form>
        </div>

        {{-- ═══ HELP SIDEBAR (col-span-4) ══════════════════════════════ --}}
        <div class="col-span-12 xl:col-span-4">
            <div class="card sticky top-6">
                <div class="card-header">
                    <h5 class="mb-0 flex items-center gap-2">
                        <i class="ti ti-info-circle text-primary"></i>
                        {{ t('dashboard.Help', 'Help') }}
                    </h5>
                </div>
                <div class="card-body space-y-5 text-sm">

                    <div>
                        <p class="font-semibold text-gray-800 mb-1">
                            {{ t('dashboard.Help_Service_Icon', 'Service Icon') }}
                        </p>
                        <p class="text-muted">
                            {{ t('dashboard.Help_Service_Icon_Desc', 'Choose a square image or SVG icon to represent this service.') }}
                        </p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">
                            {{ t('dashboard.Help_Service_Order', 'Display Order') }}
                        </p>
                        <p class="text-muted">
                            {{ t('dashboard.Help_Service_Order_Desc', 'Lower numbers appear first. Use consecutive numbers for predictable ordering.') }}
                        </p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">
                            {{ t('dashboard.Help_Service_Translations', 'Translations') }}
                        </p>
                        <p class="text-muted">
                            {{ t('dashboard.Help_Service_Translations_Desc', 'Fill in the title and description for each supported language. Arabic is required.') }}
                        </p>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
