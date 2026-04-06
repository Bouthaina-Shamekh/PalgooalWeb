<div class="lg:col-span-2" data-schema-group-label="{{ $contentGroupLabel }}"
    data-schema-field="copyright" data-schema-field-label="{{ $copyrightFieldLabel }}">
    <label class="block text-sm font-medium text-slate-700">{{ $copyrightFieldLabel }}</label>
    <input type="text" name="translations[{{ $code }}][content][copyright]"
        value="{{ $footerCopyrightValue }}"
        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
    <p class="mt-2 text-xs text-slate-500">
        {{ __('Only the links you fill in will appear in the footer. Leave any network empty to hide it.') }}
    </p>
</div>

<div class="lg:col-span-2 space-y-5"
    data-schema-group-label="{{ $socialLinksGroupLabel }}">
    @php
        $footerFacebookUrlFieldContext = $schemaFieldContext(
            'social',
            'social_links.facebook',
            __('Facebook URL'),
            'https://facebook.com/your-page',
        );
        $footerFacebookUrlRenderConfig = $schemaRenderableFieldConfig(
            $footerFacebookUrlFieldContext,
            'url',
            3,
        );
    @endphp
    @include(
        'dashboard.pages.sections.partials.fields.schema-field-renderer',
        $schemaRendererPayload(
            $footerFacebookUrlRenderConfig,
            'translations[' . $code . '][content][social_links][facebook]',
            $footerFacebookUrlValue,
            'social_links.facebook',
        )
    )

    @php
        $footerInstagramUrlFieldContext = $schemaFieldContext(
            'social',
            'social_links.instagram',
            __('Instagram URL'),
            'https://instagram.com/your-page',
        );
        $footerInstagramUrlRenderConfig = $schemaRenderableFieldConfig(
            $footerInstagramUrlFieldContext,
            'url',
            3,
        );
    @endphp

    @include(
        'dashboard.pages.sections.partials.fields.schema-field-renderer',
        $schemaRendererPayload(
            $footerInstagramUrlRenderConfig,
            'translations[' . $code . '][content][social_links][instagram]',
            $footerInstagramUrlValue,
            'social_links.instagram',
        )
    )

    @php
        $footerXUrlFieldContext = $schemaFieldContext(
            'social',
            'social_links.x',
            __('X URL'),
            'https://x.com/your-page',
        );
        $footerXUrlRenderConfig = $schemaRenderableFieldConfig(
            $footerXUrlFieldContext,
            'url',
            3,
        );
    @endphp

    @include(
        'dashboard.pages.sections.partials.fields.schema-field-renderer',
        $schemaRendererPayload(
            $footerXUrlRenderConfig,
            'translations[' . $code . '][content][social_links][x]',
            $footerXUrlValue,
            'social_links.x',
        )
    )

    @php
        $footerGithubUrlFieldContext = $schemaFieldContext(
            'social',
            'social_links.github',
            __('GitHub URL'),
            'https://github.com/your-page',
        );
        $footerGithubUrlRenderConfig = $schemaRenderableFieldConfig(
            $footerGithubUrlFieldContext,
            'url',
            3,
        );
    @endphp

    @include(
        'dashboard.pages.sections.partials.fields.schema-field-renderer',
        $schemaRendererPayload(
            $footerGithubUrlRenderConfig,
            'translations[' . $code . '][content][social_links][github]',
            $footerGithubUrlValue,
            'social_links.github',
        )
    )

    @php
        $footerYoutubeUrlFieldContext = $schemaFieldContext(
            'social',
            'social_links.youtube',
            __('YouTube URL'),
            'https://youtube.com/@your-channel',
        );
        $footerYoutubeUrlRenderConfig = $schemaRenderableFieldConfig(
            $footerYoutubeUrlFieldContext,
            'url',
            3,
        );
    @endphp

    @include(
        'dashboard.pages.sections.partials.fields.schema-field-renderer',
        $schemaRendererPayload(
            $footerYoutubeUrlRenderConfig,
            'translations[' . $code . '][content][social_links][youtube]',
            $footerYoutubeUrlValue,
            'social_links.youtube',
        )
    )
</div>
