<x-dashboard-layout>
<div class="container mx-auto py-6 max-w-5xl">
    <h1 class="text-2xl font-bold mb-6">➕ إضافة قالب جديد</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-4 mb-6 rounded">
            <ul class="list-disc ps-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dashboard.templates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 bg-white p-6 shadow rounded-lg">
        @csrf

        <!-- التصنيف -->
        <div>
            <label class="block font-bold mb-1">تصنيف القالب:</label>
            <select name="category_template_id" required class="w-full border border-gray-300 rounded p-2">
                <option value="">اختر التصنيف</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->translation?->name ?? 'بدون اسم' }}</option>
                @endforeach
            </select>
        </div>

        <!-- السعر والخصم -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block font-bold mb-1">السعر ($):</label>
                <input type="number" name="price" step="0.01" required class="w-full border p-2 rounded" />
            </div>
            <div>
                <label class="block font-bold mb-1">سعر الخصم ($):</label>
                <input type="number" name="discount_price" step="0.01" class="w-full border p-2 rounded" />
            </div>
            <div>
                <label class="block font-bold mb-1">تاريخ انتهاء الخصم:</label>
                <input type="datetime-local" name="discount_ends_at" class="w-full border p-2 rounded" />
            </div>
        </div>

        <!-- رفع الصورة -->
        <div>
            <label class="block font-bold mb-1">صورة القالب:</label>
            <input type="file" name="image" accept="image/*" required class="w-full border p-2 rounded" />
        </div>

        <!-- الترجمة -->
        <div>
            <h3 class="text-lg font-bold mb-2">الترجمات:</h3>

            <div class="space-y-6">
                @foreach ($languages as $language)
                    @php $locale = $language->code; @endphp
                    <div class="border rounded p-4" data-locale-section>
                        <h4 class="font-bold mb-2 text-primary">[{{ $language->name }}]</h4>
                        
                        <input type="hidden" name="translations[{{ $loop->index }}][locale]" value="{{ $locale }}">
                        <div class="mb-2">
                            <label class="block font-semibold mb-1">الاسم:</label>
                            <input type="text" name="translations[{{ $loop->index }}][name]"
                                class="name-input w-full border p-2 rounded" required />
                        </div>
                        <div class="mb-2">
                            <label class="block font-semibold mb-1 flex justify-between items-center">
                                <span>الرابط (slug):</span>
                                <button type="button" class="generate-slug text-sm text-blue-600 hover:underline" title="توليد تلقائي">🔁 توليد تلقائي</button>
                            </label>
                            <input type="text" name="translations[{{ $loop->index }}][slug]"
                                class="slug-input w-full border p-2 rounded" required />
                        </div>
                        <div class="mb-2">
                            <label class="block font-semibold mb-1">رابط المعاينة (اختياري):</label>
                            <input type="url" name="translations[{{ $loop->index }}][preview_url]" class="w-full border p-2 rounded" />
                        </div>

                        <div class="mb-2">
                            <label class="block font-semibold mb-1">الوصف:</label>
                            <textarea name="translations[{{ $loop->index }}][description]" rows="4" class="w-full border p-2 rounded" required></textarea>
                        </div>

                        <div class="mb-2">
                            <label class="block font-semibold mb-1">تفاصيل إضافية (JSON - اختياري):</label>
                            <textarea name="translations[{{ $loop->index }}][details]" rows="3" class="w-full border p-2 rounded" placeholder='{"key": "value"}'></textarea>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <button type="submit" class="bg-primary hover:bg-primary/80 text-white font-bold py-2 px-4 rounded">
                💾 حفظ القالب
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
        function generateSlug(input, isManual = false) {
            let slug = input
                .toLowerCase()
                .trim()
                .replace(/[\s_]+/g, '-')                        // replace spaces and underscores with hyphens
                .replace(/[^a-zA-Z0-9\u0600-\u06FF\-]+/g, '')   // keep Arabic + letters + numbers
                .replace(/\-\-+/g, '-')                         // merge multiple hyphens
                .replace(/^-+|-+$/g, '');                       // remove hyphens from start/end

            return slug;
        }
    });
</script>




</x-dashboard-layout>
