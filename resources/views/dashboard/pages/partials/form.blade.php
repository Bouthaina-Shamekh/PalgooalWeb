@php
    use App\Models\Media;

    /** @var \App\Models\Page|null $page */
    /** @var \Illuminate\Support\Collection|\App\Models\Language[] $languages */

    $isEdit        = isset($page) && $page?->exists;
    $defaultStatus = $isEdit ? (int) $page->is_active : 1;
    $defaultIsHome = $isEdit ? (int) $page->is_home   : 0;

    $defaultBuilderMode = 'sections';
@endphp

@push('styles')
<style>
/* ── Language Switcher ───────────────────────── */
.lang-switcher {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    padding: 4px;
    background: #f1f5f9;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.lang-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border: none;
    border-radius: 9px;
    background: transparent;
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all .18s ease;
    white-space: nowrap;
    line-height: 1.4;
}

.lang-tab-btn:hover {
    background: #fff;
    color: #334155;
    box-shadow: 0 1px 4px rgba(0,0,0,.08);
}

.lang-tab-btn.active {
    background: #fff;
    color: var(--bs-primary, #4f46e5);
    box-shadow: 0 2px 8px rgba(0,0,0,.10);
    font-weight: 600;
}

.lang-flag {
    font-size: 16px;
    line-height: 1;
}

.lang-name {
    font-size: 13px;
}

.lang-code {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .5px;
    color: inherit;
    opacity: .55;
    background: currentColor;
    -webkit-background-clip: text;
}

.lang-tab-btn.active .lang-code {
    opacity: .4;
}

/* validation error indicator on tab */
.lang-tab-btn.has-error {
    color: #dc2626 !important;
}
.lang-tab-btn.has-error.active {
    background: #fef2f2;
    box-shadow: 0 2px 8px rgba(220,38,38,.15);
}
</style>
@endpush

{{-- ============================
     العمود الرئيسي (col-span-8)
     ============================ --}}
<div class="col-span-12 xl:col-span-8">

    {{-- ── القسم ١: محتوى الصفحة ── --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                  style="width:26px;height:26px;font-size:13px;">١</span>
            <h5 class="mb-0">{{ t('dashboard.Page_Content', 'محتوى الصفحة') }}</h5>
        </div>
        <div class="card-body">

            {{-- تبويبات اللغات --}}
            <div class="lang-switcher mb-4" role="tablist">
                @foreach ($languages as $index => $lang)
                    @php
                        /** @var \App\Models\Language $lang */
                        $langCode    = $lang->code;
                        $isActiveTab = $index === 0;
                        // خريطة أعلام اللغات
                        $flags = ['ar' => '🇸🇦', 'en' => '🇬🇧', 'fr' => '🇫🇷', 'de' => '🇩🇪', 'tr' => '🇹🇷', 'es' => '🇪🇸'];
                        $flag  = $flags[$langCode] ?? '🌐';
                    @endphp
                    <button
                        type="button"
                        class="lang-tab-btn {{ $isActiveTab ? 'active' : '' }}"
                        data-lang-tab="{{ $langCode }}"
                        role="tab"
                        aria-controls="lang-panel-{{ $langCode }}"
                        aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                        id="lang-tab-{{ $langCode }}"
                    >
                        <span class="lang-flag">{{ $flag }}</span>
                        <span class="lang-name">{{ $lang->name }}</span>
                        <span class="lang-code">{{ strtoupper($langCode) }}</span>
                    </button>
                @endforeach
            </div>

            {{-- ألواح اللغات --}}
            @foreach ($languages as $index => $lang)
                @php
                    /** @var \App\Models\Language $lang */
                    $langCode = $lang->code;

                    $existingTranslation = $isEdit
                        ? $page->translations->firstWhere('locale', $langCode)
                        : null;

                    $titleValue       = old("translations.$langCode.title",        $existingTranslation->title        ?? '');
                    $slugValue        = old("translations.$langCode.slug",         $existingTranslation->slug         ?? '');
                    $contentValue     = old("translations.$langCode.content",      $existingTranslation->content      ?? '');
                    $metaTitleValue   = old("translations.$langCode.meta_title",   $existingTranslation->meta_title   ?? '');
                    $metaDescValue    = old("translations.$langCode.meta_description", $existingTranslation->meta_description ?? '');
                    $metaKeywordsValue = old(
                        "translations.$langCode.meta_keywords",
                        is_array($existingTranslation?->meta_keywords ?? null)
                            ? implode(',', $existingTranslation->meta_keywords)
                            : ($existingTranslation->meta_keywords ?? '')
                    );

                    $storedOg   = old("translations.$langCode.og_image", $existingTranslation->og_image ?? null);
                    $ogImageId  = null;
                    $ogImageUrl = null;

                    if (is_numeric($storedOg)) {
                        $media = Media::find((int) $storedOg);
                        if ($media) {
                            $ogImageId  = $media->id;
                            $ogImageUrl = $media->url ?? ($media->file_url ?? null);
                        }
                    } elseif (is_string($storedOg) && $storedOg !== '') {
                        $ogImageUrl = $storedOg;
                    }

                    $previewUrls = $ogImageUrl ? [$ogImageUrl] : [];
                    $isActivePanel = $index === 0;
                @endphp

                <div
                    id="lang-panel-{{ $langCode }}"
                    class="{{ $isActivePanel ? '' : 'hidden' }}"
                    data-lang-panel="{{ $langCode }}"
                    role="tabpanel"
                    aria-labelledby="lang-tab-{{ $langCode }}"
                >
                    <input type="hidden" name="translations[{{ $langCode }}][locale]" value="{{ $langCode }}">

                    @if ($isEdit && $existingTranslation)
                        <input type="hidden" name="translations[{{ $langCode }}][id]" value="{{ $existingTranslation->id }}">
                    @endif

                    {{-- عنوان الصفحة --}}
                    <div class="mb-3">
                        <label class="form-label">
                            {{ t('dashboard.Page_Title', 'عنوان الصفحة') }}
                            <span class="badge bg-light text-muted ms-1 fw-normal">{{ strtoupper($langCode) }}</span>
                        </label>
                        <input
                            type="text"
                            name="translations[{{ $langCode }}][title]"
                            class="form-control @error("translations.$langCode.title") is-invalid @enderror"
                            placeholder="{{ t('dashboard.Page_Title', 'عنوان الصفحة') }}"
                            value="{{ $titleValue }}"
                            data-slug-source="{{ $langCode }}"
                        >
                        @error("translations.$langCode.title")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- الـ Slug --}}
                    <div class="mb-3">
                        <label class="form-label">
                            {{ t('dashboard.Slug', 'الرابط (Slug)') }}
                            <span class="badge bg-light text-muted ms-1 fw-normal">{{ strtoupper($langCode) }}</span>
                        </label>
                        <input
                            type="text"
                            name="translations[{{ $langCode }}][slug]"
                            class="form-control font-mono @error("translations.$langCode.slug") is-invalid @enderror"
                            dir="ltr"
                            placeholder="page-slug"
                            value="{{ $slugValue }}"
                            data-slug-input
                            data-lang="{{ $langCode }}"
                        >
                        <div class="form-text">
                            {{ t('dashboard.Slug_Hint', 'المسافات والشرطات السفلية تتحول إلى شرطات (-). يُقبل الحروف والأرقام والشرطات فقط.') }}
                        </div>
                        @error("translations.$langCode.slug")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- محتوى الصفحة --}}
                    <div class="mb-3">
                        <label class="form-label">
                            {{ t('dashboard.Page_Content', 'محتوى الصفحة') }}
                            <span class="badge bg-light text-muted ms-1 fw-normal">{{ strtoupper($langCode) }}</span>
                        </label>
                        <textarea
                            name="translations[{{ $langCode }}][content]"
                            class="form-control js-page-content-editor @error("translations.$langCode.content") is-invalid @enderror"
                            style="min-height:180px;"
                            data-wysiwyg="page-content"
                            data-lang="{{ $langCode }}"
                            placeholder="{{ t('dashboard.Page_Content', 'محتوى الصفحة') }}"
                        >{{ $contentValue }}</textarea>
                        @error("translations.$langCode.content")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            {{ t('dashboard.Content_Hint', 'محرر نصوص غني — سيتم حفظ HTML في قاعدة البيانات.') }}
                        </div>
                    </div>

                </div>{{-- /lang-panel --}}
            @endforeach

        </div>{{-- /card-body --}}
    </div>{{-- /card ١ --}}

    {{-- ── القسم ٢: SEO وبيانات مشاركة ── --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                  style="width:26px;height:26px;font-size:13px;">٢</span>
            <h5 class="mb-0">{{ t('dashboard.SEO_Meta', 'SEO والمشاركة') }}</h5>
        </div>
        <div class="card-body">

            {{-- تبويبات اللغات للـ SEO --}}
            <div class="lang-switcher mb-4" role="tablist">
                @foreach ($languages as $index => $lang)
                    @php
                        $flags = ['ar' => '🇸🇦', 'en' => '🇬🇧', 'fr' => '🇫🇷', 'de' => '🇩🇪', 'tr' => '🇹🇷', 'es' => '🇪🇸'];
                        $flag  = $flags[$lang->code] ?? '🌐';
                    @endphp
                    <button
                        type="button"
                        class="lang-tab-btn {{ $index === 0 ? 'active' : '' }}"
                        data-seo-tab="{{ $lang->code }}"
                        role="tab"
                    >
                        <span class="lang-flag">{{ $flag }}</span>
                        <span class="lang-name">{{ $lang->name }}</span>
                        <span class="lang-code">{{ strtoupper($lang->code) }}</span>
                    </button>
                @endforeach
            </div>

            @foreach ($languages as $index => $lang)
                @php
                    $langCode = $lang->code;
                    $existingTranslation = $isEdit
                        ? $page->translations->firstWhere('locale', $langCode)
                        : null;

                    $metaTitleValue    = old("translations.$langCode.meta_title",       $existingTranslation->meta_title       ?? '');
                    $metaDescValue     = old("translations.$langCode.meta_description", $existingTranslation->meta_description ?? '');
                    $metaKeywordsValue = old(
                        "translations.$langCode.meta_keywords",
                        is_array($existingTranslation?->meta_keywords ?? null)
                            ? implode(',', $existingTranslation->meta_keywords)
                            : ($existingTranslation->meta_keywords ?? '')
                    );

                    $storedOg   = old("translations.$langCode.og_image", $existingTranslation->og_image ?? null);
                    $ogImageId  = null;
                    $ogImageUrl = null;

                    if (is_numeric($storedOg)) {
                        $media = Media::find((int) $storedOg);
                        if ($media) {
                            $ogImageId  = $media->id;
                            $ogImageUrl = $media->url ?? ($media->file_url ?? null);
                        }
                    } elseif (is_string($storedOg) && $storedOg !== '') {
                        $ogImageUrl = $storedOg;
                    }

                    $previewUrls = $ogImageUrl ? [$ogImageUrl] : [];
                @endphp

                <div
                    id="seo-panel-{{ $langCode }}"
                    class="{{ $index === 0 ? '' : 'hidden' }}"
                    data-seo-panel="{{ $langCode }}"
                >
                    {{-- Meta Title --}}
                    <div class="mb-3">
                        <label class="form-label">
                            {{ t('dashboard.Meta_Title', 'Meta Title') }}
                            <span class="badge bg-light text-muted ms-1 fw-normal">{{ strtoupper($langCode) }}</span>
                        </label>
                        <input
                            type="text"
                            name="translations[{{ $langCode }}][meta_title]"
                            class="form-control @error("translations.$langCode.meta_title") is-invalid @enderror"
                            placeholder="{{ t('dashboard.Meta_Title', 'Meta Title') }}"
                            value="{{ $metaTitleValue }}"
                        >
                        @error("translations.$langCode.meta_title")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Meta Description --}}
                    <div class="mb-3">
                        <label class="form-label">
                            {{ t('dashboard.Meta_Description', 'Meta Description') }}
                            <span class="badge bg-light text-muted ms-1 fw-normal">{{ strtoupper($langCode) }}</span>
                        </label>
                        <textarea
                            name="translations[{{ $langCode }}][meta_description]"
                            class="form-control @error("translations.$langCode.meta_description") is-invalid @enderror"
                            style="height:90px;"
                            placeholder="{{ t('dashboard.Short_description_for_search_engines', 'وصف قصير لمحركات البحث') }}"
                        >{{ $metaDescValue }}</textarea>
                        <div class="form-text">
                            {{ t('dashboard.Aim_for_50_160_characters_Leave_empty_to_reuse_the_title', '50–160 حرفاً. اتركه فارغاً لإعادة استخدام العنوان.') }}
                        </div>
                        @error("translations.$langCode.meta_description")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Meta Keywords --}}
                    <div class="mb-3">
                        <label class="form-label">
                            {{ t('dashboard.Meta_Keywords', 'الكلمات المفتاحية') }}
                            <span class="badge bg-light text-muted ms-1 fw-normal">{{ strtoupper($langCode) }}</span>
                        </label>
                        <input
                            type="text"
                            name="translations[{{ $langCode }}][meta_keywords]"
                            class="form-control @error("translations.$langCode.meta_keywords") is-invalid @enderror"
                            placeholder="keyword-1, keyword-2"
                            value="{{ $metaKeywordsValue }}"
                        >
                        <div class="form-text">
                            {{ t('dashboard.Separate_keywords_with_a_comma_or_Arabic_comma', 'افصل الكلمات بفاصلة (,) أو فاصلة عربية (،).') }}
                        </div>
                        @error("translations.$langCode.meta_keywords")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Open Graph Image --}}
                    <x-dashboard.media-picker
                        :name="'translations['.$langCode.'][og_image]'"
                        :label="t('dashboard.Open_Graph_Image_URL', 'صورة Open Graph').' ('.strtoupper($langCode).') '"
                        :value="$ogImageId ?? $ogImageUrl"
                        :preview-urls="$previewUrls"
                        :multiple="false"
                        class="mt-2"
                    />

                </div>{{-- /seo-panel --}}
            @endforeach

        </div>{{-- /card-body --}}
    </div>{{-- /card ٢ --}}

