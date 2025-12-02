@php
    use App\Models\Media;

    /** @var \App\Models\Page|null $page */
    /** @var \Illuminate\Support\Collection|\App\Models\Language[] $languages */

    // Are we editing an existing page or creating a new one?
    $isEdit        = isset($page) && $page?->exists;
    $defaultStatus = $isEdit ? (int) $page->is_active : 1;  // 1 = Published, 0 = Draft
    $defaultIsHome = $isEdit ? (int) $page->is_home   : 0;  // 1 = Homepage
@endphp

{{-- ===========================
     LEFT COLUMN: PAGE CONTENT
   =========================== --}}
<div class="col-span-2">
    <div class="card p-6 space-y-6">
        <h2 class="text-lg font-bold">
            {{ $isEdit ? t('dashboard.Edit_Page', 'Edit Page') : t('dashboard.Add_Page', 'Add Page') }}
        </h2>

        {{-- -------------------------
             Language tabs (buttons)
           ------------------------- --}}
        <div>
            <ul class="flex border-b mb-4 space-x-2 rtl:space-x-reverse" role="tablist">
                @foreach ($languages as $index => $lang)
                    @php
                        /** @var \App\Models\Language $lang */
                        $langCode    = $lang->code;
                        $isActiveTab = $index === 0; // first language is active by default
                    @endphp

                    <li>
                        <button
                            type="button"
                            class="px-4 py-2 rounded-t transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400
                                {{ $isActiveTab
                                    ? 'bg-white text-slate-900 shadow-sm border border-slate-200 border-b-white font-semibold'
                                    : 'bg-slate-100 text-slate-500 hover:bg-slate-200 border border-transparent' }}"
                            data-lang-tab="{{ $langCode }}"
                            role="tab"
                            aria-controls="lang-panel-{{ $langCode }}"
                            aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                            id="lang-tab-{{ $langCode }}"
                        >
                            {{ $lang->name }}
                        </button>
                    </li>
                @endforeach
            </ul>

            {{-- -------------------------
                 Language panels (fields)
               ------------------------- --}}
            @foreach ($languages as $index => $lang)
                @php
                    /** @var \App\Models\Language $lang */
                    $langCode = $lang->code;

                    // Existing translation for this locale (edit mode only)
                    $existingTranslation = $isEdit
                        ? $page->translations->firstWhere('locale', $langCode)
                        : null;

                    // Values with old() fallback (for validation errors)
                    $titleValue = old("translations.$langCode.title", $existingTranslation->title ?? '');
                    $slugValue  = old("translations.$langCode.slug",  $existingTranslation->slug  ?? '');

                    $contentValue = old(
                        "translations.$langCode.content",
                        $existingTranslation->content ?? ''
                    );

                    $metaTitleValue = old(
                        "translations.$langCode.meta_title",
                        $existingTranslation->meta_title ?? ''
                    );

                    $metaDescriptionValue = old(
                        "translations.$langCode.meta_description",
                        $existingTranslation->meta_description ?? ''
                    );

                    $metaKeywordsValue = old(
                        "translations.$langCode.meta_keywords",
                        is_array($existingTranslation?->meta_keywords ?? null)
                            ? implode(',', $existingTranslation->meta_keywords)
                            : ($existingTranslation->meta_keywords ?? '')
                    );

                    // OG value in DB (could be media_id or direct URL, we normalize below)
                    $storedOg = old(
                        "translations.$langCode.og_image",
                        $existingTranslation->og_image ?? null
                    );

                    $ogImageId  = null; // media ID (if used)
                    $ogImageUrl = null; // final image URL for preview

                    if (is_numeric($storedOg)) {
                        // Case A: we stored a Media ID from our Media Library
                        $media = Media::find((int) $storedOg);

                        if ($media) {
                            $ogImageId = $media->id;
                            // Adjust this to match your Media model accessors / columns
                            $ogImageUrl = $media->url ?? ($media->file_url ?? null);
                        }
                    } elseif (is_string($storedOg) && $storedOg !== '') {
                        // Case B: we stored a direct URL
                        $ogImageUrl = $storedOg;
                    }

                    // Prepare preview array for the media-picker component
                    $previewUrls = $ogImageUrl ? [$ogImageUrl] : [];

                    $isActivePanel = $index === 0; // first language panel visible by default
                @endphp

                <div
                    id="lang-panel-{{ $langCode }}"
                    class="{{ $isActivePanel ? '' : 'hidden' }}"
                    data-lang-panel="{{ $langCode }}"
                    role="tabpanel"
                    aria-labelledby="lang-tab-{{ $langCode }}"
                >
                    {{-- Hidden locale (always required in request) --}}
                    <input
                        type="hidden"
                        name="translations[{{ $langCode }}][locale]"
                        value="{{ $langCode }}"
                    >

                    {{-- Existing translation ID (edit mode) --}}
                    @if ($isEdit && $existingTranslation)
                        <input
                            type="hidden"
                            name="translations[{{ $langCode }}][id]"
                            value="{{ $existingTranslation->id }}"
                        >
                    @endif

                    <div class="space-y-4">
                        {{-- Page Title --}}
                        <div>
                            <label class="block mb-1 font-semibold">
                                {{ t('dashboard.Page_Title', 'Page Title') }} ({{ $langCode }})
                            </label>
                            <input
                                type="text"
                                name="translations[{{ $langCode }}][title]"
                                class="w-full border p-2 rounded mb-1"
                                placeholder="{{ t('dashboard.Page_Title', 'Page Title') }}"
                                value="{{ $titleValue }}"
                                data-slug-source="{{ $langCode }}" {{-- used to auto-generate slug --}}
                            >
                            @error("translations.$langCode.title")
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Slug --}}
                        <div>
                            <label class="block mb-1 font-semibold">
                                Slug ({{ $langCode }})
                            </label>
                            <input
                                type="text"
                                name="translations[{{ $langCode }}][slug]"
                                class="w-full border p-2 rounded mb-1"
                                placeholder="page-slug"
                                value="{{ $slugValue }}"
                                data-slug-input
                                data-lang="{{ $langCode }}"
                            >
                            <p class="text-xs text-gray-500">
                                {{ __('Spaces and underscores will be converted to dashes (-). We keep letters, numbers, and dashes only (supports Arabic & English).') }}
                            </p>
                            @error("translations.$langCode.slug")
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Page Content (WYSIWYG) --}}
                        <div>
                            <label class="block mb-1 font-semibold">
                                {{ t('dashboard.Page_Content', 'Page Content') }} ({{ $langCode }})
                            </label>
                            <textarea
                                name="translations[{{ $langCode }}][content]"
                                class="w-full border rounded h-32 js-page-content-editor"
                                data-wysiwyg="page-content"
                                data-lang="{{ $langCode }}"
                                placeholder="{{ t('dashboard.Page_Content', 'Page Content') }}"
                            >{{ $contentValue }}</textarea>
                            @error("translations.$langCode.content")
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                            <p class="text-xs text-gray-500 mt-1">
                                {{ __('This field uses a rich-text editor. HTML will be saved in the database as the content value.') }}
                            </p>
                        </div>

                        {{-- ======================
                             SEO META BLOCK
                           ====================== --}}
                        <div class="border-t pt-4 space-y-3">
                            <h3 class="text-sm font-semibold text-gray-600">
                                {{ t('dashboard.SEO_Meta', 'SEO Meta') }}
                            </h3>

                            {{-- Meta Title --}}
                            <div>
                                <label class="block mb-1 font-semibold">
                                    {{ t('dashboard.Meta_Title', 'Meta Title') }} ({{ $langCode }})
                                </label>
                                <input
                                    type="text"
                                    name="translations[{{ $langCode }}][meta_title]"
                                    class="w-full border p-2 rounded mb-1"
                                    placeholder="{{ t('dashboard.Meta_Title', 'Meta Title') }}"
                                    value="{{ $metaTitleValue }}"
                                >
                                @error("translations.$langCode.meta_title")
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Meta Description --}}
                            <div>
                                <label class="block mb-1 font-semibold">
                                    {{ t('dashboard.Meta_Description', 'Meta Description') }} ({{ $langCode }})
                                </label>
                                <textarea
                                    name="translations[{{ $langCode }}][meta_description]"
                                    class="w-full border p-2 rounded h-24"
                                    placeholder="{{ t('dashboard.Short_description_for_search_engines', 'Short description for search engines') }}"
                                >{{ $metaDescriptionValue }}</textarea>
                                <p class="text-xs text-gray-500">
                                    {{ t('dashboard.Aim_for_50_160_characters_Leave_empty_to_reuse_the_title', 'Aim for 50-160 characters. Leave empty to reuse the title.') }}
                                </p>
                                @error("translations.$langCode.meta_description")
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Meta Keywords --}}
                            <div>
                                <label class="block mb-1 font-semibold">
                                    {{ t('dashboard.Meta_Keywords', 'Meta Keywords') }} ({{ $langCode }})
                                </label>
                                <input
                                    type="text"
                                    name="translations[{{ $langCode }}][meta_keywords]"
                                    class="w-full border p-2 rounded mb-1"
                                    placeholder="keyword-1, keyword-2"
                                    value="{{ $metaKeywordsValue }}"
                                >
                                <p class="text-xs text-gray-500">
                                    {{ t('dashboard.Separate_keywords_with_a_comma_or_Arabic_comma', 'Separate keywords with a comma or Arabic comma.') }}
                                </p>
                                @error("translations.$langCode.meta_keywords")
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Open Graph Image (Media Library Picker) --}}
                            <x-dashboard.media-picker
                                :name="'translations['.$langCode.'][og_image]'" {{-- matches request()->input() structure --}}
                                :label="__('Open Graph Image URL').' ('.$langCode.')'"
                                :value="$ogImageId ?? $ogImageUrl"            {{-- prefer Media ID, fallback to direct URL --}}
                                :preview-urls="$previewUrls"                  {{-- initial preview (edit mode) --}}
                                :multiple="false"                             {{-- one OG image per locale --}}
                                class="mt-4"
                            />
                        </div> {{-- /SEO Meta --}}
                    </div> {{-- /.space-y-4 --}}
                </div> {{-- /language panel --}}
            @endforeach
        </div>
    </div>
