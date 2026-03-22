@php
    use App\Models\Template as TemplateModel;
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $basePrice = (float) ($template->price ?? 0);
    $discRaw = $template->discount_price;
    $discPrice = is_null($discRaw) ? null : (float) $discRaw;
    $hasDiscount = ! is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
    $finalPrice = $hasDiscount ? $discPrice : $basePrice;
    $endsAt = $hasDiscount && ! empty($template->discount_ends_at) ? Carbon::parse($template->discount_ends_at) : null;

    $resolveMediaUrl = static function (?string $value, bool $storage = false): ?string {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//', '/', 'data:'])) {
            return $value;
        }

        $normalized = str_replace('\\', '/', $value);
        $looksLikePath = str_contains($normalized, '/')
            || (bool) preg_match('/\.[a-z0-9]{2,5}($|\?)/i', $normalized);

        if (! $looksLikePath) {
            return null;
        }

        return asset(($storage ? 'storage/' : '') . ltrim($normalized, '/'));
    };

    $looksLikeImageAsset = static function (?string $value): bool {
        $value = trim((string) $value);

        if ($value === '') {
            return false;
        }

        if (Str::startsWith($value, 'data:image/')) {
            return true;
        }

        $normalized = str_replace('\\', '/', $value);

        return (bool) preg_match('/\.(png|jpe?g|gif|svg|webp|avif|bmp|ico)(?:$|[?#])/i', $normalized);
    };

    $mediaLabelFromPath = static function (?string $value): string {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $path = parse_url($value, PHP_URL_PATH);
        $filename = basename($path ?: str_replace('\\', '/', $value));
        $label = preg_replace('/\.[^.]+$/', '', $filename);

        return trim(str_replace(['-', '_'], ' ', (string) $label));
    };

    $templateName = trim((string) ($translation?->name ?? __('Template')));
    $templateImageUrl = $resolveMediaUrl($template->image, true);
    $shortDesc = Str::limit(Str::of(strip_tags($translation?->description ?? ''))->squish()->toString(), 160);

    $payload = is_array($translation?->details)
        ? $translation->details
        : (json_decode($translation->details ?? '[]', true) ?: []);

    $resolveGalleryItems = static function (array $details, callable $urlResolver, string $name) {
        return collect($details['gallery'] ?? [])
            ->filter(fn ($item) => is_array($item) && filled($item['src'] ?? null))
            ->map(function ($item) use ($urlResolver, $name) {
                $src = $urlResolver($item['src'] ?? null);

                if (! $src) {
                    return null;
                }

                return [
                    'src' => $src,
                    'alt' => trim((string) ($item['alt'] ?? ($name . ' screenshot'))),
                ];
            })
            ->filter()
            ->values();
    };

    $resolveDevelopmentItems = static function (array $details, callable $urlResolver, callable $imageDetector) use ($mediaLabelFromPath) {
        return collect($details['details'] ?? [])
            ->filter(function ($item) use ($imageDetector) {
                if (! is_array($item)) {
                    return false;
                }

                $source = trim((string) ($item['src'] ?? ($item['image'] ?? ($item['value'] ?? ''))));

                return $source !== '' && $imageDetector($source);
            })
            ->map(function ($item) use ($urlResolver, $mediaLabelFromPath) {
                $label = trim((string) ($item['alt'] ?? ($item['name'] ?? ($item['label'] ?? ''))));
                $source = trim((string) ($item['src'] ?? ($item['image'] ?? ($item['value'] ?? ''))));
                $src = $urlResolver($source);

                if (! $src) {
                    return null;
                }

                if ($label === '') {
                    $label = $mediaLabelFromPath($source);
                }

                return [
                    'name' => $label,
                    'src' => $src,
                    'alt' => trim((string) ($item['alt'] ?? ($label !== '' ? $label : __('Development tool')))),
                ];
            })
            ->filter()
            ->values();
    };

    $resolveDashboardItems = static function (array $details, callable $urlResolver, string $name) {
        $dashboardPayload = is_array($details['specs'] ?? null) ? ($details['specs'] ?? []) : [];

        return collect(is_array($dashboardPayload['images'] ?? null) ? ($dashboardPayload['images'] ?? []) : [])
            ->filter(fn ($item) => is_array($item) && filled($item['src'] ?? null))
            ->map(function ($item) use ($urlResolver, $name) {
                $src = $urlResolver($item['src'] ?? null);

                if (! $src) {
                    return null;
                }

                return [
                    'src' => $src,
                    'alt' => trim((string) ($item['alt'] ?? ($name . ' dashboard image'))),
                ];
            })
            ->filter()
            ->values();
    };

    $hasDevelopmentItems = static function (array $details) use ($looksLikeImageAsset) {
        return collect($details['details'] ?? [])->contains(function ($item) use ($looksLikeImageAsset) {
            if (! is_array($item)) {
                return false;
            }

            $source = trim((string) ($item['src'] ?? ($item['image'] ?? ($item['value'] ?? ''))));

            return $source !== '' && $looksLikeImageAsset($source);
        });
    };

    $hasDashboardItems = static function (array $details) {
        $dashboardPayload = is_array($details['specs'] ?? null) ? ($details['specs'] ?? []) : [];
        $description = trim((string) ($dashboardPayload['description'] ?? ''));
        $hasImages = collect(is_array($dashboardPayload['images'] ?? null) ? ($dashboardPayload['images'] ?? []) : [])
            ->contains(fn ($item) => is_array($item) && filled($item['src'] ?? null));

        return $description !== '' || $hasImages;
    };

    $resolveBrowsers = static function (array $details) {
        $browsers = collect($details['browsers'] ?? [])
            ->filter(fn ($browser) => is_string($browser) && trim($browser) !== '')
            ->map(fn ($browser) => trim($browser))
            ->unique()
            ->values();

        if ($browsers->isNotEmpty()) {
            return $browsers;
        }

        $legacyFromDetails = collect($details['details'] ?? [])
            ->first(function ($item) {
                if (! is_array($item)) {
                    return false;
                }

                return Str::contains(Str::lower(trim((string) ($item['name'] ?? ($item['label'] ?? '')))), ['browser', 'browsers']);
            });

        if (is_array($legacyFromDetails) && filled($legacyFromDetails['value'] ?? null)) {
            return collect(explode(',', (string) $legacyFromDetails['value']))
                ->map(fn ($browser) => trim($browser))
                ->filter()
                ->unique()
                ->values();
        }

        $legacySpecs = $details['specs'] ?? [];
        if (is_array($legacySpecs) && array_is_list($legacySpecs)) {
            $legacyFromSpecs = collect($legacySpecs)->first(function ($item) {
                if (! is_array($item)) {
                    return false;
                }

                return Str::contains(Str::lower(trim((string) ($item['name'] ?? ''))), ['browser', 'browsers']);
            });

            if (is_array($legacyFromSpecs) && filled($legacyFromSpecs['value'] ?? null)) {
                return collect(explode(',', (string) $legacyFromSpecs['value']))
                    ->map(fn ($browser) => trim($browser))
                    ->filter()
                    ->unique()
                    ->values();
            }
        }

        return collect();
    };

    $features = collect($payload['features'] ?? [])
        ->filter(fn ($feature) => is_array($feature) && filled($feature['title'] ?? null))
        ->map(fn ($feature) => [
            'title' => trim((string) ($feature['title'] ?? '')),
            'icon' => trim((string) ($feature['icon'] ?? '')),
        ])
        ->values();

    $gallery = $resolveGalleryItems($payload, fn ($value) => $resolveMediaUrl($value, true), $templateName);

    if ($gallery->isEmpty()) {
        $fallbackTranslationWithGallery = $template->translations
            ->first(function ($candidate) use ($translation) {
                if (! $candidate || ($translation && $candidate->is($translation))) {
                    return false;
                }

                $candidatePayload = is_array($candidate->details)
                    ? $candidate->details
                    : (json_decode($candidate->details ?? '[]', true) ?: []);

                return ! empty($candidatePayload['gallery']) && is_array($candidatePayload['gallery']);
            });

        if ($fallbackTranslationWithGallery) {
            $fallbackPayload = is_array($fallbackTranslationWithGallery->details)
                ? $fallbackTranslationWithGallery->details
                : (json_decode($fallbackTranslationWithGallery->details ?? '[]', true) ?: []);

            $gallery = $resolveGalleryItems($fallbackPayload, fn ($value) => $resolveMediaUrl($value, true), $templateName);
        }
    }

    $developmentTools = $resolveDevelopmentItems($payload, fn ($value) => $resolveMediaUrl($value, true), $looksLikeImageAsset);

    if ($developmentTools->isEmpty()) {
        $fallbackTranslationWithDevelopmentTools = $template->translations
            ->first(function ($candidate) use ($translation, $hasDevelopmentItems) {
                if (! $candidate || ($translation && $candidate->is($translation))) {
                    return false;
                }

                $candidatePayload = is_array($candidate->details)
                    ? $candidate->details
                    : (json_decode($candidate->details ?? '[]', true) ?: []);

                return $hasDevelopmentItems($candidatePayload);
            });

        if ($fallbackTranslationWithDevelopmentTools) {
            $fallbackPayload = is_array($fallbackTranslationWithDevelopmentTools->details)
                ? $fallbackTranslationWithDevelopmentTools->details
                : (json_decode($fallbackTranslationWithDevelopmentTools->details ?? '[]', true) ?: []);

            $developmentTools = $resolveDevelopmentItems($fallbackPayload, fn ($value) => $resolveMediaUrl($value, true), $looksLikeImageAsset);
        }
    }

    $dashboardPayload = is_array($payload['specs'] ?? null) ? ($payload['specs'] ?? []) : [];
    $dashboardDescription = trim((string) ($dashboardPayload['description'] ?? ''));
    $dashboardImages = $resolveDashboardItems($payload, fn ($value) => $resolveMediaUrl($value, true), $templateName);

    if ($dashboardDescription === '' && $dashboardImages->isEmpty()) {
        $fallbackTranslationWithDashboard = $template->translations
            ->first(function ($candidate) use ($translation, $hasDashboardItems) {
                if (! $candidate || ($translation && $candidate->is($translation))) {
                    return false;
                }

                $candidatePayload = is_array($candidate->details)
                    ? $candidate->details
                    : (json_decode($candidate->details ?? '[]', true) ?: []);

                return $hasDashboardItems($candidatePayload);
            });

        if ($fallbackTranslationWithDashboard) {
            $fallbackPayload = is_array($fallbackTranslationWithDashboard->details)
                ? $fallbackTranslationWithDashboard->details
                : (json_decode($fallbackTranslationWithDashboard->details ?? '[]', true) ?: []);

            $fallbackDashboardPayload = is_array($fallbackPayload['specs'] ?? null) ? ($fallbackPayload['specs'] ?? []) : [];
            $dashboardDescription = trim((string) ($fallbackDashboardPayload['description'] ?? ''));
            $dashboardImages = $resolveDashboardItems($fallbackPayload, fn ($value) => $resolveMediaUrl($value, true), $templateName);
        }
    }

    $specs = collect(is_array($payload['specs'] ?? null) && ! array_key_exists('description', $dashboardPayload) && ! array_key_exists('images', $dashboardPayload) ? ($payload['specs'] ?? []) : [])
        ->filter(fn ($item) => is_array($item) && filled($item['name'] ?? null) && filled($item['value'] ?? null))
        ->map(fn ($item) => [
            'name' => trim((string) ($item['name'] ?? '')),
            'value' => trim((string) ($item['value'] ?? '')),
        ])
        ->values();

    $detailsList = collect($payload['details'] ?? [])
        ->filter(function ($item) use ($looksLikeImageAsset) {
            if (! is_array($item)) {
                return false;
            }

            $label = trim((string) ($item['name'] ?? ($item['label'] ?? '')));
            $value = trim((string) ($item['value'] ?? ''));
            $source = trim((string) ($item['src'] ?? ($item['image'] ?? $value)));

            return $label !== '' && $value !== '' && ! $looksLikeImageAsset($source);
        })
        ->map(fn ($item) => [
            'name' => trim((string) ($item['name'] ?? ($item['label'] ?? ''))),
            'value' => trim((string) ($item['value'] ?? '')),
        ])
        ->values();

    $browserList = $resolveBrowsers($payload);

    if ($browserList->isEmpty()) {
        $fallbackTranslationWithBrowsers = $template->translations
            ->first(function ($candidate) use ($translation, $resolveBrowsers) {
                if (! $candidate || ($translation && $candidate->is($translation))) {
                    return false;
                }

                $candidatePayload = is_array($candidate->details)
                    ? $candidate->details
                    : (json_decode($candidate->details ?? '[]', true) ?: []);

                return $resolveBrowsers($candidatePayload)->isNotEmpty();
            });

        if ($fallbackTranslationWithBrowsers) {
            $fallbackPayload = is_array($fallbackTranslationWithBrowsers->details)
                ? $fallbackTranslationWithBrowsers->details
                : (json_decode($fallbackTranslationWithBrowsers->details ?? '[]', true) ?: []);

            $browserList = $resolveBrowsers($fallbackPayload);
        }
    }

    $tags = collect($payload['tags'] ?? [])
        ->filter(fn ($tag) => is_string($tag) && trim($tag) !== '')
        ->map(fn ($tag) => trim($tag))
        ->unique()
        ->values();

    $categoryName = trim((string) ($template->categoryTemplate?->getTranslation(app()->getLocale())?->name
        ?? $template->categoryTemplate?->translations?->first()?->name
        ?? ''));

    $headerTags = collect([$categoryName])
        ->merge($tags)
        ->filter(fn ($tag) => is_string($tag) && trim($tag) !== '')
        ->map(fn ($tag) => trim($tag))
        ->unique()
        ->take(2)
        ->values();

    $productDetailsText = Str::of(strip_tags($translation?->description ?? ''))
        ->replaceMatches('/\s+/', ' ')
        ->trim()
        ->toString();

    if ($productDetailsText === '') {
        $productDetailsText = $shortDesc;
    }

    // Use all gallery items from the admin "gallery" field for the Template Screens section.
    $templateScreens = $gallery->values();
    $dashboardDetail = $detailsList->first(fn ($item) => Str::contains(Str::lower($item['name']), ['dashboard', 'panel']));
    $dashboardImage = $dashboardImages->first()['src'] ?? $gallery->last()['src'] ?? $templateImageUrl;
    $dashboardText = $dashboardDescription !== '' ? $dashboardDescription : ($dashboardDetail['value'] ?? Str::limit($productDetailsText, 220));
    $dashboardGallery = $dashboardImages->slice(1)->values();

    $usedInDevelopmentFallback = $tags->take(6)->values();
    if ($usedInDevelopmentFallback->isEmpty() && $categoryName !== '') {
        $usedInDevelopmentFallback = collect([$categoryName]);
    }

    $lastUpdateValue = $template->updated_at
        ? $template->updated_at->locale(app()->getLocale())->translatedFormat('j F Y')
        : null;

    $featureOne = $features->get(0);
    $featureTwo = $features->get(1);
    $featureThree = $features->get(2);

    $browsersValue = $browserList->isNotEmpty()
        ? $browserList->implode(', ')
        : 'IE11, Firefox, Safari, Opera, Chrome, Edge';

    $sidebarHighlights = collect([
        $featureOne['title'] ?? __('12 months support'),
        $featureTwo['title'] ?? __('Included Dashboard'),
        $featureThree['title'] ?? __('Free upgrades'),
    ])->filter()->take(3)->values();

    $relatedTemplates = TemplateModel::query()
        ->with(['translations', 'categoryTemplate.translations'])
        ->whereKeyNot($template->getKey())
        ->when($template->category_template_id, fn ($query) => $query->where('category_template_id', $template->category_template_id))
        ->latest('id')
        ->take(4)
        ->get();

    if ($relatedTemplates->count() < 4) {
        $relatedTemplates = $relatedTemplates->concat(
            TemplateModel::query()
                ->with(['translations', 'categoryTemplate.translations'])
                ->whereKeyNot($template->getKey())
                ->whereNotIn('id', $relatedTemplates->pluck('id'))
                ->latest('id')
                ->take(4 - $relatedTemplates->count())
                ->get()
        );
    }

    $relatedTemplates = $relatedTemplates
        ->map(function ($item) use ($resolveMediaUrl) {
            $itemTranslation = $item->translation(app()->getLocale()) ?? $item->translations->first();

            if (! $itemTranslation || ! filled($itemTranslation->slug)) {
                return null;
            }

            return [
                'name' => trim((string) ($itemTranslation->name ?? __('Template'))),
                'slug' => $itemTranslation->slug,
                'image' => $resolveMediaUrl($item->image, true),
            ];
        })
        ->filter()
        ->values();

    $templatesSlug = function_exists('page_slug') ? page_slug('templates') : null;
    $templatesPageUrl = $templatesSlug ? route('frontend.page.show', $templatesSlug) : url('/');
    $previewSource = trim((string) ($translation?->preview_url ?? ''));
    $hasValidPreviewSource = $previewSource !== ''
        && (filter_var($previewSource, FILTER_VALIDATE_URL) || Str::startsWith($previewSource, '//'));
    $previewUrl = $hasValidPreviewSource && filled($translation?->slug)
        ? route('template.preview', $translation->slug)
        : null;

    $checkoutUrl = route('checkout.cart', [
        'template_id' => $template->id,
        'review' => 1,
        'domain' => request('domain'),
        'years' => 1,
    ]);

    $finalPriceCents = (int) round($finalPrice * 100);
