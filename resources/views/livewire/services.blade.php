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
                            <button wire:click="delete({{ $service->id }})" class="btn btn-sm btn-danger">حذف</button>
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
                <input type="file" wire:model="service.icon" class="form-control">
                @if (isset($service['icon']) && !is_object($service['icon']))
                    <img src="{{ asset('storage/' . $service['icon']) }}" class="mt-2 w-12 h-12">
                @endif
                @error('service.icon') <span class="text-red-600">{{ $message }}</span> @enderror
            </div>

            {{-- الترتيب --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">ترتيب الظهور</label>
                <input type="number" wire:model="service.order" class="form-control">
                @error('service.order') <span class="text-red-600">{{ $message }}</span> @enderror
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
    @endif
</div>
