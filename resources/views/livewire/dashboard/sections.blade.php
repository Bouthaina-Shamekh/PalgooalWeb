<div class="space-y-6">
    <h2 class="text-xl font-bold mb-4">إدارة سكشنات الصفحة</h2>

    {{-- ✅ رسائل النجاح --}}
    @if (session()->has('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 text-red-800 px-4 py-2 rounded">
            {{ session('error') }}
        </div>
    @endif



    {{-- ✅ عرض السكشنات الحالية --}}
    @foreach ($sections as $section)
        @switch($section->key)
            @case('hero')
                <livewire:dashboard.sections.hero-section :section="$section" :key="$section->id" />
                @break
            @case('features')
                <livewire:dashboard.sections.features-section :section="$section" :key="$section->id" />
                @break
            @case('banner')
                <livewire:dashboard.sections.banner-section :section="$section" :key="$section->id" />
                @break

            @case('services')
                <livewire:dashboard.sections.services-section :section="$section" :key="$section->id" />
            @break
            @case('works')
                <livewire:dashboard.sections.works-section :section="$section" :key="$section->id" />
            @break
            @case('home-works')
                <livewire:dashboard.sections.home-works-section :section="$section" :key="$section->id" />
            @break

            @default
                <div class="p-4 bg-gray-100 rounded shadow">
                    سكشن غير مدعوم حالياً: {{ $section->key }}
                </div>
        @endswitch
    @endforeach

    {{-- ✅ إضافة سكشن جديد --}}
    <div class="mt-10 border-t pt-6 space-y-6">
        <h3 class="text-lg font-semibold">إضافة سكشن جديد</h3>

        @php
            $keyNames = [
                'hero' => 'الواجهة الرئيسية (Hero)',
                'features' => 'مميزات (Features)',
                'services' => 'الخدمات (Services)',
                'templates' => 'القوالب (Templates)',
                'works' => 'الأعمال (Works)',
                'home-works' => 'أعمالنا في الهوم (Home Works)',
                'testimonials' => 'آراء العملاء (Testimonials)',
                'blog' => 'المدونة (Blog)',
                'banner' => 'اللوحة (banner)',
            ];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 text-sm font-medium">نوع السكشن</label>
                <select wire:model.live="sectionKey" class="border p-2 rounded w-full">
                    <option value="">اختر نوع السكشن</option>
                    @foreach ($availableKeys as $key)
                        <option value="{{ $key }}">{{ $keyNames[$key] ?? ucfirst($key) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium">الترتيب (اختياري)</label>
                <input type="number" wire:model="sectionOrder" class="border p-2 rounded w-full" placeholder="مثال: 1، 2، 3...">
            </div>
        </div>

        @if ($sectionKey)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($languages as $lang)
                    <div class="col-span-2 border p-4 rounded bg-gray-50">
                        <h4 class="font-bold mb-3">{{ $lang->name }} ({{ $lang->code }})</h4>

                        @if ($sectionKey === 'hero')
                            <label class="block text-sm mb-1">العنوان الرئيسي</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.title"
                                   class="w-full border p-2 rounded mb-2" placeholder="العنوان الرئيسي">

                            <label class="block text-sm mb-1">النص الفرعي</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.subtitle"
                                   class="w-full border p-2 rounded mb-2" placeholder="النص الفرعي">

                            <label class="block text-sm mb-1">نص الزر الاول</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_text-1"
                                   class="w-full border p-2 rounded mb-2" placeholder="مثال: اكتشف الآن">

                            <label class="block text-sm mb-1">رابط الزر الاول</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_url-1"
                                   class="w-full border p-2 rounded" placeholder="https://example.com">
                            <label class="block text-sm mb-1">نص الزر الثاني</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_text-2"
                                   class="w-full border p-2 rounded mb-2" placeholder="مثال: اكتشف الآن">

                            <label class="block text-sm mb-1">رابط الزر الثاني</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_url-2"
                                   class="w-full border p-2 rounded" placeholder="https://example.com">

                        @elseif ($sectionKey === 'features')
                            <label class="block text-sm mb-1">عنوان القسم</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.title"
                                   class="w-full border p-2 rounded mb-2" placeholder="ما الذي يميزنا؟">

                            <label class="block text-sm mb-1">الوصف المختصر</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.subtitle"
                                   class="w-full border p-2 rounded mb-2" placeholder="نقدم حلولًا احترافية...">

                            <label class="block text-sm mb-1">المميزات (ميزة في كل سطر)</label>
                            <textarea wire:model.defer="translations.{{ $lang->code }}.features"
                                      rows="4"
                                      class="w-full border p-2 rounded"
                                      placeholder="ميزة 1&#10;ميزة 2&#10;ميزة 3"></textarea>

                        @elseif ($sectionKey === 'services')
                            <label class="block text-sm mb-1">عنوان الخدمات</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.title"
                                   class="w-full border p-2 rounded mb-2" placeholder="خدماتنا الرقمية">

                            <label class="block text-sm mb-1">الوصف</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.subtitle"
                                   class="w-full border p-2 rounded mb-2" placeholder="نوفر لك أفضل الخدمات...">
                        @elseif ($sectionKey === 'works')
                            <label class="block text-sm mb-1">عنوان الخدمات</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.title"
                                   class="w-full border p-2 rounded mb-2" placeholder="خدماتنا الرقمية">

                            <label class="block text-sm mb-1">الوصف</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.subtitle"
                                   class="w-full border p-2 rounded mb-2" placeholder="نوفر لك أفضل الخدمات...">
                        @elseif ($sectionKey === 'home-works')
                            <label class="block text-sm mb-1">عنوان الخدمات</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.title"
                                   class="w-full border p-2 rounded mb-2" placeholder="خدماتنا الرقمية">

                            <label class="block text-sm mb-1">الوصف</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.subtitle"
                                   class="w-full border p-2 rounded mb-2" placeholder="نوفر لك أفضل الخدمات...">

                            <label class="block text-sm mb-1">نص الزر الاول</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_text-1"
                                    class="w-full border p-2 rounded mb-2" placeholder="مثال: اكتشف الآن">

                            <label class="block text-sm mb-1">رابط الزر الاول</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_url-1"
                                    class="w-full border p-2 rounded" placeholder="/works">
                        @elseif ($sectionKey === 'banner')
                            <label class="block text-sm mb-1">العنوان</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.title"
                                   class="w-full border p-2 rounded mb-2" placeholder="خدماتنا الرقمية">

                            <label class="block text-sm mb-1">الوصف</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.subtitle"
                                   class="w-full border p-2 rounded mb-2" placeholder="نوفر لك أفضل الخدمات...">

                            <label class="block text-sm mb-1">نص الزر الاول</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_text-1"
                                    class="w-full border p-2 rounded mb-2" placeholder="مثال: اكتشف الآن">

                            <label class="block text-sm mb-1">رابط الزر الاول</label>
                            <input type="text" wire:model.defer="translations.{{ $lang->code }}.button_url-1"
                                    class="w-full border p-2 rounded" placeholder="https://example.com">
                        @endif
                    </div>
                @endforeach
            </div>

            <button wire:click="addSection"
                    class="bg-primary text-white px-6 py-2 rounded hover:bg-primary/90 transition">
                إضافة السكشن
            </button>


        @endif
    </div>
    @push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    console.log("✅ سكربت SweetAlert جاهز");

    window.addEventListener('confirm-delete-section', event => {
        console.log("📢 تم استقبال حدث التأكيد", event.detail);

        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'لن تتمكن من التراجع بعد الحذف!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.emit('deleteSection', { sectionId: event.detail.sectionId }); // 👈 هنا
            }
        });
    });
</script>
@endpush
</div>
