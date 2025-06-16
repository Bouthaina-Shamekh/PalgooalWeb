<div class="p-6">
    <div class="mb-4">
        <input type="file" wire:model="files" multiple>
    </div>
    <!-- شبكة الوسائط -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach($mediaItems as $media)
        <div 
            wire:click="selectMedia({{ $media->id }})"
            class="relative border rounded-xl overflow-hidden shadow-sm bg-white cursor-pointer transition transform hover:scale-[1.02] hover:shadow-md
                {{ $selectedMedia && $selectedMedia->id === $media->id ? 'ring-2 ring-primary' : '' }}"
        >
            @if(Str::startsWith($media->mime_type, 'image/'))
                <img src="{{ $media->url }}" alt="{{ $media->name }}"
                     class="w-full h-32 object-cover" loading="lazy">
            @else
                <div class="w-full h-32 flex items-center justify-center bg-gray-100 text-gray-500 text-sm">
                    {{ strtoupper($media->mime_type) }}
                </div>
            @endif

            <div class="p-2 text-sm text-gray-700 truncate border-t">
                <p class="font-semibold truncate">{{ $media->name }}</p>
                <p class="text-xs text-gray-500">{{ $media->created_at->format('Y-m-d') }}</p>
            </div>

            @if($selectedMedia && $selectedMedia->id === $media->id)
                <div class="absolute top-2 right-2 bg-primary text-white text-xs px-2 py-1 rounded-full shadow">محدد</div>
            @endif
        </div>
        @endforeach
    </div>
    @if($showModal && $selectedMedia)
    <div class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/60 px-4" wire:click.self="closeModal">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-5xl overflow-hidden relative flex flex-col md:flex-row">
            <!-- زر الإغلاق -->
            <button tabindex="0" wire:click="closeModal"
            class="absolute top-2 left-2 text-primary rounded-full w-8 h-8 flex items-center justify-center hover:text-secondary">
            ✕
        </button>

        <!-- المعاينة -->
        <div class="w-full md:w-1/2 bg-gray-50 flex items-center justify-center p-4">
            @if(Str::startsWith($selectedMedia->mime_type, 'image/'))
                <img src="{{ $selectedMedia->url }}" alt="{{ $selectedMedia->alt }}"
                     class="max-h-[400px] w-auto object-contain rounded-md">
            @else
                <div class="p-10 bg-white text-gray-500 text-sm text-center">
                    {{ strtoupper($selectedMedia->mime_type) }}
                </div>
            @endif
        </div>

        <!-- التفاصيل -->
        <div class="w-full md:w-1/2 p-6 space-y-4 overflow-y-auto max-h-[90vh]">
            <h2 class="text-lg font-bold">تفاصيل الوسيط</h2>

            <div class="text-sm text-gray-600 space-y-1">
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
                    <label class="block text-sm font-medium text-gray-700">Alt Text</label>
                    <input type="text" wire:model.defer="alt" class="w-full border rounded px-2 py-1">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input type="text" wire:model.defer="title" class="w-full border rounded px-2 py-1">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Caption</label>
                    <textarea wire:model.defer="caption" class="w-full border rounded px-2 py-1"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea wire:model.defer="description" class="w-full border rounded px-2 py-1"></textarea>
                </div>
            </div>

            <!-- الأزرار -->
            <div class="flex flex-wrap gap-3 pt-4">
                <button wire:click="saveMediaDetails"
                        class="bg-primary text-white px-4 py-2 rounded hover:bg-primary/80">💾 حفظ</button>

                <button onclick="navigator.clipboard.writeText('{{ $selectedMedia->url }}')"
                        class="bg-gray-600 text-primary px-4 py-2 rounded">📎 نسخ الرابط</button>

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


