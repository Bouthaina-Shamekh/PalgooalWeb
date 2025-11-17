<x-dashboard-layout>

    <style>[x-cloak]{ display:none !important; }</style>

    <div class="container mx-auto py-6 max-w-5xl">
        <h1 class="text-2xl font-bold mb-6">✏️ تعديل القالب: {{ $template->translation()?->name ?? 'Template #'.$template->id }}</h1>

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-4 mb-6 rounded">
                <ul class="list-disc ps-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-4 mb-6 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form
            action="{{ route('dashboard.templates.update', $template->id) }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6 bg-white p-6 shadow rounded-lg"
        >
            @csrf
            @method('PUT')

            {{-- التصنيف --}}
            <div>
                <label class="block font-bold mb-1">تصنيف القالب:</label>
                <select name="category_template_id" required class="w-full border border-gray-300 rounded p-2">
                    <option value="">اختر التصنيف</option>
                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected(old('category_template_id', $template->category_template_id) == $category->id)
                        >
                            {{ $category->translation?->name ?? 'بدون اسم' }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- خطة الاستضافة --}}
            <div>
                <label class="block font-bold mb-1">خطة الاستضافة المرتبطة:</label>
                <select name="plan_id" required class="w-full border border-gray-300 rounded p-2">
                    <option value="">اختر الخطة</option>
                    @foreach ($plans as $plan)
                        <option
                            value="{{ $plan->id }}"
                            @selected(old('plan_id', $template->plan_id) == $plan->id)
                        >
                            {{ $plan->name }} ({{ number_format($plan->price_cents / 100, 2) }} $)
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- السعر والخصم --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block font-bold mb-1">السعر ($):</label>
                    <input
                        type="number"
                        name="price"
                        step="0.01"
                        required
                        class="w-full border p-2 rounded"
                        value="{{ old('price', $template->price) }}"
                    />
                </div>
                <div>
                    <label class="block font-bold mb-1">سعر الخصم ($):</label>
                    <input
                        type="number"
                        name="discount_price"
                        step="0.01"
                        class="w-full border p-2 rounded"
                        value="{{ old('discount_price', $template->discount_price) }}"
                    />
                </div>
                <div>
                    <label class="block font-bold mb-1">تاريخ انتهاء الخصم:</label>
                    <input
                        type="datetime-local"
                        name="discount_ends_at"
                        class="w-full border p-2 rounded"
                        value="{{ old('discount_ends_at', $template->discount_ends_at ? $template->discount_ends_at->format('Y-m-d\TH:i') : '') }}"
                    />
                </div>
            </div>

            {{-- صورة القالب --}}
            <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-4 items-start">
                <div>
                    <label class="block font-bold mb-1">صورة القالب (يمكن تركها فارغة للإبقاء على الحالية):</label>
                    <input
                        type="file"
                        name="image"
                        accept="image/*"
                        class="w-full border p-2 rounded"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        إذا لم تقم برفع صورة جديدة، ستبقى الصورة الحالية كما هي.
                    </p>
                </div>
                @if ($template->image)
                    <div class="text-center">
                        <span class="block text-sm font-semibold mb-2">المعاينة الحالية:</span>
                        <img
                            src="{{ asset('storage/'.$template->image) }}"
                            class="h-24 w-32 object-cover rounded mx-auto border"
                            alt="صورة القالب الحالية"
                        >
                    </div>
                @endif
            </div>

            {{-- الترجمة + التابات --}}
            @php
                $firstLocale = $languages->first()->code ?? null;
            @endphp

            <div x-data="{ activeLocale: '{{ $firstLocale }}' }" class="mt-6">
                <h3 class="text-lg font-bold mb-3 text-gray-900">الترجمات:</h3>

                {{-- Tabs Header --}}
                <div class="inline-flex flex-wrap items-center gap-2 border-b border-gray-200 pb-2 mb-4">
                    @foreach ($languages as $language)
                        @php $locale = $language->code; @endphp
                        <button
                            type="button"
                            @click="activeLocale = '{{ $locale }}'"
                            :class="activeLocale === '{{ $locale }}'
                                ? 'bg-primary text-white shadow-sm'
                                : 'bg-gray-100 text-gray-700'"
                            class="px-3 py-1.5 rounded-full text-sm font-semibold border border-transparent hover:border-primary/40 transition-colors"
                        >
                            {{ $language->name }} ({{ $locale }})
                        </button>
                    @endforeach
                </div>

                {{-- Tabs Content --}}
                <div class="space-y-6">
                    @foreach ($languages as $language)
                        @php
                            $locale = $language->code;
                            $i      = $loop->index;
                            $existing = $template->translations->firstWhere('locale', $locale);

                            // معالجة الـ details الموجودة في قاعدة البيانات (array أو JSON)
                            $existingPayload = $existing?->details;
                            if (!is_array($existingPayload)) {
                                $existingPayload = $existingPayload
                                    ? (json_decode($existingPayload, true) ?: [])
                                    : [];
                            }
                        @endphp

                        <div
                            x-show="activeLocale === '{{ $locale }}'"
                            x-cloak
                            class="border border-gray-200 rounded-xl p-4 sm:p-5 bg-gray-50/60"
                            data-locale-section
                        >
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <h4 class="font-bold text-primary text-base sm:text-lg">
                                    [{{ $language->name }}] ({{ $locale }})
                                </h4>
                                <span class="text-xs text-gray-500">
                                    بيانات هذه اللغة فقط.
                                </span>
                            </div>

                            <input type="hidden" name="translations[{{ $i }}][locale]" value="{{ $locale }}">

                            {{-- الاسم --}}
                            <div class="mb-3">
                                <label class="block font-semibold mb-1 text-gray-800">الاسم:</label>
                                <input
                                    type="text"
                                    name="translations[{{ $i }}][name]"
                                    class="name-input w-full border border-gray-300 p-2.5 rounded-lg bg-white text-sm focus:ring-primary focus:border-primary"
                                    required
                                    value="{{ old("translations.$i.name", $existing->name ?? '') }}"
                                />
                            </div>

                            {{-- slug --}}
                            <div class="mb-3">
                                <label class="block font-semibold mb-1 flex justify-between items-center text-gray-800">
                                    <span>الرابط (slug):</span>
                                    <button
                                        type="button"
                                        class="generate-slug text-xs sm:text-sm text-blue-600 hover:underline"
                                        title="توليد تلقائي من الاسم"
                                    >
                                        🔁 توليد تلقائي
                                    </button>
                                </label>
                                <input
                                    type="text"
                                    name="translations[{{ $i }}][slug]"
                                    class="slug-input w-full border border-gray-300 p-2.5 rounded-lg bg-white text-sm focus:ring-primary focus:border-primary"
                                    required
                                    value="{{ old("translations.$i.slug", $existing->slug ?? '') }}"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    يُستخدم هذا الحقل في الرابط: مثال: <code>my-store-template</code>
                                </p>
                            </div>

                            {{-- رابط المعاينة --}}
                            <div class="mb-3">
                                <label class="block font-semibold mb-1 text-gray-800">
                                    رابط المعاينة (اختياري):
                                </label>
                                <input
                                    type="url"
                                    name="translations[{{ $i }}][preview_url]"
                                    class="w-full border border-gray-300 p-2.5 rounded-lg bg-white text-sm focus:ring-primary focus:border-primary"
                                    value="{{ old("translations.$i.preview_url", $existing->preview_url ?? '') }}"
                                />
                            </div>

                            {{-- الوصف --}}
                            <div class="mb-4">
                                <label class="block font-semibold mb-1 text-gray-800">الوصف:</label>
                                <textarea
                                    name="translations[{{ $i }}][description]"
                                    rows="4"
                                    class="w-full border border-gray-300 p-2.5 rounded-lg bg-white text-sm focus:ring-primary focus:border-primary"
                                    required
                                >{{ old("translations.$i.description", $existing->description ?? '') }}</textarea>
                            </div>

                            {{-- المميزات (Features) --}}
                            <div class="mb-6 rounded-xl border border-gray-200 p-4 sm:p-5 bg-white"
                                 data-features-wrapper>
                                <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                                    <h4 class="text-base sm:text-lg font-bold text-gray-800">
                                        المميزات (Features)
                                    </h4>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                class="add-feature inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white hover:bg-primary/90 shadow">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                                            </svg>
                                            إضافة ميزة
                                        </button>
                                        <button type="button"
                                                class="clear-features inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-gray-700 hover:bg-gray-200">
                                            مسح الكل
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-3" data-features-list></div>
                            </div>

                            {{-- المعرض (Gallery) --}}
                            <div class="mb-6 rounded-xl border border-gray-200 p-4 sm:p-5 bg-white"
                                 data-gallery-wrapper>
                                <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                                    <h4 class="text-base sm:text-lg font-bold text-gray-800">
                                        صور من القالب (Gallery)
                                    </h4>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                class="add-image inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white hover:bg-primary/90 shadow">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                                            </svg>
                                            إضافة صورة
                                        </button>
                                        <button type="button"
                                                class="clear-images inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-gray-700 hover:bg-gray-200">
                                            مسح الكل
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-3" data-images-list></div>
                            </div>

                            {{-- التفاصيل (Details) --}}
                            <div class="mb-6 rounded-xl border border-gray-200 p-4 sm:p-5 bg-white"
                                 data-details-wrapper>
                                <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                                    <h4 class="text-base sm:text-lg font-bold text-gray-800">
                                        تفاصيل إضافية (Details)
                                    </h4>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                class="add-detail inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white hover:bg-primary/90 shadow">
                                            إضافة سطر
                                        </button>
                                        <button type="button"
                                                class="clear-details inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-gray-700 hover:bg-gray-200">
                                            مسح الكل
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-3" data-details-list></div>
                            </div>

                            {{-- المواصفات (Specs) --}}
                            <div class="mb-6 rounded-xl border border-gray-200 p-4 sm:p-5 bg-white"
                                 data-specs-wrapper>
                                <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                                    <h4 class="text-base sm:text-lg font-bold text-gray-800">
                                        مواصفات القالب (Specs)
                                    </h4>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                class="add-spec inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white hover:bg-primary/90 shadow">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path
                                                    d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" />
                                            </svg>
                                            إضافة سطر
                                        </button>
                                        <button type="button"
                                                class="clear-specs inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-gray-700 hover:bg-gray-200">
                                            مسح الكل
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-3" data-specs-list></div>
                            </div>

                            {{-- الوسوم (Tags) --}}
                            <div class="mb-6 rounded-xl border border-gray-200 p-4 sm:p-5 bg-white"
                                 data-tags-wrapper>
                                <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                                    <h4 class="text-base sm:text-lg font-bold text-gray-800">
                                        الوسوم (Tags)
                                    </h4>
                                </div>
                                <div class="flex items-center gap-2 mb-3">
                                    <input
                                        type="text"
                                        class="tag-input w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                                        placeholder="اكتب الوسم ثم اضغط إضافة (مثال: متجر)"
                                    >
                                    <button type="button"
                                            class="add-tag inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white hover:bg-primary/90 shadow">
                                        إضافة
                                    </button>
                                    <button type="button"
                                            class="clear-tags inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-gray-700 hover:bg-gray-200">
                                        مسح الكل
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2" data-tags-list></div>
                            </div>

                            {{-- الحقل المخفي الموحّد (JSON) --}}
                            @php
                                $oldDetails = old("translations.$i.details");
                            @endphp
                            <input
                                type="hidden"
                                name="translations[{{ $i }}][details]"
                                class="details-json"
                                value="{{ $oldDetails ? $oldDetails : '' }}"
                                @if(!$oldDetails && !empty($existingPayload))
                                    data-existing='@json($existingPayload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'
                                @endif
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                يتم حفظ المميزات + المعرض + المواصفات + التفاصيل + الوسوم كـ JSON تلقائيًا.
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <button type="submit" class="bg-primary hover:bg-primary/80 text-white font-bold py-2 px-4 rounded">
                    💾 حفظ التعديلات
                </button>
            </div>
        </form>
    </div>

    {{-- نفس سكربت create: توليد slug + details JSON --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('[data-locale-section]');

            sections.forEach(section => {
                const nameInput = section.querySelector('.name-input');
                const slugInput = section.querySelector('.slug-input');
                const generateBtn = section.querySelector('.generate-slug');

                if (nameInput && slugInput && generateBtn) {
                    generateBtn.addEventListener('click', function() {
                        slugInput.value = generateSlug(nameInput.value);
                    });
                }

                if (slugInput) {
                    slugInput.addEventListener('input', function() {
                        this.value = generateSlug(this.value, true);
                    });
                }
            });

            function generateSlug(input, isManual = false) {
                let slug = (input || '')
                    .toLowerCase()
                    .trim()
                    .replace(/[\s_]+/g, '-')
                    .replace(/[^a-zA-Z0-9\u0600-\u06FF\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+|-+$/g, '');
                return slug;
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-locale-section]').forEach(section => {
                const listFeatures = section.querySelector('[data-features-list]');
                const addFeature = section.querySelector('.add-feature');
                const clearFeatures = section.querySelector('.clear-features');

                const listImages = section.querySelector('[data-images-list]');
                const addImage = section.querySelector('.add-image');
                const clearImages = section.querySelector('.clear-images');

                const listSpecs = section.querySelector('[data-specs-list]');
                const addSpec = section.querySelector('.add-spec');
                const clearSpecs = section.querySelector('.clear-specs');

                const listDetails = section.querySelector('[data-details-list]');
                const addDetail = section.querySelector('.add-detail');
                const clearDetails = section.querySelector('.clear-details');

                const tagsInput = section.querySelector('.tag-input');
                const addTagBtn = section.querySelector('.add-tag');
                const clearTagsBtn = section.querySelector('.clear-tags');
                const tagsList = section.querySelector('[data-tags-list]');

                const detailsInp = section.querySelector('.details-json');

                function escapeHtml(str) {
                    return (str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function featureRow(item = { title: '', icon: '' }) {
                    const row = document.createElement('div');
                    row.className =
                        'feature-row grid grid-cols-1 sm:grid-cols-[1fr_160px_auto] gap-2 rounded-lg border border-gray-200 p-3 bg-white';
                    row.innerHTML = `
                        <input type="text" class="feat-title w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                               placeholder="عنوان الميزة" value="${escapeHtml(item.title)}">
                        <input type="text" class="feat-icon w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                               placeholder="🎨 أيقونة (اختياري)" value="${escapeHtml(item.icon || '')}">
                        <button type="button" class="remove-feature inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100">حذف</button>`;
                    row.querySelector('.remove-feature').addEventListener('click', () => {
                        row.remove();
                        syncJson();
                    });
                    row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncJson));
                    return row;
                }

                function imageRow(item = { src: '', alt: '' }) {
                    const row = document.createElement('div');
                    row.className =
                        'image-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 rounded-lg border border-gray-200 p-3 bg-white';
                    row.innerHTML = `
                        <input type="text" class="img-src w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                               placeholder="رابط الصورة" value="${escapeHtml(item.src)}">
                        <input type="text" class="img-alt w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                               placeholder="نص بديل (ALT)" value="${escapeHtml(item.alt || '')}">
                        <button type="button" class="remove-image inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100">حذف</button>`;
                    row.querySelector('.remove-image').addEventListener('click', () => {
                        row.remove();
                        syncJson();
                    });
                    row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncJson));
                    return row;
                }

                function specRow(item = { name: '', value: '' }) {
                    const row = document.createElement('div');
                    row.className =
                        'spec-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 rounded-lg border border-gray-200 p-3 bg-white';
                    row.innerHTML = `
                        <input type="text" class="spec-name w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                               placeholder="الاسم" value="${escapeHtml(item.name)}">
                        <input type="text" class="spec-value w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                               placeholder="القيمة" value="${escapeHtml(item.value)}">
                        <button type="button" class="remove-spec inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100">حذف</button>`;
                    row.querySelector('.remove-spec').addEventListener('click', () => {
                        row.remove();
                        syncJson();
                    });
                    row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncJson));
                    return row;
                }

                function detailRow(item = { name: '', value: '' }) {
                    const row = document.createElement('div');
                    row.className =
                        'detail-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 rounded-lg border border-gray-200 p-3 bg-white';
                    row.innerHTML = `
                        <input type="text" class="detail-name w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                               placeholder="العنصر (مثال: آخر تحديث)" value="${escapeHtml(item.name)}">
                        <input type="text" class="detail-value w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                               placeholder="القيمة (مثال: يوليو 2025)" value="${escapeHtml(item.value)}">
                        <button type="button" class="remove-detail inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100">حذف</button>`;
                    row.querySelector('.remove-detail').addEventListener('click', () => {
                        row.remove();
                        syncJson();
                    });
                    row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncJson));
                    return row;
                }

                function addTagChip(label) {
                    const text = (label || '').trim();
                    if (!text || !tagsList) return;
                    const exists = Array.from(tagsList.querySelectorAll('[data-tag]'))
                        .some(el => (el.dataset.tag || '').toLowerCase() === text.toLowerCase());
                    if (exists) return;

                    const chip = document.createElement('span');
                    chip.className =
                        'inline-flex items-center gap-1 bg-primary/10 text-primary text-xs font-bold px-3 py-1 rounded-full';
                    chip.setAttribute('data-tag', text);
                    chip.innerHTML = `${escapeHtml(text)}
                        <button type="button" class="remove-tag ml-1 text-primary hover:text-primary/70">×</button>`;
                    chip.querySelector('.remove-tag').addEventListener('click', () => {
                        chip.remove();
                        syncJson();
                    });
                    tagsList.appendChild(chip);
                    syncJson();
                }

                function syncJson() {
                    const features = Array.from(listFeatures?.querySelectorAll('.feature-row') || []).map(
                        r => ({
                            title: r.querySelector('.feat-title')?.value.trim() || '',
                            icon: r.querySelector('.feat-icon')?.value.trim() || ''
                        })
                    ).filter(x => x.title.length);

                    const gallery = Array.from(listImages?.querySelectorAll('.image-row') || []).map(r => ({
                        src: r.querySelector('.img-src')?.value.trim() || '',
                        alt: r.querySelector('.img-alt')?.value.trim() || ''
                    })).filter(x => x.src.length);

                    const specs = Array.from(listSpecs?.querySelectorAll('.spec-row') || []).map(r => ({
                        name: r.querySelector('.spec-name')?.value.trim() || '',
                        value: r.querySelector('.spec-value')?.value.trim() || ''
                    })).filter(x => x.name && x.value);

                    const details = Array.from(listDetails?.querySelectorAll('.detail-row') || []).map(r => ({
                        name: r.querySelector('.detail-name')?.value.trim() || '',
                        value: r.querySelector('.detail-value')?.value.trim() || ''
                    })).filter(x => x.name && x.value);

                    const tags = Array.from(tagsList?.querySelectorAll('[data-tag]') || [])
                        .map(el => (el.dataset.tag || '').trim())
                        .filter(Boolean);

                    let payload = {};
                    try {
                        if (detailsInp.value) payload = JSON.parse(detailsInp.value) || {};
                        else if (detailsInp.dataset.existing) payload = JSON.parse(detailsInp.dataset.existing) || {};
                    } catch (e) {
                        payload = {};
                    }

                    payload.features = features;
                    payload.gallery  = gallery;
                    payload.specs    = specs;
                    payload.details  = details;
                    payload.tags     = tags;

                    detailsInp.value = JSON.stringify(payload);
                }

                (function init() {
                    let existing = {};
                    try {
                        if (detailsInp.value) {
                            existing = JSON.parse(detailsInp.value) || {};
                        } else if (detailsInp.dataset.existing) {
                            existing = JSON.parse(detailsInp.dataset.existing) || {};
                        }
                    } catch (e) {
                        existing = {};
                    }

                    if (listFeatures) {
                        if (Array.isArray(existing.features) && existing.features.length) {
                            existing.features.forEach(f => listFeatures.appendChild(featureRow(f)));
                        } else {
                            listFeatures.appendChild(featureRow());
                        }
                    }

                    if (listImages) {
                        if (Array.isArray(existing.gallery) && existing.gallery.length) {
                            existing.gallery.forEach(img => listImages.appendChild(imageRow(img)));
                        } else {
                            listImages.appendChild(imageRow());
                        }
                    }

                    if (listSpecs) {
                        if (Array.isArray(existing.specs) && existing.specs.length) {
                            existing.specs.forEach(s => listSpecs.appendChild(specRow(s)));
                        } else {
                            listSpecs.appendChild(specRow());
                        }
                    }

                    if (listDetails) {
                        if (Array.isArray(existing.details) && existing.details.length) {
                            existing.details.forEach(d => listDetails.appendChild(detailRow(d)));
                        } else {
                            listDetails.appendChild(detailRow());
                        }
                    }

                    if (tagsList && Array.isArray(existing.tags) && existing.tags.length) {
                        existing.tags.forEach(t => addTagChip(t));
                    }

                    addFeature?.addEventListener('click', () => {
                        listFeatures.appendChild(featureRow());
                        syncJson();
                    });
                    clearFeatures?.addEventListener('click', () => {
                        listFeatures.innerHTML = '';
                        syncJson();
                    });

                    addImage?.addEventListener('click', () => {
                        listImages.appendChild(imageRow());
                        syncJson();
                    });
                    clearImages?.addEventListener('click', () => {
                        listImages.innerHTML = '';
                        syncJson();
                    });

                    addSpec?.addEventListener('click', () => {
                        listSpecs.appendChild(specRow());
                        syncJson();
                    });
                    clearSpecs?.addEventListener('click', () => {
                        listSpecs.innerHTML = '';
                        syncJson();
                    });

                    addDetail?.addEventListener('click', () => {
                        listDetails.appendChild(detailRow());
                        syncJson();
                    });
                    clearDetails?.addEventListener('click', () => {
                        listDetails.innerHTML = '';
                        syncJson();
                    });

                    addTagBtn?.addEventListener('click', () => {
                        addTagChip(tagsInput.value);
                        tagsInput.value = '';
                        tagsInput.focus();
                    });
                    tagsInput?.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            addTagChip(tagsInput.value);
                            tagsInput.value = '';
                        }
                    });
                    clearTagsBtn?.addEventListener('click', () => {
                        if (tagsList) tagsList.innerHTML = '';
                        syncJson();
                    });

                    syncJson();
                })();
            });
        });
    </script>

</x-dashboard-layout>