</div>

{{-- ===========================
     RIGHT COLUMN: PUBLISHING
   =========================== --}}
<div class="space-y-6">
    <div class="card p-4 space-y-4">
        <h3 class="font-semibold">
            {{ t('dashboard.Publishing_Options', 'Publishing Options') }}
        </h3>

        {{-- Status (draft / published) --}}
        <div>
            <label class="block font-semibold mb-1">
                {{ t('dashboard.Status', 'Status') }}
            </label>
            @php
                $statusOld = old('is_active', $defaultStatus);
            @endphp

            <label class="flex items-center gap-2">
                <input
                    type="radio"
                    name="is_active"
                    value="0"
                    class="form-radio"
                    {{ (string) $statusOld === '0' ? 'checked' : '' }}
                >
                <span>{{ t('dashboard.Draft', 'Draft') }}</span>
            </label>

            <label class="flex items-center gap-2">
                <input
                    type="radio"
                    name="is_active"
                    value="1"
                    class="form-radio"
                    {{ (string) $statusOld === '1' ? 'checked' : '' }}
                >
                <span>{{ t('dashboard.Published', 'Published') }}</span>
            </label>
        </div>

        {{-- Homepage flag --}}
        <div>
            <label class="block font-semibold mb-1">
                {{ t('dashboard.Homepage', 'Homepage') }}
            </label>
            @php
                $isHomeOld = old('is_home', $defaultIsHome);
            @endphp

            <label class="flex items-center gap-2">
                <input
                    type="checkbox"
                    name="is_home"
                    value="1"
                    class="form-checkbox"
                    {{ (string) $isHomeOld === '1' ? 'checked' : '' }}
                >
                <span>{{ t('dashboard.Make_Homepage', 'Make Homepage') }}</span>
            </label>

            <p class="text-xs text-gray-500 mt-1">
                {{ __('If enabled, this page will be used as the main marketing homepage.') }}
            </p>
        </div>

        {{-- Publish Date --}}
        <div>
            <label class="block font-semibold mb-1">
                {{ t('dashboard.Publish_Date', 'Publish Date') }}
            </label>
            @php
                $publishedAtOld = old(
                    'published_at',
                    $isEdit && $page->published_at
                        ? $page->published_at->format('Y-m-d\TH:i')
                        : ''
                );
            @endphp

            <input
                type="datetime-local"
                name="published_at"
                class="w-full border p-2 rounded"
                value="{{ $publishedAtOld }}"
            >
            @error('published_at')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        {{-- Submit button --}}
        <button
            type="submit"
            class="w-full inline-flex items-center justify-center gap-2 rounded bg-primary py-2 text-sm font-semibold text-white transition hover:bg-primary/80 focus:outline-none focus:ring-2 focus:ring-primary/40 focus:ring-offset-2 disabled:opacity-70 dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:focus:ring-offset-slate-900"
        >
            {{ $isEdit ? t('dashboard.Update', 'Update') : t('dashboard.Publish', 'Publish') }}
        </button>
    </div>