@endphp

<x-template.layouts.index-layouts
    title="{{ $templateName }} - {{ t('Frontend.Palgoals', 'Palgoals') }}"
    description="{{ $shortDesc }}"
    keywords="templates, web templates, storefront templates"
    ogImage="{{ $templateImageUrl }}"
>
    {{-- Redesign draft. The live route still uses template-show.blade.php. --}}
    <section id="template-single" class="bg-[#F2F2F2] px-4 py-4 sm:px-6 lg:px-12">
        <div class="container mx-auto">
            <p class="animate-from-left mb-8 flex items-center gap-2 text-base capitalize text-gray-dark">
                <a href="{{ url('/') }}" class="flex items-center gap-1 transition-colors hover:text-purple-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14.056" height="11.948" viewBox="0 0 14.056 11.948" aria-hidden="true">
                        <path
                            d="M8.622,16.448V12.231h2.811v4.217h3.514V10.825h2.108L10.028,4.5,3,10.825H5.108v5.622Z"
                            transform="translate(-3 -4.5)"
                            fill="currentColor"
                        />
                    </svg>
                    <span>{{ t('Frontend.Home', 'Home') }}</span>
                </a>
                <span>/</span>
                <a href="{{ $templatesPageUrl }}" class="transition-colors hover:text-purple-brand">{{ t('Frontend.Template', 'Template') }}</a>
            </p>

            <div class="animate-from-right mb-4 flex flex-col items-center justify-between gap-4 py-2 text-center md:flex-row md:items-start md:text-start">
                <div>
                    <h1 class="mb-4 text-3xl font-extrabold uppercase text-purple-brand md:text-4xl lg:text-[40px]">
                        {{ $templateName }}
                    </h1>

                    @if ($headerTags->isNotEmpty())
                        <div class="flex flex-wrap justify-center gap-3 md:justify-start">
                            @foreach ($headerTags as $tag)
                                <span class="rounded-full bg-[#E9E9E9] px-4 py-1.5 text-base capitalize text-[#626262]">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($previewUrl)
                    <a
                        href="{{ $previewUrl }}"
                        target="_blank"
                        rel="noopener"
                        class="rounded-xl bg-red-brand px-6 py-2.5 text-base text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-opacity-90 hover:shadow-lg md:text-xl"
                    >
                        {{ __('Live Preview') }}
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:items-start lg:gap-4">
                <div class="order-1 space-y-10 lg:col-span-8 lg:row-start-1 ltr:lg:col-start-5 rtl:lg:col-start-1">
                    <div class="animate-from-right relative overflow-hidden rounded-[20px] bg-white p-2 md:p-4">
                        <div class="relative">
                            @if ($templateImageUrl)
                                <img
                                    src="{{ $templateImageUrl }}"
                                    class="h-64 w-full rounded-[20px] object-cover md:h-[415px] lg:h-auto"
                                    alt="{{ $templateName }}"
                                >
                            @endif
                        </div>
                    </div>

                    <div class="animate-from-left border-b border-gray-200 pb-8">
                        <h2 class="mb-1 text-xl font-bold text-purple-brand">{{ __('Product details') }}</h2>
                        <p class="text-base leading-relaxed text-purple-brand md:text-xl">
                            {{ $productDetailsText }}
                        </p>
                    </div>

                    @if ($templateScreens->isNotEmpty())
                        <div class="animate-from-right border-b border-gray-200 pb-8">
                            <h2 class="mb-4 text-xl font-bold text-purple-brand">{{ __('Template Screens') }}</h2>
                            <div class="scrollbar-hide flex select-none gap-3 overflow-x-auto pb-2">
                                @foreach ($templateScreens as $screen)
                                    <div class="relative aspect-[3/4] w-64 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                        <div class="absolute inset-0 z-10 transition-colors duration-300"></div>
                                        <img src="{{ $screen['src'] }}" class="h-full w-full object-cover" alt="{{ $screen['alt'] }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($dashboardImage)
                        <div class="animate-from-left border-b border-gray-200 pb-8">
                            <h2 class="mb-1 text-xl font-bold text-purple-brand">{{ __('Dashboard') }}</h2>
                            <p class="mb-4 text-base leading-relaxed text-purple-brand md:text-xl">
                                {{ $dashboardText }}
                            </p>
                            <img src="{{ $dashboardImage }}" class="h-64 w-full rounded-[20px] object-cover md:h-96 lg:h-auto" alt="{{ __('Dashboard Preview') }}">
                            @if ($dashboardGallery->isNotEmpty())
                                <div class="mt-4 flex gap-3 overflow-x-auto pb-2">
                                    @foreach ($dashboardGallery as $image)
                                        <div class="h-32 w-44 shrink-0 overflow-hidden rounded-[20px]">
                                            <img src="{{ $image['src'] }}" class="h-full w-full object-cover" alt="{{ $image['alt'] }}">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($features->isNotEmpty())
                        <div class="animate-from-right border-b border-gray-200 pb-8">
                            <h2 class="mb-1 text-xl font-bold text-purple-brand">{{ __('Features') }}</h2>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                @foreach ($features as $feature)
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 flex-shrink-0 text-red-brand" fill="currentColor" viewBox="0 0 27 21" aria-hidden="true">
                                            <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z" fill="#BA112C" />
                                        </svg>
                                        <span class="text-base text-purple-brand md:text-lg">{{ $feature['title'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($developmentTools->isNotEmpty())
                        <div class="animate-from-left border-b border-gray-200 pb-8">
                            <h2 class="mb-2 text-xl font-bold text-purple-brand">{{ __('Used in development') }}</h2>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($developmentTools as $tool)
                                    <div class="h-18 w-25 shrink-0 overflow-hidden rounded-[20px] transition-all duration-300 hover:-translate-y-1" title="{{ $tool['name'] }}">
                                        <img src="{{ $tool['src'] }}" class="h-full w-full object-cover" alt="{{ $tool['alt'] }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @elseif ($usedInDevelopmentFallback->isNotEmpty())
                        <div class="animate-from-left border-b border-gray-200 pb-8">
                            <h2 class="mb-2 text-xl font-bold text-purple-brand">{{ __('Used in development') }}</h2>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($usedInDevelopmentFallback as $tool)
                                    <div class="flex h-18 min-w-[100px] shrink-0 items-center justify-center rounded-[20px] border border-gray-200 bg-white px-4 text-center transition-all duration-300 hover:-translate-y-1">
                                        <span class="text-sm font-semibold text-purple-brand md:text-base">{{ $tool }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($lastUpdateValue)
                        <div class="animate-from-left border-b border-gray-200 pb-8">
                            <h2 class="mb-2 text-xl font-bold text-purple-brand">{{ __('Last Update') }}</h2>
                            <p class="text-base leading-relaxed text-purple-brand md:text-xl">{{ $lastUpdateValue }}</p>
                        </div>
                    @endif

                    <div class="animate-from-right border-b border-gray-200 pb-8">
                        <h2 class="mb-2 text-xl font-bold text-purple-brand">{{ __('Compatible Browsers') }}</h2>
                        <p class="text-base leading-relaxed text-purple-brand md:text-xl">{{ $browsersValue }}</p>
                    </div>

                    @if ($relatedTemplates->isNotEmpty())
                        <div class="animate-from-right pt-4">
                            <h2 class="mb-3 text-xl font-bold text-purple-brand">{{ __('Other Templates') }}</h2>
                            <div id="other-templates-grid" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                @foreach ($relatedTemplates as $relatedTemplate)
                                    <a
                                        href="{{ route('template.show', $relatedTemplate['slug']) }}"
                                        class="template-card overflow-hidden rounded-[20px] bg-white p-4 shadow-md transition-all duration-300 hover:-translate-y-2 hover:shadow-lg"
                                    >
                                        @if ($relatedTemplate['image'])
                                            <div class="mb-2">
                                                <img
                                                    src="{{ $relatedTemplate['image'] }}"
                                                    class="h-auto w-full rounded-xl object-cover md:h-[165px] md:rounded-[20px]"
                                                    alt="{{ $relatedTemplate['name'] }}"
                                                >
                                            </div>
                                        @endif
                                        <div>
                                            <h3 class="text-lg font-medium text-purple-brand md:text-xl">
                                                {{ $relatedTemplate['name'] }}
                                            </h3>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div id="subscription-sidebar" class="order-2 sticky top-6 z-20 self-start lg:col-span-4 lg:row-start-1 lg:top-24 lg:self-start ltr:lg:col-start-1 rtl:lg:col-start-9">
                    <div
                        class="animate-from-left rounded-[20px] border border-gray-100 bg-white p-6"
                        data-template-price-box
                        data-final-price="{{ $finalPrice }}"
                        data-base-price="{{ $basePrice }}"
                    >
                        <div class="flex items-center justify-between border-b border-gray-200 px-2 pb-4">
                            <h2 class="text-xl font-bold text-purple-brand">{{ __('Subscription price') }}</h2>
                            <div class="text-end" dir="ltr">
                                <p id="template-price-value" class="text-3xl font-bold text-red-brand">${{ number_format($finalPrice, 2) }}</p>
                                @if ($hasDiscount)
                                    <p id="template-original-price" class="text-sm text-slate-400 line-through">${{ number_format($basePrice, 2) }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="my-6 space-y-1 px-2">
                            @foreach ($sidebarHighlights as $highlight)
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20.462" height="15.579" viewBox="0 0 20.462 15.579" aria-hidden="true">
                                        <path
                                            d="M11.61,20.723,6.728,15.841,5.1,17.468l6.51,6.51L25.562,10.028,23.934,8.4Z"
                                            transform="translate(-5.1 -8.4)"
                                            fill="#ba112c"
                                        />
                                    </svg>
                                    <span class="text-base leading-relaxed text-purple-brand md:text-lg">{{ $highlight }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="mb-6 border-t border-gray-200 px-2 pt-4">
                            <p class="mb-3 text-xl font-bold text-purple-brand">{{ __('Period') }}</p>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#E7E7E7] text-purple-brand transition-colors hover:bg-gray-50"
                                    id="period-minus"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17.359" height="3" viewBox="0 0 17.359 3" aria-hidden="true">
                                        <line y2="14.359" transform="translate(15.859 1.5) rotate(90)" stroke="currentColor" stroke-linecap="round" stroke-width="3" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    class="flex flex-1 items-center justify-center rounded-xl border border-[#E7E7E7] bg-[#F1F1F1] px-4 py-3 text-base font-extrabold text-purple-brand"
                                    id="period-dropdown-btn"
                                >
                                    <span id="period-label">1 Year</span>
                                </button>

                                <button
                                    type="button"
                                    class="flex h-12 w-12 items-center justify-center rounded-xl border border-[#E7E7E7] text-purple-brand transition-colors hover:bg-gray-50"
                                    id="period-plus"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="17.359" height="17.359" viewBox="0 0 17.359 17.359" aria-hidden="true">
                                        <g transform="translate(-1138.322 -569.878)">
                                            <line y2="14.359" transform="translate(1147.001 571.378)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="3" />
                                            <line y2="14.359" transform="translate(1154.18 578.558) rotate(90)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="3" />
                                        </g>
                                    </svg>
                                </button>
                            </div>

                            <div id="period-dropdown-menu" class="mt-2 hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
                                @foreach ([1, 2, 3] as $yearOption)
                                    <button
                                        type="button"
                                        class="period-option block w-full px-4 py-3 text-start text-purple-brand hover:bg-gray-100"
                                        data-period="{{ $yearOption }}"
                                    >
                                        {{ $yearOption }} {{ Str::plural('Year', $yearOption) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="px-2">
                            <a
                                href="{{ $checkoutUrl }}"
                                class="block w-full rounded-xl bg-purple-brand py-3 text-center text-lg text-white transition-all duration-300 hover:-translate-y-0.5 hover:bg-opacity-90 hover:shadow-lg"
                                id="buy-now-btn"
                                data-template-id="{{ $template->id }}"
                                data-template-name="{{ $templateName }}"
                                data-price-cents="{{ $finalPriceCents }}"
                                data-base-url="{{ route('checkout.cart') }}"
                                data-domain="{{ request('domain') }}"
                            >
                                {{ __('Buy Now') }}
                            </a>
                        </div>

                        @if ($endsAt)
                            <p class="mt-4 text-center text-xs text-slate-500" dir="ltr">
                                {{ __('Offer ends on') }} {{ $endsAt->locale(app()->getLocale())->translatedFormat('j F Y') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

  
</x-template.layouts.index-layouts>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/gsap.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.7/dist/ScrollTrigger.min.js" defer></script>
<script src="{{ asset('assets/tamplate/js/template-show-redesign.js') }}?v={{ filemtime(public_path('assets/tamplate/js/template-show-redesign.js')) }}" defer></script>
