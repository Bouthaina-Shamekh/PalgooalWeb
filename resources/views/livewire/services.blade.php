<div class="p-6">
    {{-- SweetAlert Trigger --}}
    @if ($alert)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: '{{ $alertType }}',
                    title: '{{ $alertType === "success" ? "نجاح" : "خطأ" }}',
                    text: '{{ $alertMessage }}',
                    confirmButtonText: 'موافق'
                });
            });
        </script>
    @endif

    {{-- Index View --}}
    @if ($mode === 'index')
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold">إدارة الخدمات</h2>
            <button wire:click="showAdd" class="btn btn-primary">+ إضافة خدمة</button>
        </div>

        <table class="table-auto w-full text-right border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2">#</th>
                    <th class="p-2">الأيقونة</th>
                    <th class="p-2">العنوان ({{ app()->getLocale() }})</th>
                    <th class="p-2">الترتيب</th>
                    <th class="p-2">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($services as $service)
                    <tr class="border-t">
                        <td class="p-2">{{ $loop->iteration }}</td>
                        <td class="p-2">
                            <img src="{{ asset('storage/' . $service->icon) }}" class="w-10 h-10">
                        </td>
                        <td class="p-2">
                            {{ $service->translation()?->title }}
                        </td>
                        <td class="p-2">{{ $service->order }}</td>
                        <td class="p-2 space-x-2">
                            <button wire:click="showEdit({{ $service->id }})" class="btn btn-sm btn-warning">تعديل</button>
                            <button onclick="confirmDeleteService({{ $service->id }})" class="btn btn-danger">حذف</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $services->links() }}
        </div>
    @endif

    {{-- Add/Edit View --}}
    @if (in_array($mode, ['add', 'edit']))
        <div class="mb-4">
            <h2 class="text-xl font-bold">{{ $mode === 'edit' ? 'تعديل خدمة' : 'إضافة خدمة' }}</h2>
        </div>

        <form wire:submit.prevent="save" class="grid grid-cols-12 gap-6">
            {{-- أيقونة --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">الأيقونة</label>

                <div class="flex items-center gap-2">
                    <input type="file" wire:model="service.icon" class="form-control hidden">
                    <button type="button" wire:click="$set('showMediaSection', true)" class="bg-primary text-white px-2 py-1 rounded text-sm">
                        اختر من الوسائط أو ارفع جديد
                    </button>
                </div>

                @if ($service['icon'])
                    <img src="{{ asset('storage/' . $service['icon']) }}" class="mt-2 w-12 h-12">
                @endif
            </div>


            {{-- الترتيب --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">ترتيب الظهور</label>
                <input type="number" wire:model="service.order" class="form-control">
                @error('service.order') <span class="text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="col-span-6">
                <label class="block text-sm font-medium">رابط الخدمة</label>
                <input type="text" wire:model="service.url" class="form-control">
                @error('service.url') <span class="text-red-600">{{ $message }}</span> @enderror
            </div>

            {{-- الترجمات --}}
            <div class="col-span-12 grid grid-cols-{{ count($languages) }} gap-4">
                @foreach ($languages as $index => $lang)
                    <div class="border p-4 rounded shadow-sm">
                        <h4 class="text-lg font-bold mb-2">{{ $lang->native }}</h4>

                        <input type="text"
                            class="form-control mb-2"
                            placeholder="العنوان"
                            wire:model="serviceTranslations.{{ $index }}.title">

                        <textarea class="form-control" rows="3" placeholder="الوصف"
                            wire:model="serviceTranslations.{{ $index }}.description"></textarea>

                        <input type="hidden" wire:model="serviceTranslations.{{ $index }}.locale">
                    </div>
                @endforeach
            </div>

            <div class="col-span-12 text-right mt-6">
                <button type="button" wire:click="showIndex" class="btn btn-secondary">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>

        <!-- Modal -->
        @if($showMediaSection)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-xl shadow-lg max-w-4xl w-full p-6 relative">

                <!-- زر إغلاق -->
                <button type="button" wire:click="$set('showMediaSection', false)"
                    class="absolute top-2 left-2 text-gray-500 hover:text-red-600 text-xl font-bold">&times;</button>

                <h2 class="text-lg font-semibold mb-4">اختر صورة أو ارفع جديد</h2>

                <!-- رفع صورة جديدة -->
                <div class="mb-4">
                    <input type="file" wire:model="mediaUpload" class="form-input">
                    @error('mediaUpload') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- شبكة الصور داخل modal -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 max-h-[350px] overflow-y-auto">
                    @foreach(\App\Models\Media::where('mime_type', 'like', 'image/%')->latest()->take(50)->get() as $media)
                        <div
                            wire:click="selectImage('{{ $media->file_path }}')"
                            class="relative border rounded-xl overflow-hidden shadow-sm bg-white cursor-pointer transition transform hover:scale-[1.02] hover:shadow-md"
                        >
                            <img src="{{ asset('storage/' . $media->file_path) }}" alt="{{ $media->name }}"
                                class="w-full h-32 object-cover" loading="lazy">

                            <div class="p-2 text-sm text-gray-700 truncate border-t">
                                <p class="font-semibold truncate">{{ $media->name }}</p>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($media->created_at)->format('Y-m-d') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>


            </div>
        </div>
        @endif
    @endif

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function confirmDeleteService(serviceId) {
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
                Livewire.dispatch('deleteServiceConfirmed', { id: serviceId });
            }
        });
    }

    window.addEventListener('service-deleted-success', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '✅ تم حذف الخدمة بنجاح',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });

    window.addEventListener('service-delete-failed', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: '❌ فشل حذف الخدمة',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });
</script>



</div>