</div>


    {{-- CKEditor 5 CDN (rich-text WYSIWYG editor for page content) --}}
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /**
             * --------------------------------------------------------------
             * Language tabs (pure vanilla JS)
             * --------------------------------------------------------------
             */
            const tabs   = document.querySelectorAll('[data-lang-tab]');
            const panels = document.querySelectorAll('[data-lang-panel]');

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const lang = tab.getAttribute('data-lang-tab');

                    // Reset all tabs to inactive state
                    tabs.forEach(function (t) {
                        t.classList.remove(
                            'bg-white',
                            'text-slate-900',
                            'shadow-sm',
                            'border',
                            'border-slate-200',
                            'border-b-white',
                            'font-semibold'
                        );
                        t.classList.add(
                            'bg-slate-100',
                            'text-slate-500',
                            'border-transparent'
                        );
                    });

                    // Mark clicked tab as active
                    tab.classList.remove('bg-slate-100', 'text-slate-500', 'border-transparent');
                    tab.classList.add(
                        'bg-white',
                        'text-slate-900',
                        'shadow-sm',
                        'border',
                        'border-slate-200',
                        'border-b-white',
                        'font-semibold'
                    );

                    // Show only the matching panel
                    panels.forEach(function (panel) {
                        if (panel.getAttribute('data-lang-panel') === lang) {
                            panel.classList.remove('hidden');
                        } else {
                            panel.classList.add('hidden');
                        }
                    });
                });
            });

            /**
             * --------------------------------------------------------------
             * Slug normalizer (Unicode-friendly: Arabic + English)
             * --------------------------------------------------------------
             */
            function normalizeSlug(value) {
                if (!value) return '';

                value = value.trim();
                value = value.toLowerCase();                 // lowercase ASCII
                value = value.replace(/[\s_]+/g, '-');       // spaces & underscores -> dash
                value = value.replace(/[ـ]+/g, '');          // remove Arabic tatweel
                value = value.replace(/[^\p{L}\p{N}-]+/gu, ''); // keep letters, digits, dash
                value = value.replace(/-+/g, '-');           // collapse multiple dashes
                value = value.replace(/^-+|-+$/g, '');       // trim dashes at edges

                return value;
            }

            /**
             * --------------------------------------------------------------
             * Slug inputs (manual editing)
             * --------------------------------------------------------------
             */
            const slugInputs = document.querySelectorAll('[data-slug-input]');

            slugInputs.forEach(function (input) {
                input.addEventListener('input', function () {
                    const caretPos   = input.selectionStart;
                    const normalized = normalizeSlug(input.value);
                    input.value      = normalized;

                    try {
                        input.setSelectionRange(caretPos, caretPos);
                    } catch (e) {
                        // Ignore if browser does not support setSelectionRange
                    }
                });

                input.addEventListener('change', function () {
                    if (input.value !== '') {
                        // Mark slug as manually edited -> stop auto sync from title
                        input.dataset.touched = '1';
                    }
                });
            });

            /**
             * --------------------------------------------------------------
             * Auto-generate slug from title (per language)
             * --------------------------------------------------------------
             */
            const titleInputs = document.querySelectorAll('[data-slug-source]');

            titleInputs.forEach(function (titleInput) {
                titleInput.addEventListener('input', function () {
                    const lang      = titleInput.dataset.slugSource;
                    const slugInput = document.querySelector(
                        '[data-slug-input][data-lang="' + lang + '"]'
                    );

                    if (!slugInput) return;

                    // If user already edited slug manually, do not override it
                    if (slugInput.dataset.touched === '1') {
                        return;
                    }

                    slugInput.value = normalizeSlug(titleInput.value);
                });
            });

            /**
             * --------------------------------------------------------------
             * Initialize CKEditor 5 for all page-content textareas
             * --------------------------------------------------------------
             * - Targets: <textarea data-wysiwyg="page-content" ...>
             * - Each language panel has its own editor instance.
             * - Editor writes HTML back to the original <textarea>,
             *   so Laravel receives the value in the request.
             * --------------------------------------------------------------
             */
            const contentTextareas = document.querySelectorAll('textarea[data-wysiwyg="page-content"]');

            contentTextareas.forEach(function (textarea) {
                ClassicEditor
                    .create(textarea, {
                        toolbar: [
                            'heading', '|',
                            'bold', 'italic', 'link',
                            'bulletedList', 'numberedList', 'blockQuote', '|',
                            'undo', 'redo'
                        ],
                        language: document.documentElement.lang || 'ar',
                    })
                    .catch(error => {
                        console.error('CKEditor initialization error:', error);
                    });
            });
        });
    </script>

