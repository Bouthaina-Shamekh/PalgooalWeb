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
            <h2 class="text-2xl font-bold">إدارة المعرض</h2>
            <button wire:click="showAdd" class="btn btn-primary">+ إضافة معرض</button>
        </div>

        <table class="table-auto w-full text-right border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2">#</th>
                    <th class="p-2">الصورة</th>
                    <th class="p-2">العنوان ({{ app()->getLocale() }})</th>
                    <th class="p-2">النوع</th>
                    <th class="p-2">الترتيب</th>
                    <th class="p-2">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($portfolios as $portfolio)
                    <tr class="border-t">
                        <td class="p-2">{{ $loop->iteration }}</td>
                        <td class="p-2">
                            <img src="{{ asset('storage/' . $portfolio->image) }}" class="w-10 h-10">
                        </td>
                        <td class="p-2">
                            {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->title }}
                        </td>
                        <td class="p-2">{{ $portfolio->translations()->where('locale', app()->getLocale())->first()->type }}</td>
                        <td class="p-2">{{ $portfolio->order }}</td>
                        <td class="p-2 space-x-2">
                            <button wire:click="showEdit({{ $portfolio->id }})" class="btn btn-sm btn-warning">تعديل</button>
                            <button onclick="confirmDeletePortfolio({{ $portfolio->id }})" class="btn btn-danger">حذف</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $portfolios->links() }}
        </div>
    @endif

    {{-- Add/Edit View --}}
    @if (in_array($mode, ['add', 'edit']))
        <div class="mb-4">
            <h2 class="text-xl font-bold">{{ $mode === 'edit' ? 'تعديل معرض' : 'إضافة معرض' }}</h2>
        </div>

        <form wire:submit.prevent="save" class="grid grid-cols-12 gap-6">
            {{-- الصورة --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">الصورة</label>
                <input type="file" wire:model="portfolio.default_image" class="form-control">
                @if (isset($portfolio['default_image']) && $portfolio['default_image'] != null && $portfolio['default_image'] != '')
                    <img src="{{ asset('storage/' . $portfolio['default_image']) }}" class="mt-2 w-12 h-12">
                @endif
                @error('portfolio.default_image') <span class="text-red-600">{{ $message }}</span> @enderror
            </div>

            {{-- الصور --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">الصور</label>
                <input type="file" wire:model="portfolio.imagesMultiple" class="form-control" multiple>
                @if (isset($portfolio['imagesMultiple']) && $portfolio['imagesMultiple'] != null && $portfolio['imagesMultiple'] != '')
                    @foreach ($portfolio['imagesMultiple'] as $image)
                        <img src="{{ asset('storage/' . $image) }}" class="mt-2 w-12 h-12">
                    @endforeach
                @endif
                @error('portfolio.imagesMultiple') <span class="text-red-600">{{ $message }}</span> @enderror
            </div>
            {{-- الترتيب --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">ترتيب الظهور</label>
                <input type="number" wire:model="portfolio.order" class="form-control">
                @error('portfolio.order') <span class="text-red-600">{{ $message }}</span> @enderror
            </div>

            {{-- Date --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">التاريخ</label>
                <input type="date" wire:model="portfolio.delivery_date" class="form-control">
                @error('portfolio.delivery_date') <span class="text-red-600">{{ $message }}</span> @enderror
            </div>

            {{-- الترجمات --}}
            <div class="col-span-12 grid grid-cols-{{ count($languages) }} gap-4">
                @foreach ($languages as $index => $lang)
                    <div class="border p-4 rounded shadow-sm">
                        <h4 class="text-lg font-bold mb-2">{{ $lang->native }}</h4>

                        <input type="text"
                            class="form-control mb-2"
                            placeholder="العنوان"
                            wire:model="portfolioTranslations.{{ $index }}.title">
                        <input type="text"
                            class="form-control mb-2"
                            placeholder="النوع"
                            wire:model="portfolioTranslations.{{ $index }}.type">
                        <input type="text"
                            class="form-control mb-2"
                            placeholder="المواد"
                            wire:model="portfolioTranslations.{{ $index }}.materials">
                        <input type="text"
                            class="form-control mb-2"
                            placeholder="الرابط"
                            wire:model="portfolioTranslations.{{ $index }}.link">

                        <input type="hidden" wire:model="portfolioTranslations.{{ $index }}.locale">
                    </div>
                @endforeach
            </div>

            <div class="col-span-12 text-right mt-6">
                <button type="button" wire:click="showIndex" class="btn btn-secondary">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function confirmDeletePortfolio(portfolioId) {
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
                Livewire.dispatch('deletePortfolioConfirmed', { id: portfolioId });
            }
        });
    }

    window.addEventListener('portfolio-deleted-success', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '✅ تم حذف المعرض بنجاح',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });

    window.addEventListener('portfolio-delete-failed', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: '❌ فشل حذف المعرض',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });
</script>



</div>
