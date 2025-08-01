<div class="p-6 bg-white dark:bg-gray-900 min-h-screen">
    <h1 class="text-2xl font-bold text-primary mb-6 border-b pb-4">📁 مكتبة الوسائط</h1>

    <!-- منطقة السحب والإفلات -->
    <div
        class="border-2 border-dashed border-gray-300 bg-gray-50 dark:bg-gray-800 rounded-xl p-10 text-center transition hover:border-primary hover:bg-primary/5"
        x-data
        x-on:dragover.prevent
        x-on:drop.prevent="
            let files = $event.dataTransfer.files;
            $refs.fileInput.files = files;
            $refs.fileInput.dispatchEvent(new Event('change'));
        "
    >
        <p class="text-gray-600 dark:text-gray-300 text-sm">🖱️ اسحب الملفات هنا أو انقر للرفع</p>
        <label class="inline-block mt-4 px-5 py-2.5 bg-primary text-white text-sm font-medium rounded-md cursor-pointer hover:bg-primary/90 transition">
            اختر ملفات
            <input type="file" multiple wire:model="files" x-ref="fileInput" class="hidden" />
        </label>
    </div>

    <!-- شريط الأدوات -->
    <div class="flex flex-wrap md:flex-nowrap md:items-center justify-between gap-4 mt-10 mb-6">
        <label class="flex items-center gap-2 px-4 py-2 bg-primary text-white text-sm font-medium rounded cursor-pointer hover:bg-primary/90">
            📤 رفع ملفات
            <input type="file" wire:model="files" multiple class="hidden">
        </label>

        <input
            type="text"
            wire:model.debounce.300ms="search"
            placeholder="🔍 ابحث باسم الملف..."
            class="w-full md:w-1/3 border border-gray-300 rounded px-4 py-2 text-sm focus:ring focus:ring-primary/30 focus:outline-none"
        />

        <select wire:model="fileTypeFilter"
            class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-primary/30 focus:outline-none bg-white dark:bg-gray-800 text-gray-700 dark:text-white">
            <option value="">📂 كل الأنواع</option>
            <option value="images">🖼️ الصور</option>
            <option value="videos">🎥 الفيديو</option>
            <option value="documents">📄 المستندات</option>
            <option value="spreadsheets">📊 جداول البيانات</option>
            <option value="archives">🗂️ الأرشيف</option>
        </select>
    </div>

    <!-- الشبكة -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach($mediaItems as $media)
        <div 
            wire:click="selectMedia({{ $media->id }})"
            class="group relative border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-white dark:bg-gray-800 shadow hover:shadow-lg cursor-pointer transition transform hover:scale-[1.02]"
        >
            @if(Str::startsWith($media->mime_type, 'image/'))
                <img src="{{ $media->url }}" alt="{{ $media->name }}" class="w-full h-32 object-cover" loading="lazy">
            @else
                <div class="w-full h-32 flex items-center justify-center bg-gray-100 text-gray-600 text-xs dark:bg-gray-700 dark:text-gray-300">
                    {{ strtoupper($media->mime_type) }}
                </div>
            @endif

            <div class="p-2 text-sm text-gray-800 dark:text-gray-100 truncate border-t dark:border-gray-700">
                <p class="font-semibold truncate">{{ $media->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $media->created_at->format('Y-m-d') }}</p>
            </div>

            @if($selectedMedia && $selectedMedia->id === $media->id)
                <div class="absolute top-2 right-2 bg-primary text-white text-xs px-2 py-1 rounded-full shadow">✅ محدد</div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- المودال -->
    @if($showModal && $selectedMedia)
    <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/60 px-4" wire:click.self="closeModal">
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-5xl overflow-hidden relative flex flex-col md:flex-row">
            <button tabindex="0" wire:click="closeModal"
                class="absolute top-2 left-2 text-primary rounded-full w-8 h-8 flex items-center justify-center hover:text-secondary">
                ✕
            </button>

            <!-- المعاينة -->
            <div class="w-full md:w-1/2 bg-gray-50 dark:bg-gray-800 flex items-center justify-center p-4">
                @if(Str::startsWith($selectedMedia->mime_type, 'image/'))
                    <img src="{{ $selectedMedia->url }}" alt="{{ $selectedMedia->alt }}" class="max-h-[400px] w-auto object-contain rounded-md">
                @else
                    <div class="p-10 bg-white text-gray-500 text-sm text-center">
                        {{ strtoupper($selectedMedia->mime_type) }}
                    </div>
                @endif
            </div>

            <!-- التفاصيل -->
            <div class="w-full md:w-1/2 p-6 space-y-4 overflow-y-auto max-h-[90vh]">
                <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">تفاصيل الوسيط</h2>

                <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                    <div><strong>الاسم:</strong> {{ $selectedMedia->name }}</div>
                    <div><strong>النوع:</strong> {{ $selectedMedia->mime_type }}</div>
                    <div><strong>الحجم:</strong> {{ number_format($selectedMedia->size / 1024, 2) }} KB</div>
                    <div><strong>الرابط:</strong>
                        <input type="text" value="{{ $selectedMedia->url }}" readonly
                               class="w-full border px-2 py-1 rounded bg-gray-100 text-xs text-gray-500 mt-1">
                    </div>
                </div>

                <div class="space-y-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alt Text</label>
                        <input type="text" wire:model.defer="alt" class="w-full border rounded px-2 py-1">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input type="text" wire:model.defer="title" class="w-full border rounded px-2 py-1">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Caption</label>
                        <textarea wire:model.defer="caption" class="w-full border rounded px-2 py-1"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea wire:model.defer="description" class="w-full border rounded px-2 py-1"></textarea>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 pt-4">
                    <button wire:click="saveMediaDetails"
                            class="bg-primary text-white px-4 py-2 rounded hover:bg-primary/80">💾 حفظ</button>

                    <button onclick="navigator.clipboard.writeText('{{ $selectedMedia->url }}')"
                            class="bg-gray-600 text-white px-4 py-2 rounded">📎 نسخ الرابط</button>

                    <a href="{{ $selectedMedia->url }}" download
                       class="bg-yellow-500 text-white px-4 py-2 rounded">⬇️ تحميل</a>

                    <button wire:click="deleteMedia"
                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-500">🗑 حذف نهائي</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script src="https://unpkg.com/alpinejs" defer></script>
