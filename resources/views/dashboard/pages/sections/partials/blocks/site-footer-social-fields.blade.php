<div class="lg:col-span-2" data-schema-group-label="{{ $contentGroupLabel }}" data-schema-field="copyright"
    data-schema-field-label="{{ $copyrightFieldLabel }}">
    <label class="block text-sm font-medium text-slate-700">{{ $copyrightFieldLabel }}</label>
    <input type="text" name="translations[{{ $code }}][content][copyright]" value="{{ $footerCopyrightValue }}"
        class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900">
    <p class="mt-2 text-xs text-slate-500">
        {{ __('Only the links you fill in will appear in the footer. Leave any network empty to hide it.') }}
    </p>
</div>

<div class="lg:col-span-2 space-y-5" data-schema-group-label="{{ $socialLinksGroupLabel }}">
    @php
        $socialFieldLabels = is_array($socialFieldLabels ?? null) ? $socialFieldLabels : [];
    @endphp
    @include('dashboard.pages.sections.partials.fields.schema-field-renderer', [
        'fieldType' => 'url',
        'label' => (string) ($socialFieldLabels['facebook'] ?? __('Facebook URL')),
        'name' => 'translations[' . $code . '][content][social_links][facebook]',
        'value' => $footerFacebookUrlValue,
        'placeholder' => 'https://facebook.com/your-page',
        'schemaField' => 'social_links.facebook',
        'wrapperClass' => '',
    ])

    @include('dashboard.pages.sections.partials.fields.schema-field-renderer', [
        'fieldType' => 'url',
        'label' => (string) ($socialFieldLabels['instagram'] ?? __('Instagram URL')),
        'name' => 'translations[' . $code . '][content][social_links][instagram]',
        'value' => $footerInstagramUrlValue,
        'placeholder' => 'https://instagram.com/your-page',
        'schemaField' => 'social_links.instagram',
        'wrapperClass' => '',
    ])

    @include('dashboard.pages.sections.partials.fields.schema-field-renderer', [
        'fieldType' => 'url',
        'label' => (string) ($socialFieldLabels['x'] ?? __('X URL')),
        'name' => 'translations[' . $code . '][content][social_links][x]',
        'value' => $footerXUrlValue,
        'placeholder' => 'https://x.com/your-page',
        'schemaField' => 'social_links.x',
        'wrapperClass' => '',
    ])

    @include('dashboard.pages.sections.partials.fields.schema-field-renderer', [
        'fieldType' => 'url',
        'label' => (string) ($socialFieldLabels['github'] ?? __('GitHub URL')),
        'name' => 'translations[' . $code . '][content][social_links][github]',
        'value' => $footerGithubUrlValue,
        'placeholder' => 'https://github.com/your-page',
        'schemaField' => 'social_links.github',
        'wrapperClass' => '',
    ])

    @include('dashboard.pages.sections.partials.fields.schema-field-renderer', [
        'fieldType' => 'url',
        'label' => (string) ($socialFieldLabels['youtube'] ?? __('YouTube URL')),
        'name' => 'translations[' . $code . '][content][social_links][youtube]',
        'value' => $footerYoutubeUrlValue,
        'placeholder' => 'https://youtube.com/@your-channel',
        'schemaField' => 'social_links.youtube',
        'wrapperClass' => '',
    ])
</div>
