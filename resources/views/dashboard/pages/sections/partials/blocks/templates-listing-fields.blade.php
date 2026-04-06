<div class="lg:col-span-2 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700">{{ __('Templates Source') }}</label>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('This section builds the templates archive from the Templates module automatically. Use these fields only for the breadcrumb text, filter labels, card button labels, and items shown per page.') }}
            </p>
        </div>
        <a href="{{ route('dashboard.templates.index') }}"
            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
            <i class="ti ti-layout-grid text-base leading-none" aria-hidden="true"></i>
            <span>{{ __('Open Templates') }}</span>
        </a>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5">
        @php
            $breadcrumbLabelFieldContext = $schemaFieldContext(
                'content',
                'breadcrumb_label',
                __('Breadcrumb Label'),
                t('dashboard.templates', 'Templates'),
            );
            $allCategoriesLabelFieldContext = $schemaFieldContext(
                'content',
                'all_categories_label',
                __('All Categories Label'),
                t('dashboard.all_categories', 'All Categories'),
            );
            $typeLabelFieldContext = $schemaFieldContext(
                'content',
                'type_label',
                t('dashboard.type', 'Type'),
                null,
            );
            $bestSellersLabelFieldContext = $schemaFieldContext(
                'content',
                'best_sellers_label',
                __('Best Sellers Filter Label'),
                t('dashboard.best_sellers', 'Best Sellers'),
            );
            $priceLabelFieldContext = $schemaFieldContext(
                'content',
                'price_label',
                t('dashboard.price', 'Price'),
                null,
            );
            $typeLabelRenderConfig = $schemaRenderableFieldConfig(
                $typeLabelFieldContext,
                'text',
                3,
            );
            $bestSellersLabelRenderConfig = $schemaRenderableFieldConfig(
                $bestSellersLabelFieldContext,
                'text',
                3,
            );
            $priceLabelRenderConfig = $schemaRenderableFieldConfig(
                $priceLabelFieldContext,
                'text',
                3,
            );
            $breadcrumbLabelRenderConfig = $schemaRenderableFieldConfig(
                $breadcrumbLabelFieldContext,
                'text',
                3,
            );
            $allCategoriesLabelRenderConfig = $schemaRenderableFieldConfig(
                $allCategoriesLabelFieldContext,
                'text',
                3,
            );
            $buyLabelFieldContext = $schemaFieldContext(
                'content',
                'buy_label',
                __('Buy Button Label'),
                t('dashboard.buy_now', 'Buy Now'),
            );
            $previewLabelFieldContext = $schemaFieldContext(
                'content',
                'preview_label',
                __('Preview Button Label'),
                t('dashboard.live_preview', 'Live Preview'),
            );
            $buyLabelRenderConfig = $schemaRenderableFieldConfig(
                $buyLabelFieldContext,
                'text',
                3,
            );
            $previewLabelRenderConfig = $schemaRenderableFieldConfig(
                $previewLabelFieldContext,
                'text',
                3,
            );
        @endphp

        {{--
        This block uses schema-driven rendering.

        All simple fields below follow the standardized pipeline:
        schemaFieldContext أ¢â€ â€™ schemaRenderableFieldConfig أ¢â€ â€™ schemaRendererPayload

        Use this pattern for simple text and URL fields.
        Do not mix with manual includes unless the field requires special handling.
        --}}

        @include(
            'dashboard.pages.sections.partials.fields.schema-field-renderer',
            $schemaRendererPayload(
                $breadcrumbLabelRenderConfig,
                'translations[' . $code . '][content][breadcrumb_label]',
                $templatesListingBreadcrumbLabelValue,
                'breadcrumb_label',
                'lg:col-span-2',
            )
        )

        @include(
            'dashboard.pages.sections.partials.fields.schema-field-renderer',
            $schemaRendererPayload(
                $allCategoriesLabelRenderConfig,
                'translations[' . $code . '][content][all_categories_label]',
                $templatesListingAllCategoriesLabelValue,
                'all_categories_label',
                'lg:col-span-2',
            )
        )

        @include(
            'dashboard.pages.sections.partials.fields.schema-field-renderer',
            $schemaRendererPayload(
                $typeLabelRenderConfig,
                'translations[' . $code . '][content][type_label]',
                $templatesListingTypeLabelValue,
                'type_label',
                'lg:col-span-2',
            )
        )

        @include(
            'dashboard.pages.sections.partials.fields.schema-field-renderer',
            $schemaRendererPayload(
                $bestSellersLabelRenderConfig,
                'translations[' . $code . '][content][best_sellers_label]',
                $templatesListingBestSellersLabelValue,
                'best_sellers_label',
                'lg:col-span-2',
            )
        )

        @include(
            'dashboard.pages.sections.partials.fields.schema-field-renderer',
            $schemaRendererPayload(
                $priceLabelRenderConfig,
                'translations[' . $code . '][content][price_label]',
                $templatesListingPriceLabelValue,
                'price_label',
                'lg:col-span-2',
            )
        )

        @include(
            'dashboard.pages.sections.partials.fields.schema-field-renderer',
            $schemaRendererPayload(
                $buyLabelRenderConfig,
                'translations[' . $code . '][content][buy_label]',
                $templatesListingBuyLabelValue,
                'buy_label',
                'lg:col-span-2',
            )
        )

        @include(
            'dashboard.pages.sections.partials.fields.schema-field-renderer',
            $schemaRendererPayload(
                $previewLabelRenderConfig,
                'translations[' . $code . '][content][preview_label]',
                $templatesListingPreviewLabelValue,
                'preview_label',
                'lg:col-span-2',
            )
        )

        <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-slate-700">{{ __('Items Per Page') }}</label>
            <input type="number" min="1" name="translations[{{ $code }}][content][items_per_page]"
                value="{{ $templatesListingItemsPerPageValue }}"
                class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900"
                placeholder="12">
            <p class="mt-2 text-xs text-slate-500">
                {{ __('This controls how many template cards appear before the pagination switches to the next page.') }}
            </p>
        </div>
    </div>
</div>
