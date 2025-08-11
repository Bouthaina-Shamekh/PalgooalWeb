<x-dashboard-layout>
<div class="container mx-auto py-6 max-w-5xl">
    <h1 class="text-2xl font-bold mb-6">✏️ تعديل القالب: {{ $template->translation()?->name }}</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-4 mb-6 rounded">
            <ul class="list-disc ps-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dashboard.templates.update', $template->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6 bg-white p-6 shadow rounded-lg">
        @csrf
        @method('PUT')

        <!-- التصنيف -->
        <div>
            <label class="block font-bold mb-1">تصنيف القالب:</label>
            <select name="category_template_id" required class="w-full border border-gray-300 rounded p-2">
                <option value="">اختر التصنيف</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $template->category_template_id == $category->id ? 'selected' : '' }}>
                        {{ $category->translation?->name ?? 'بدون اسم' }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- السعر والخصم -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block font-bold mb-1">السعر ($):</label>
                <input type="number" name="price" step="0.01" value="{{ old('price', $template->price) }}" required class="w-full border p-2 rounded" />
            </div>
            <div>
                <label class="block font-bold mb-1">سعر الخصم ($):</label>
                <input type="number" name="discount_price" step="0.01" value="{{ old('discount_price', $template->discount_price) }}" class="w-full border p-2 rounded" />
            </div>
            <div>
                <label class="block font-bold mb-1">تاريخ انتهاء الخصم:</label>
                <input type="datetime-local" name="discount_ends_at"
                       value="{{ old('discount_ends_at', optional($template->discount_ends_at)->format('Y-m-d\TH:i')) }}"
                       class="w-full border p-2 rounded" />
            </div>
        </div>

        <!-- رفع صورة جديدة -->
        <div>
            <label class="block font-bold mb-1">الصورة الحالية:</label>
            <img src="{{ asset('storage/' . $template->image) }}" class="w-32 h-24 object-cover mb-2 rounded">
            <input type="file" name="image" accept="image/*" class="w-full border p-2 rounded" />
            <small class="text-gray-500">يمكنك ترك هذا الحقل فارغًا إذا لم ترغب في تغييره.</small>
        </div>

        <!-- الترجمة -->
        <div>
            <h3 class="text-lg font-bold mb-2">الترجمات:</h3>

            <div class="space-y-6">
                @foreach ($languages as $language)
                    @php
                        $locale = $language->code;
                        $translation = $template->translations->where('locale', $locale)->first();
                    @endphp

                    <div class="border rounded p-4" data-locale-section>
                        <h4 class="font-bold mb-2 text-primary">[{{ $language->name }}]</h4>
                        <input type="hidden" name="translations[{{ $loop->index }}][locale]" value="{{ $locale }}">
                        <div class="mb-2">
                            <label class="block font-semibold mb-1">الاسم:</label>
                            <input type="text" name="translations[{{ $loop->index }}][name]" value="{{ old("translations.$loop->index.name", $translation?->name) }}"
                                class="name-input w-full border p-2 rounded" required />
                        </div>
                        <div class="mb-2">
                            <label class="block font-semibold mb-1 flex justify-between items-center">
                                <span>الرابط (slug):</span>
                                <button type="button" class="generate-slug text-sm text-blue-600 hover:underline" title="توليد تلقائي">🔁 توليد تلقائي</button>
                            </label>
                            <input type="text" name="translations[{{ $loop->index }}][slug]" value="{{ old("translations.$loop->index.slug", $translation?->slug) }}"
                                class="slug-input w-full border p-2 rounded" required />
                        </div>
                        <div class="mb-2">
                            <label class="block font-semibold mb-1">رابط المعاينة:</label>
                            <input type="url" name="translations[{{ $loop->index }}][preview_url]" value="{{ old("translations.$loop->index.preview_url", $translation?->preview_url) }}" class="w-full border p-2 rounded" />
                        </div>
                        <div class="mb-2">
                            <label class="block font-semibold mb-1">الوصف:</label>
                            <textarea name="translations[{{ $loop->index }}][description]" rows="4" class="w-full border p-2 rounded" required>{{ old("translations.$loop->index.description", $translation?->description) }}</textarea>
                        </div>
                        <div class="mb-6 rounded-xl border border-gray-200 p-4 sm:p-5 bg-white" data-features-wrapper>
                            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                                <h4 class="text-base sm:text-lg font-bold text-gray-800">المميزات (Features)</h4>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="add-feature inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white hover:bg-primary/90 shadow">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
                                        إضافة ميزة
                                    </button>
                                    <button type="button"
                                        class="clear-features inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-gray-700 hover:bg-gray-200">
                                        مسح الكل
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-3" data-features-list>
                                {{-- يتم ملؤها ديناميكياً --}}
                            </div>
                        </div>
                        {{-- المعرض (Gallery) --}}
                        <div class="mb-6 rounded-xl border border-gray-200 p-4 sm:p-5 bg-white" data-gallery-wrapper>
                            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                                <h4 class="text-base sm:text-lg font-bold text-gray-800">صور من القالب (Gallery)</h4>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="add-image inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white hover:bg-primary/90 shadow">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
                                        إضافة صورة
                                    </button>
                                    <button type="button"
                                        class="clear-images inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-gray-700 hover:bg-gray-200">
                                        مسح الكل
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-3" data-images-list>
                                {{-- يتم ملؤها ديناميكياً --}}
                            </div>
                        </div>
                        {{-- المواصفات (Specs) --}}
                        <div class="mb-6 rounded-xl border border-gray-200 p-4 sm:p-5 bg-white" data-specs-wrapper>
                            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                                <h4 class="text-base sm:text-lg font-bold text-gray-800">تفاصيل القالب (Specs)</h4>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="add-spec inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-2 text-white hover:bg-primary/90 shadow">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"/></svg>
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
                        <div class="mb-6 rounded-xl border border-gray-200 p-4 sm:p-5 bg-white" data-tags-wrapper>
                            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                                <h4 class="text-base sm:text-lg font-bold text-gray-800">الوسوم (Tags)</h4>
                            </div>
                            <div class="flex items-center gap-2 mb-3">
                                <input type="text" class="tag-input w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
                                    placeholder="اكتب الوسم ثم اضغط إضافة (مثال: متجر)">
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
                            {{-- الحقل الذي سيحمل JSON النهائي --}}
                            <input type="hidden"
                                name="translations[{{ $loop->index }}][details]"
                                class="details-json"
                                value="{{ old("translations.$loop->index.details",
                                    is_array($translation?->details)
                                    ? json_encode($translation->details, JSON_UNESCAPED_UNICODE)
                                    : ($translation?->details ?? '')
                                ) }}"
                                data-existing='@json($translation?->details)'>
                            <p class="mt-2 text-xs text-gray-500">يتم حفظ المميزات كـ JSON تلقائياً داخل الحقل المخفي.</p>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sections = document.querySelectorAll('[data-locale-section]');

        sections.forEach(section => {
            const nameInput = section.querySelector('.name-input');
            const slugInput = section.querySelector('.slug-input');
            const generateBtn = section.querySelector('.generate-slug');

            // زر التوليد التلقائي من الاسم
            if (nameInput && slugInput && generateBtn) {
                generateBtn.addEventListener('click', function () {
                    slugInput.value = generateSlug(nameInput.value);
                });
            }

            // تعديل مباشر داخل slug => يتم تصحيح النص بإضافة "-"
            if (slugInput) {
                slugInput.addEventListener('input', function () {
                    this.value = generateSlug(this.value, true); // true = manual typing
                });
            }
        });

        // الدالة المساعدة
        function generateSlug(input) {
            let slug = input
            .toLowerCase()
            .trim()
            .replace(/[\s_]+/g, '-')                       // replace spaces and underscores with hyphen
            .replace(/[^\p{L}\p{N}\-]+/gu, '')             // keep letters, numbers, hyphen
            .replace(/\-\-+/g, '-')                        // collapse multiple hyphens
            .replace(/^-+|-+$/g, '');                      // remove hyphens from start/end
            return slug;
        }
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-locale-section]').forEach(section => {
    // عناصر المميزات
    const listFeatures = section.querySelector('[data-features-list]');
    const addFeature   = section.querySelector('.add-feature');
    const clearFeatures= section.querySelector('.clear-features');

    // عناصر المعرض
    const listImages   = section.querySelector('[data-images-list]');
    const addImage     = section.querySelector('.add-image');
    const clearImages  = section.querySelector('.clear-images');

    // عناصر المواصفات
    const listSpecs    = section.querySelector('[data-specs-list]');
    const addSpec      = section.querySelector('.add-spec');
    const clearSpecs   = section.querySelector('.clear-specs');

    // عناصر الوسوم
    const tagsInput    = section.querySelector('.tag-input');
    const addTagBtn    = section.querySelector('.add-tag');
    const clearTagsBtn = section.querySelector('.clear-tags');
    const tagsList     = section.querySelector('[data-tags-list]');

    // الحقل المخفي الوحيد
    const detailsInp   = section.querySelector('.details-json');

    // Helpers
    function escapeHtml(str){ return (str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;')
                                           .replace(/>/g,'&gt;').replace(/"/g,'&quot;')
                                           .replace(/'/g,'&#039;'); }

    // Rows builders
    function featureRow(item = { title: '', icon: '' }) {
      const row = document.createElement('div');
      row.className = 'feature-row grid grid-cols-1 sm:grid-cols-[1fr_160px_auto] gap-2 rounded-lg border border-gray-200 p-3 bg-white';
      row.innerHTML = `
        <input type="text" class="feat-title w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
               placeholder="عنوان الميزة (مثال: تصميم احترافي)" value="${escapeHtml(item.title)}">
        <input type="text" class="feat-icon w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
               placeholder="🎨 أيقونة (اختياري)" value="${escapeHtml(item.icon || '')}">
        <button type="button" class="remove-feature inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100">حذف</button>
      `;
      row.querySelector('.remove-feature').addEventListener('click', () => { row.remove(); syncJson(); });
      row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncJson));
      return row;
    }

    function imageRow(item = { src: '', alt: '' }) {
      const row = document.createElement('div');
      row.className = 'image-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 rounded-lg border border-gray-200 p-3 bg-white';
      row.innerHTML = `
        <input type="text" class="img-src w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
               placeholder="رابط الصورة (http/https أو storage/...)" value="${escapeHtml(item.src)}">
        <input type="text" class="img-alt w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
               placeholder="نص بديل (ALT)" value="${escapeHtml(item.alt || '')}">
        <button type="button" class="remove-image inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100">حذف</button>
      `;
      row.querySelector('.remove-image').addEventListener('click', () => { row.remove(); syncJson(); });
      row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncJson));
      return row;
    }

    function specRow(item = { name: '', value: '' }) {
      const row = document.createElement('div');
      row.className = 'spec-row grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-2 rounded-lg border border-gray-200 p-3 bg-white';
      row.innerHTML = `
        <input type="text" class="spec-name w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
               placeholder="الاسم (مثال: لغة القالب)" value="${escapeHtml(item.name)}">
        <input type="text" class="spec-value w-full rounded-md border-gray-300 focus:border-primary focus:ring-primary"
               placeholder="القيمة (مثال: عربي)" value="${escapeHtml(item.value)}">
        <button type="button" class="remove-spec inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100">حذف</button>
      `;
      row.querySelector('.remove-spec').addEventListener('click', () => { row.remove(); syncJson(); });
      row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncJson));
      return row;
    }

    function addTagChip(label){
      const text = (label || '').trim();
      if (!text) return;
      // منع التكرار
      const exists = Array.from(tagsList?.querySelectorAll('[data-tag]') || [])
        .some(el => (el.dataset.tag || '').toLowerCase() === text.toLowerCase());
      if (exists) return;

      const chip = document.createElement('span');
      chip.className = 'inline-flex items-center gap-1 bg-primary/10 text-primary text-xs font-bold px-3 py-1 rounded-full';
      chip.setAttribute('data-tag', text);
      chip.innerHTML = `${escapeHtml(text)}
        <button type="button" class="remove-tag ml-1 text-primary hover:text-primary/70">×</button>`;
      chip.querySelector('.remove-tag').addEventListener('click', () => { chip.remove(); syncJson(); });
      tagsList?.appendChild(chip);
      syncJson();
    }

    // Sync details (merge)
    function syncJson() {
      const features = Array.from(listFeatures?.querySelectorAll('.feature-row') || []).map(r => ({
        title: r.querySelector('.feat-title')?.value.trim() || '',
        icon:  r.querySelector('.feat-icon')?.value.trim() || ''
      })).filter(x => x.title.length);

      const gallery = Array.from(listImages?.querySelectorAll('.image-row') || []).map(r => ({
        src: r.querySelector('.img-src')?.value.trim() || '',
        alt: r.querySelector('.img-alt')?.value.trim() || ''
      })).filter(x => x.src.length);

      const specs = Array.from(listSpecs?.querySelectorAll('.spec-row') || []).map(r => ({
        name:  r.querySelector('.spec-name')?.value.trim()  || '',
        value: r.querySelector('.spec-value')?.value.trim() || ''
      })).filter(x => x.name.length && x.value.length);

      const tags = Array.from(tagsList?.querySelectorAll('[data-tag]') || [])
        .map(el => (el.dataset.tag || '').trim())
        .filter(Boolean);

      // اقرأ القديم لو في مفاتيح أخرى غير هذه (للمستقبل)
      let payload = {};
      try {
        if (detailsInp.value) payload = JSON.parse(detailsInp.value) || {};
      } catch(e){ payload = {}; }

      payload.features = features;
      payload.gallery  = gallery;
      payload.specs    = specs;
      payload.tags     = tags;

      detailsInp.value = JSON.stringify(payload);
    }

    // Init from existing
    (function init() {
      // حمّل القديم من value أو data-existing
      let existing = null;
      try {
        if (detailsInp.value) existing = JSON.parse(detailsInp.value);
        if (!existing && detailsInp.dataset.existing) existing = JSON.parse(detailsInp.dataset.existing);
      } catch (e) { existing = null; }

      const exFeatures = (existing && Array.isArray(existing.features)) ? existing.features : [];
      const exGallery  = (existing && Array.isArray(existing.gallery))  ? existing.gallery  : [];
      const exSpecs    = (existing && Array.isArray(existing.specs))    ? existing.specs    : [];
      const exTags     = (existing && Array.isArray(existing.tags))     ? existing.tags     : [];

      // املأ من القديم
      if (listFeatures) {
        if (exFeatures.length) exFeatures.forEach(f => listFeatures.appendChild(featureRow(f)));
        if (!listFeatures.children.length) listFeatures.appendChild(featureRow());
      }
      if (listImages) {
        if (exGallery.length) exGallery.forEach(it => listImages.appendChild(imageRow(it)));
        if (!listImages.children.length) listImages.appendChild(imageRow());
      }
      if (listSpecs) {
        if (exSpecs.length) exSpecs.forEach(s => listSpecs.appendChild(specRow(s)));
        if (!listSpecs.children.length) listSpecs.appendChild(specRow());
      }
      if (Array.isArray(exTags) && exTags.length && tagsList) {
        exTags.forEach(t => addTagChip(t));
      }

      // أزرار
      addFeature?.addEventListener('click', () => { listFeatures.appendChild(featureRow()); syncJson(); });
      clearFeatures?.addEventListener('click', () => { listFeatures.innerHTML = ''; syncJson(); });

      addImage?.addEventListener('click', () => { listImages.appendChild(imageRow()); syncJson(); });
      clearImages?.addEventListener('click', () => { listImages.innerHTML = ''; syncJson(); });

      addSpec?.addEventListener('click', () => { listSpecs.appendChild(specRow()); syncJson(); });
      clearSpecs?.addEventListener('click', () => { listSpecs.innerHTML = ''; syncJson(); });

      addTagBtn?.addEventListener('click', () => { addTagChip(tagsInput.value); tagsInput.value=''; tagsInput.focus(); });
      tagsInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); addTagChip(tagsInput.value); tagsInput.value=''; }
      });
      clearTagsBtn?.addEventListener('click', () => { tagsList.innerHTML = ''; syncJson(); });

      syncJson();
    })();
  });
});
</script>



</x-dashboard-layout>