</div>{{-- /col-span-8 --}}


{{-- ============================
     الـ Sidebar (col-span-4)
     ============================ --}}
<div class="col-span-12 xl:col-span-4">
    <div class="sticky top-6 space-y-4">

        {{-- بطاقة النشر --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ t('dashboard.Publishing_Options', 'خيارات النشر') }}</h5>
            </div>
            <div class="card-body">

                {{-- Builder Mode (hidden) --}}
                <input type="hidden" name="builder_mode" value="{{ $defaultBuilderMode }}">

                {{-- نوع المنشئ --}}
                <div class="mb-3">
                    <label class="form-label">{{ t('dashboard.Builder_Type', 'نوع المنشئ') }}</label>
                    <div class="rounded border bg-light px-3 py-2 small text-muted">
                        <span class="d-block fw-semibold text-body">{{ t('dashboard.Sections_Builder', 'Sections Builder') }}</span>
                        <span class="d-block text-muted" style="font-size:11px;">
                            {{ t('dashboard.Visual_Builder_Archived_Hint', 'المنشئ المرئي مؤرشف — تستخدم الصفحات الجديدة SectionDefinitions.') }}
                        </span>
                    </div>
                    @error('builder_mode')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                {{-- الحالة --}}
                <div class="mb-3">
                    <label class="form-label">{{ t('dashboard.Status', 'الحالة') }}</label>
                    @php $statusOld = old('is_active', $defaultStatus); @endphp

                    <div class="form-check mb-1">
                        <input
                            type="radio" name="is_active" value="1"
                            id="status_published"
                            class="form-check-input"
                            {{ (string) $statusOld === '1' ? 'checked' : '' }}
                        >
                        <label class="form-check-label cursor-pointer" for="status_published">
                            <span class="badge bg-success">{{ t('dashboard.Published', 'منشور') }}</span>
                        </label>
                    </div>

                    <div class="form-check">
                        <input
                            type="radio" name="is_active" value="0"
                            id="status_draft"
                            class="form-check-input"
                            {{ (string) $statusOld === '0' ? 'checked' : '' }}
                        >
                        <label class="form-check-label cursor-pointer" for="status_draft">
                            <span class="badge bg-secondary">{{ t('dashboard.Draft', 'مسودة') }}</span>
                        </label>
                    </div>
                </div>

                {{-- الصفحة الرئيسية --}}
                <div class="mb-3">
                    <label class="form-label">{{ t('dashboard.Homepage', 'الصفحة الرئيسية') }}</label>
                    @php $isHomeOld = old('is_home', $defaultIsHome); @endphp

                    <div class="form-check">
                        <input
                            type="checkbox" name="is_home" value="1"
                            id="is_home"
                            class="form-check-input"
                            {{ (string) $isHomeOld === '1' ? 'checked' : '' }}
                        >
                        <label class="form-check-label cursor-pointer" for="is_home">
                            {{ t('dashboard.Make_Homepage', 'جعل هذه الصفحة الرئيسية') }}
                        </label>
                    </div>
                    <div class="form-text">
                        {{ t('dashboard.Homepage_Hint', 'عند التفعيل تصبح هذه الصفحة هي الصفحة الرئيسية للموقع.') }}
                    </div>
                </div>

                {{-- تاريخ النشر --}}
                <div class="mb-3">
                    <label class="form-label">{{ t('dashboard.Publish_Date', 'تاريخ النشر') }}</label>
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
                        class="form-control @error('published_at') is-invalid @enderror"
                        value="{{ $publishedAtOld }}"
                    >
                    @error('published_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>{{-- /card-body --}}

            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="ti ti-device-floppy me-1"></i>
                    {{ $isEdit ? t('dashboard.Update', 'حفظ التعديلات') : t('dashboard.Publish', 'نشر الصفحة') }}
                </button>
                <a href="{{ route('dashboard.pages.index') }}" class="btn btn-light">
                    {{ t('common.Cancel', 'إلغاء') }}
                </a>
            </div>
        </div>{{-- /card النشر --}}

        {{-- بطاقة تلميحات --}}
        <div class="card border-0 bg-light">
            <div class="card-body small text-muted">
                <p class="mb-2 fw-semibold text-body">
                    <i class="ti ti-info-circle me-1 text-primary"></i>
                    {{ t('dashboard.Page_Help_Title', 'ملاحظات') }}
                </p>
                <ul class="mb-0 ps-3">
                    <li>{{ t('dashboard.Page_Help_1', 'أضف الصفحة أولاً ثم أضف أقسامها من قائمة الصفحات.') }}</li>
                    <li>{{ t('dashboard.Page_Help_2', 'الـ Slug يُولَّد تلقائياً من العنوان ويمكن تعديله.') }}</li>
                    <li>{{ t('dashboard.Page_Help_3', 'بيانات SEO اختيارية — تُحسّن ظهور الصفحة في محركات البحث.') }}</li>
                </ul>
            </div>
        </div>

    </div>{{-- /sticky --}}
</div>{{-- /col-span-4 --}}


{{-- CKEditor 5 CDN --}}
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ── helpers ── */
    function makeSwitcher(attrTab, attrPanel) {
        var tabs   = document.querySelectorAll('[' + attrTab + ']');
        var panels = document.querySelectorAll('[' + attrPanel + ']');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var lang = tab.getAttribute(attrTab);
                tabs.forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                panels.forEach(function (p) {
                    p.classList.toggle('hidden', p.getAttribute(attrPanel) !== lang);
                });
            });
        });
    }

    makeSwitcher('data-lang-tab', 'data-lang-panel');
    makeSwitcher('data-seo-tab',  'data-seo-panel');

    /* ── تمييز التبويبات التي بها أخطاء ── */
    document.querySelectorAll('.is-invalid').forEach(function (field) {
        // حقل الاسم مثل "translations[ar][title]" → نستخرج langCode
        var nameAttr = field.getAttribute('name') || '';
        var match    = nameAttr.match(/translations\[([^\]]+)\]/);
        if (!match) return;
        var lang = match[1];
        // أشعل has-error على تبويبات اللغة المعنية
        document.querySelectorAll('[data-lang-tab="' + lang + '"], [data-seo-tab="' + lang + '"]')
            .forEach(function (btn) { btn.classList.add('has-error'); });
    });

    /* ── CKEditor 5 ── */
    document.querySelectorAll('textarea[data-wysiwyg="page-content"]').forEach(function (textarea) {
        ClassicEditor.create(textarea, {
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'link',
                'bulletedList', 'numberedList', 'blockQuote', '|',
                'undo', 'redo'
            ],
            language: document.documentElement.lang || 'ar',
        }).catch(function (err) { console.error('CKEditor error:', err); });
    });

});
</script>
