<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.portfolios.index') }}">{{ t('dashboard.portfolios', 'Portfolios') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_Portfolio', 'Add Portfolio') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_New_Portfolio', 'Add New Portfolio') }}</h2>
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
            <form action="{{ route('dashboard.portfolios.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('dashboard.portfolios._form')
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
                <div class="card-body space-y-5 text-sm text-gray-600">

                    <div>
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Portfolio_Images', 'Images') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Portfolio_Images_Desc', 'The default image appears in the portfolio list. Add multiple images for a detailed gallery view.') }}</p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Portfolio_Order', 'Display Order') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Portfolio_Order_Desc', 'Lower numbers appear first in the portfolio list. Use 0 for highest priority.') }}</p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Portfolio_Translations', 'Translations') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Portfolio_Translations_Desc', 'Enter the title and details for each language. Active language fields are required.') }}</p>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
