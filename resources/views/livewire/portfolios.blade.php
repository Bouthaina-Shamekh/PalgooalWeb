<div class="p-6">
    <style>
        ul[id^="type_suggestions_"] li {
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            line-height: 1.5;
        }

        ul[id^="type_suggestions_"] li:hover {
            background-color: #f3f3f3;
            font-weight: 500;
            border-radius: 5px;
        }

        ul[id^="type_suggestions_"] {
            border-radius: 6px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
            overflow-y: auto;
            max-height: 200px;
            width: 200px;
        }
    </style>
    {{-- SweetAlert Trigger --}}
    @if ($alert)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '{{ $alertType }}',
                    title: '{{ $alertType === 'success' ? 'نجاح' : 'خطأ' }}',
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
                    <th class="p-2">الحالة</th>
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
                            <img src="{{ asset('storage/' . $portfolio->default_image) }}" class="w-10 h-10">
                        </td>
                        <td class="p-2">
                            {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->title }}
                        </td>
                        <td class="p-2">
                            {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->status }}</td>
                        <td class="p-2">
                            {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->type }}</td>
                        <td class="p-2">{{ $portfolio->order }}</td>
                        <td class="p-2 space-x-2">
                            <button wire:click="showEdit({{ $portfolio->id }})"
                                class="btn btn-sm btn-warning">تعديل</button>
                            <button onclick="confirmDeletePortfolio({{ $portfolio->id }})"
                                class="btn btn-danger">حذف</button>
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
                <label class="block text-sm font-medium">الصورة الافتراضية</label>

                <div class="flex items-center gap-2">
                    <input type="file" wire:model="portfolio.default_image" class="form-control hidden">
                    <button type="button" wire:click="openMediaModal('single')" class="bg-primary text-white px-2 py-1 rounded text-sm">
                        اختر من الوسائط
                    </button>
                </div>

                @if ($portfolio['default_image'])
                    <img src="{{ asset('storage/' . $portfolio['default_image']) }}" class="mt-2 w-12 h-12">
                @endif

                @error('portfolio.default_image') <span class="text-red-600">{{ $message }}</span> @enderror
            </div>


            {{-- الصور --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">الصور المتعددة</label>

                <div class="flex items-center gap-2">
                    <input type="file" wire:model="portfolio.images" class="form-control hidden" multiple>
                    <button type="button" wire:click="openMediaModal('multiple')" class="bg-primary text-white px-2 py-1 rounded text-sm">
                        اختر من الوسائط
                    </button>
                </div>

                @if (!empty($portfolio['images']))
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach ($portfolio['images'] as $image)
                            <img src="{{ asset('storage/' . $image) }}" class="w-12 h-12 object-cover rounded">
                        @endforeach
                    </div>
                @endif

                @error('portfolio.images') <span class="text-red-600">{{ $message }}</span> @enderror
            </div>


            {{-- مدة التنفيذ بالأيام --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">مدة التنفيذ بالأيام</label>
                <input type="number" wire:model="portfolio.implementation_period_days" class="form-control">
                @error('portfolio.implementation_period_days')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>

            {{-- الترتيب --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">ترتيب الظهور</label>
                <input type="number" wire:model="portfolio.order" class="form-control">
                @error('portfolio.order')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>

            {{-- Client --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">العميل</label>
                <input type="text" wire:model="portfolio.client" class="form-control">
                @error('portfolio.client')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>

            {{-- slug --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">Slug</label>
                <input type="text" wire:model="portfolio.slug" class="form-control" required>
                @error('portfolio.slug')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>

            {{-- Date --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">التاريخ</label>
                <input type="date" wire:model="portfolio.delivery_date" class="form-control">
                @error('portfolio.delivery_date')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>

            {{-- الترجمات --}}
            <div class="col-span-12 grid grid-cols-{{ count($languages) }} gap-4">
                @foreach ($languages as $index => $lang)
                    <div class="border p-4 rounded shadow-sm position-relative">
                        <h4 class="text-lg font-bold mb-2">{{ $lang->native }}</h4>

                        <input type="text" class="form-control mb-2" placeholder="العنوان"
                            wire:model="portfolioTranslations.{{ $index }}.title">

                        <textarea class="form-control mb-2" placeholder="الوصف"
    wire:model="portfolioTranslations.{{ $index }}.description" rows="3"></textarea>


                        <input type="text" class="form-control mb-2" placeholder="النوع"
                            wire:model="portfolioTranslations.{{ $index }}.type"
                            id="type_input_{{ $lang->code }}" oninput="showSuggestions('{{ $lang->code }}')"
                            onfocus="showSuggestions('{{ $lang->code }}')"
                            onkeydown="handleTypeKeydown(event, '{{ $lang->code }}')" autocomplete="off">

                        <ul class="list-group shadow rounded border position-absolute"
                            id="type_suggestions_{{ $lang->code }}"
                            style="top: calc(100% + 4px); z-index: 1050; display: none;
                                   background: #fff; width: 200px; max-height: 200px;
                                   overflow-y: auto; box-shadow: 0 6px 12px rgba(0,0,0,0.15); border: 1px solid #ddd;">
                        </ul>

                        <input type="text" class="form-control mb-2" placeholder="المواد"
                            wire:model="portfolioTranslations.{{ $index }}.materials">
                        <input type="text" class="form-control mb-2" placeholder="الرابط"
                            wire:model="portfolioTranslations.{{ $index }}.link">

                        <select class="form-control mb-2" wire:model="portfolioTranslations.{{ $index }}.status">
                            <option value="">اختر الحالة</option>
                            @foreach ($statusSuggestions[$lang->code] as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>


                        <input type="hidden" wire:model="portfolioTranslations.{{ $index }}.locale">
                    </div>
                @endforeach
            </div>

            <div class="col-span-12 text-right mt-6">
                <button type="button" wire:click="showIndex" class="btn btn-secondary">إلغاء</button>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
        @if($showMediaSection)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-xl shadow-lg max-w-5xl w-full p-6 relative">

                <!-- زر إغلاق -->
                <button type="button" wire:click="$set('showMediaSection', false)"
                        class="absolute top-2 left-2 text-gray-500 hover:text-red-600 text-xl font-bold">&times;</button>

                <h2 class="text-lg font-semibold mb-4">
                    {{ $mediaMode === 'multiple' ? 'اختر صور متعددة' : 'اختر صورة واحدة' }}
                </h2>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 max-h-[350px] overflow-y-auto">
                    @foreach(\App\Models\Media::where('mime_type', 'like', 'image/%')->latest()->take(50)->get() as $media)
                        <div
                            wire:click="{{ $mediaMode === 'multiple' ? "toggleImageSelection('$media->file_path')" : "selectSingleImage('$media->file_path')" }}"
                            class="relative border rounded-xl overflow-hidden shadow-sm bg-white cursor-pointer transition transform hover:scale-[1.02] hover:shadow-md
                                {{ $mediaMode === 'multiple' && in_array($media->file_path, $selectedImages) ? 'ring-2 ring-primary' : '' }}"
                        >
                            <img src="{{ asset('storage/' . $media->file_path) }}"
                                class="w-full h-32 object-cover" loading="lazy">

                            @if($mediaMode === 'multiple' && in_array($media->file_path, $selectedImages))
                                <div class="absolute top-2 right-2 bg-primary text-white text-xs px-2 py-1 rounded-full shadow">محدد</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- زر تأكيد فقط لو متعدد -->
                @if($mediaMode === 'multiple')
                    <div class="mt-4 flex justify-end">
                        <button type="button" wire:click="confirmMultipleSelection"
                                class="bg-primary text-white px-4 py-2 rounded text-sm">تأكيد الاختيار</button>
                    </div>
                @endif
            </div>
        </div>
        @endif

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
                    Livewire.dispatch('deletePortfolioConfirmed', {
                        id: portfolioId
                    });
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
    <script>
        const allSuggestions = @json($typeSuggestions);

        function showSuggestions(locale) {
            const input = document.getElementById('type_input_' + locale);
            const list = document.getElementById('type_suggestions_' + locale);

            let allValue = input.value;
            let parts = allValue.split(/[,،]\s*/).map(v => v.trim()).filter(v => v.length);
            let current = parts[parts.length - 1]?.toLowerCase() || '';

            const usedValues = parts.map(v => v.toLowerCase());

            const filtered = (allSuggestions[locale] || []).filter(type => {
                const typeLower = type.toLowerCase();
                return typeLower.includes(current) && !usedValues.includes(typeLower);
            });

            if (!filtered.length) {
                list.style.display = 'none';
                list.innerHTML = '';
                return;
            }

            list.innerHTML = filtered.map(type => `
        <li class="list-group-item list-group-item-action"
            onclick="selectType('${locale}', '${type}')">
            ${type}
        </li>
    `).join('');
            list.style.display = 'block';
        }



        function selectType(locale, selectedValue) {
            const input = document.getElementById('type_input_' + locale);
            const list = document.getElementById('type_suggestions_' + locale);

            let allValue = input.value;
            let parts = allValue.split(/[,،]\s*/); // يفصل حسب الفواصل
            parts[parts.length - 1] = selectedValue; // استبدال آخر كلمة
            let cleaned = parts.filter(p => p.trim().length > 0); // إزالة الفارغات

            input.value = cleaned.join('، ') + '، '; // يعيد الصياغة ويضيف فاصلة
            list.style.display = 'none';
            list.innerHTML = '';
            input.dispatchEvent(new Event('input')); // تحديث Livewire
        }


        function handleTypeKeydown(e, locale) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const list = document.getElementById('type_suggestions_' + locale);
                const firstItem = list.querySelector('li');
                if (firstItem) {
                    selectType(locale, firstItem.innerText);
                }
            }
        }


        document.addEventListener('click', function(e) {
            document.querySelectorAll('ul[id^="type_suggestions_"]').forEach(list => {
                if (!list.contains(e.target) && !e.target.matches('input[id^="type_input_"]')) {
                    list.style.display = 'none';
                }
            });
        });
    </script>



</div>
