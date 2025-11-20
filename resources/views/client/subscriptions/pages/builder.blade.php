<x-client-layout>
    <div class="page-header mb-6">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('client.home') }}">لوحة العميل</a></li>
                <li class="breadcrumb-item"><a href="{{ route('client.subscriptions') }}">اشتراكاتي</a></li>
                <li class="breadcrumb-item" aria-current="page">Page Builder</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Page Builder - {{ $page->translations->firstWhere('locale', app()->getLocale())?->title ?? $page->slug }}</h2>
            </div>
        </div>
    </div>

    @if (session('ok'))
        <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="list-disc ms-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4 space-y-4">
            <div class="card p-4">
                <h4 class="font-semibold mb-3">الأقسام</h4>
                <ul id="sectionsList" class="space-y-2" data-reorder-url="{{ route('client.subscriptions.pages.sections.reorder', [$subscription, $page]) }}">
                    @foreach ($sections as $section)
                        <li class="border rounded-lg p-3 flex items-center justify-between bg-white cursor-move" data-id="{{ $section->id }}">
                            <div>
                                <p class="text-xs text-gray-500 uppercase">{{ $section->type }}</p>
                                <p class="font-semibold">{{ $section->translations->firstWhere('locale', app()->getLocale())?->title ?? $section->key }}</p>
                            </div>
                            <a href="{{ route('client.subscriptions.pages.builder', [$subscription, $page, 'section' => $section->id]) }}"
                                class="text-sm text-primary hover:underline">تعديل</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card p-4">
                <h4 class="font-semibold mb-3">إضافة قسم</h4>
                <form action="{{ route('client.subscriptions.pages.sections.add', [$subscription, $page]) }}" method="POST" class="space-y-3">
                    @csrf
                    <label class="block text-sm text-gray-600">اختر بلوك</label>
                    <select name="block_key" class="w-full rounded border px-3 py-2">
                        <option value="">-- اختر --</option>
                        @foreach ($blocks as $key => $block)
                            <option value="{{ $key }}">{{ $block['type'] }} - {{ $block['variant'] ?? $key }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary w-full">إضافة</button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-8">
            <div class="card p-4">
                @if ($selectedSection)
                    <h3 class="text-lg font-semibold mb-3">تعديل القسم: {{ $selectedSection->type }}</h3>
                    <form action="{{ route('client.subscriptions.sections.update', [$subscription, $selectedSection]) }}" method="POST" class="space-y-4" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">العنوان</label>
                            <input type="text" name="title" value="{{ $selectedSection->translations->firstWhere('locale', app()->getLocale())?->title }}"
                                class="w-full rounded border px-3 py-2">
                        </div>

                        @if ($selectedSection->type === 'hero')
                            @php
                                $heroContent = $selectedSection->translations->firstWhere('locale', app()->getLocale())?->content ?? [];
                            @endphp
                            <div class="grid md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">العنوان الفرعي</label>
                                    <input type="text" name="subtitle" value="{{ $heroContent['subtitle'] ?? '' }}" class="w-full rounded border px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">نص الزر</label>
                                    <input type="text" name="button_label" value="{{ $heroContent['button_label'] ?? '' }}" class="w-full rounded border px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">رابط الزر</label>
                                    <input type="text" name="button_url" value="{{ $heroContent['button_url'] ?? '' }}" class="w-full rounded border px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">صورة الخلفية</label>
                                    <input type="text" name="image" value="{{ $heroContent['image'] ?? '' }}" class="w-full rounded border px-3 py-2" placeholder="أو ارفع صورة">
                                    <div class="mt-2">
                                        <input type="file" name="background_image_file" class="text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">لون الخلفية</label>
                                    <input type="color" name="background_color" value="{{ data_get($heroContent, 'colors.background') ?? '#ef4444' }}" class="w-full h-10 rounded border">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">لون النص</label>
                                    <input type="color" name="text_color" value="{{ data_get($heroContent, 'colors.text') ?? '#ffffff' }}" class="w-full h-10 rounded border">
                                </div>
                            </div>
                        @elseif($selectedSection->type === 'menu')
                            @php
                                $menuContent = $selectedSection->translations->firstWhere('locale', app()->getLocale())?->content ?? [];
                                $items = $menuContent['items'] ?? [];
                            @endphp
                            <div class="space-y-3" id="menuItems">
                                @foreach ($items as $idx => $item)
                                    <div class="border rounded p-3 grid md:grid-cols-3 gap-2">
                                        <input type="text" name="items[{{ $idx }}][name]" value="{{ $item['name'] ?? '' }}" class="rounded border px-2 py-1" placeholder="الاسم">
                                        <input type="text" name="items[{{ $idx }}][description]" value="{{ $item['description'] ?? '' }}" class="rounded border px-2 py-1" placeholder="الوصف">
                                        <input type="text" name="items[{{ $idx }}][price]" value="{{ $item['price'] ?? '' }}" class="rounded border px-2 py-1" placeholder="السعر">
                                    </div>
                                @endforeach
                                <button type="button" class="btn btn-outline-primary text-sm" onclick="addMenuItem()">+ إضافة طبق</button>
                            </div>
                            <div class="grid md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">لون الخلفية</label>
                                    <input type="color" name="background_color" value="{{ data_get($menuContent, 'colors.background') ?? '#ffffff' }}" class="w-full h-10 rounded border">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">لون النص</label>
                                    <input type="color" name="text_color" value="{{ data_get($menuContent, 'colors.text') ?? '#1f2937' }}" class="w-full h-10 rounded border">
                                </div>
                            </div>
                        @elseif($selectedSection->type === 'testimonials')
                            @php
                                $tContent = $selectedSection->translations->firstWhere('locale', app()->getLocale())?->content ?? [];
                                $items = $tContent['items'] ?? [];
                            @endphp
                            <div class="space-y-3" id="testimonialItems">
                                @foreach ($items as $idx => $item)
                                    <div class="border rounded p-3 grid md:grid-cols-3 gap-2">
                                        <input type="text" name="items[{{ $idx }}][name]" value="{{ $item['name'] ?? '' }}" class="rounded border px-2 py-1" placeholder="الاسم">
                                        <input type="text" name="items[{{ $idx }}][text]" value="{{ $item['text'] ?? '' }}" class="rounded border px-2 py-1" placeholder="النص">
                                        <input type="number" min="1" max="5" name="items[{{ $idx }}][rating]" value="{{ $item['rating'] ?? 5 }}" class="rounded border px-2 py-1" placeholder="التقييم">
                                    </div>
                                @endforeach
                                <button type="button" class="btn btn-outline-primary text-sm" onclick="addTestimonial()">+ إضافة شهادة</button>
                            </div>
                            <div class="grid md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">لون الخلفية</label>
                                    <input type="color" name="background_color" value="{{ data_get($tContent, 'colors.background') ?? '#f9fafb' }}" class="w-full h-10 rounded border">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-600 mb-1">لون النص</label>
                                    <input type="color" name="text_color" value="{{ data_get($tContent, 'colors.text') ?? '#111827' }}" class="w-full h-10 rounded border">
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500">لا يوجد محرر لهذا النوع بعد. يمكنك إضافة التعديلات من لوحة التطوير.</p>
                        @endif

                        <div class="flex items-center gap-3">
                            <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                            @if (app()->environment('local'))
                                <a class="text-sm text-primary" href="{{ route('tenant.preview', $subscription->id) }}" target="_blank">معاينة في تبويب جديد</a>
                            @endif
                        </div>
                    </form>
                @else
                    <p class="text-gray-500">لا توجد أقسام بعد.</p>
                @endif
            </div>
            @if (app()->environment('local'))
                <div class="card p-4 mt-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="font-semibold">معاينة حية</h4>
                            <p class="text-xs text-gray-500">يتم إعادة تحميلها بعد الحفظ.</p>
                        </div>
                        <button type="button" onclick="reloadPreview()" class="btn btn-sm btn-outline-primary">إعادة تحميل</button>
                    </div>
                    <div class="aspect-[16/9] border rounded-xl overflow-hidden bg-gray-50">
                        <iframe id="tenantPreviewFrame"
                            src="{{ route('tenant.preview.page', [$subscription->id, $page->slug]) }}"
                            class="w-full h-full" frameborder="0"></iframe>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        const list = document.getElementById('sectionsList');
        if (list) {
            Sortable.create(list, {
                animation: 150,
                onEnd: function() {
                    const ids = Array.from(list.querySelectorAll('[data-id]')).map(li => li.dataset.id);
                    fetch(list.dataset.reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ order: ids })
                    });
                }
            });
        }
        function addMenuItem() {
            const wrapper = document.getElementById('menuItems');
            const idx = wrapper.querySelectorAll('.grid').length;
            const div = document.createElement('div');
            div.className = 'border rounded p-3 grid md:grid-cols-3 gap-2';
            div.innerHTML = `
                <input type="text" name="items[\${idx}][name]" class="rounded border px-2 py-1" placeholder="الاسم">
                <input type="text" name="items[\${idx}][description]" class="rounded border px-2 py-1" placeholder="الوصف">
                <input type="text" name="items[\${idx}][price]" class="rounded border px-2 py-1" placeholder="السعر">`;
            wrapper.insertBefore(div, wrapper.lastElementChild);
        }
        function addTestimonial() {
            const wrapper = document.getElementById('testimonialItems');
            const idx = wrapper.querySelectorAll('.grid').length;
            const div = document.createElement('div');
            div.className = 'border rounded p-3 grid md:grid-cols-3 gap-2';
            div.innerHTML = `
                <input type="text" name="items[\${idx}][name]" class="rounded border px-2 py-1" placeholder="الاسم">
                <input type="text" name="items[\${idx}][text]" class="rounded border px-2 py-1" placeholder="النص">
                <input type="number" min="1" max="5" name="items[\${idx}][rating]" value="5" class="rounded border px-2 py-1" placeholder="التقييم">`;
            wrapper.insertBefore(div, wrapper.lastElementChild);
        }

        @if (session('ok') && app()->environment('local'))
            document.addEventListener('DOMContentLoaded', () => {
                reloadPreview();
            });
        @endif

        function reloadPreview() {
            const frame = document.getElementById('tenantPreviewFrame');
            if (frame) {
                frame.src = frame.src;
            }
        }
    </script>
</x-client-layout>