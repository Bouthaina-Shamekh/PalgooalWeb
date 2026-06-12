<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.testimonials.index') }}">{{ t('dashboard.testimonials', 'Testimonials') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_Testimonial', 'Add Testimonial') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_New_Testimonial', 'Add New Testimonial') }}</h2>
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
            <form action="{{ route('dashboard.testimonials.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('dashboard.testimonials._form')
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
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Testimonial_Image', 'Profile Image') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Testimonial_Image_Desc', 'Choose a square profile photo for the testimonial card. Leave empty to show a placeholder.') }}</p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Testimonial_Order', 'Display Order') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Testimonial_Order_Desc', 'Lower numbers appear first. Use consecutive numbers to control the display sequence.') }}</p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Testimonial_Approval', 'Publication Status') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Testimonial_Approval_Desc', 'Approved testimonials are immediately visible to visitors. Pending ones stay hidden until approved.') }}</p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Testimonial_Translations', 'Translations') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Testimonial_Translations_Desc', 'Fill in the name, job title and testimonial text for at least one language.') }}</p>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
