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

                        <div class="mb-2">
                            <label class="block font-semibold mb-1">تفاصيل إضافية (JSON):</label>
                            <textarea name="translations[{{ $loop->index }}][details]" rows="3" class="w-full border p-2 rounded">{{ old("translations.$loop->index.details", $translation?->details) }}</textarea>
                        </div>
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

</x-dashboard-layout>
