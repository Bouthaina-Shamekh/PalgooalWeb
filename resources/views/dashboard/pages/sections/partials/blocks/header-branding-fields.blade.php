{{-- Header branding fields: extracted partial for the bounded brand identity region, including title, logo, and navigation hint. --}}
@php
    $brandIdentityFieldColumnClass =
        $isProgrammingShowcase ||
        $isMobileAppShowcase ||
        $isDesignShowcase ||
        $isDigitalMarketingShowcase ||
        $isReviewsShowcase ||
        $isOurWorkShowcase ||
        $isDomainsShowcase ||
        $isTemplatesSliderShowcase ||
        $isTemplatesListingShowcase
            ? 'lg:col-span-2'
            : '';

    $mainTitleFieldColumnClass =
        $isHeroCampaign ||
        $isProgrammingShowcase ||
        $isMobileAppShowcase ||
        $isDesignShowcase ||
        $isDigitalMarketingShowcase ||
        $isReviewsShowcase ||
        $isOurWorkShowcase ||
        $isDomainsShowcase ||
        $isTemplatesSliderShowcase ||
        $isTemplatesListingShowcase ||
        $isSiteHeader
            ? 'lg:col-span-2'
            : '';

    $mainTitleLabelText = $isSiteHeader || $isSiteFooter
        ? __('Brand Name')
        : ($isHeroCampaign
            ? __('Main Title - Line 1')
            : ($isProgrammingShowcase ||
            $isMobileAppShowcase ||
            $isDesignShowcase ||
            $isDigitalMarketingShowcase ||
            $isReviewsShowcase ||
            $isOurWorkShowcase ||
            $isDomainsShowcase ||
            $isTemplatesSliderShowcase ||
            $isTemplatesListingShowcase
                ? __('Section Title')
                : __('Main Title')));

    $headerRegionFieldColumnClass = 'lg:col-span-2';
    $headerLogoFieldLabel = __('Brand Image');
    $headerLogoFieldHint = __(
        'Upload a brand image from your media library. If you leave this empty, the header will use the first letter of the brand name.',
    );
    $headerNavigationHint = __(
        'Navigation links are pulled automatically from your active site pages. Edit the button here if you want a highlighted action on the right side of the header.',
    );
@endphp

@if ($showBrandFields)
    <div class="{{ $brandIdentityFieldColumnClass }}">
        <label class="block text-sm font-medium text-slate-700">{{ __('Brand Prefix') }}</label>
        <input type="text" name="translations[{{ $code }}][content][brand_prefix]"
            value="{{ $brandPrefixValue }}"
            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
            placeholder="PAL">
    </div>

    <div class="{{ $brandIdentityFieldColumnClass }}">
        <label class="block text-sm font-medium text-slate-700">{{ __('Brand Suffix') }}</label>
        <input type="text" name="translations[{{ $code }}][content][brand_suffix]"
            value="{{ $brandSuffixValue }}"
            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
            placeholder="GOALS">
    </div>
@endif

@if ($showMainTitleField)
    <div class="{{ $mainTitleFieldColumnClass }}">
        <label class="block text-sm font-medium text-slate-700">
            {{ $mainTitleLabelText }}
        </label>
        <input type="text" name="translations[{{ $code }}][content][title]"
            value="{{ $heroTitleValue }}"
            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
            @if ($isReviewsShowcase) placeholder="{{ __('REVIEWS') }}" @endif
            @if ($isOurWorkShowcase) placeholder="{{ __('OUR WORK') }}" @endif>
    </div>
@endif

@if ($isSiteHeader)
    <div class="{{ $headerRegionFieldColumnClass }}">
        <x-dashboard.media-picker :name="'translations[' . $code . '][content][logo]'" :label="$headerLogoFieldLabel" :button-text="__('Choose From Media Library')"
            :value="$headerLogoValue" :preview-urls="$headerLogoPreviewUrls" :multiple="false" store-value="id" />
        <p class="mt-2 text-xs text-slate-500">
            {{ $headerLogoFieldHint }}
        </p>
    </div>
@endif

@if ($isSiteHeader)
    <div
        class="{{ $headerRegionFieldColumnClass }} rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
        {{ $headerNavigationHint }}
    </div>
@endif
