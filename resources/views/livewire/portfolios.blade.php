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
    <style>
        .masonry {
            column-count: 4;
            column-gap: 1rem;
        }

        @media (max-width: 992px) {
            .masonry {
                column-count: 3;
            }
        }

        @media (max-width: 768px) {
            .masonry {
                column-count: 2;
            }
        }

        @media (max-width: 576px) {
            .masonry {
                column-count: 1;
            }
        }

        .masonry-item {
            position: relative;
            transition: transform 0.2s ease;
            border-radius: 10px;
            box-shadow: 1px 6px 8px rgba(0, 0, 0, 0.3);
            margin-bottom: 11px;
        }

        .masonry-item:hover {
            transform: scale(1.03);
            z-index: 2;
        }

        .media-actions {
            position: absolute;
            top: 0;
            right: -60px;
            display: flex;
            gap: 0.25rem;
            flex-direction: column;
            align-items: center;
            transition: 0.3s all;
        }

        .masonry-item:hover .media-actions {
            display: flex !important;
            right: 0;

        }

        .media-actions .btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .masonry-item img {
            width: 100%;
            height: auto;
            display: block;
        }

        .masonry-item .info {
            padding: 0.75rem;
            text-align: center;
        }

        .masonry-item .info small {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .masonry-item .actions {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
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
                    <input type="text" id="imageInput" wire:model="portfolio.default_image" class="form-control">
                    <button type="button" data-mode="single"
                        class="openMediaModal bg-primary text-white px-2 py-1 rounded text-sm">
                        اختر من الوسائط
                    </button>
                </div>

                @if ($portfolio['default_image'])
                    <img src="{{ asset('storage/' . $portfolio['default_image']) }}" class="mt-2 w-12 h-12">
                @endif

                @error('portfolio.default_image')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>


            {{-- الصور --}}
            <div class="col-span-6">
                <label class="block text-sm font-medium">الصور المتعددة</label>

                <div class="flex items-center gap-2">
                    <input type="text" id="imagesInput" wire:model="portfolio.images" class="form-control">
                    <button type="button" data-mode="multiple"
                        class="openMediaModal bg-primary text-white px-2 py-1 rounded text-sm">
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

                @error('portfolio.images')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
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

                        <select class="form-control mb-2"
                            wire:model="portfolioTranslations.{{ $index }}.status">
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

        {{-- مودال الوسائط --}}
        <div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-2xl font-bold mb-6">📁 مكتبة الوسائط</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeMediaModal"
                            data-pc-modal-dismiss="#mediaModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <form id="uploadForm" enctype="multipart/form-data" class="mb-3">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="file" name="image" id="imageInput" class="form-control mb-2" required>
                            <button type="submit" class="btn btn-primary">رفع صورة</button>
                        </form>
                        <div id="mediaGrid" class="masonry">
                            {{-- الصور ستُملأ تلقائيًا عبر jQuery --}}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="selectMediaBtn">اختيار</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog"
            aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تأكيد الحذف</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeDeleteModal"
                            data-pc-modal-dismiss="#confirmDeleteModal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        هل أنت متأكد من حذف هذه الصورة؟
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-pc-modal-dismiss="#confirmDeleteModal"
                            id="closeDeleteModal">إلغاء</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">نعم، حذف</button>
                    </div>
                </div>
            </div>
        </div>
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    let mediaMode = 'single'; // default
    let selectedImages = [];

    $(document).ready(function() {
        // عند فتح المودال
        $(document).on('click', '.openMediaModal', function() {
            mediaMode = $(this).data('mode');
            selectedImages = [];
            loadMedia();
            $('.modal').removeClass('show animate');
            $('#mediaModal').addClass('show animate');
            if(mediaMode === 'multiple') {
                $('#selectMediaBtn').show();
            }else{
                $('#selectMediaBtn').hide();
            }
        });

        // إغلاق المودال
        $(document).on('click', '#closeMediaModal', function() {
            $('.modal').removeClass('show animate');
        });

        // جلب الصور
        function loadMedia() {
            $.get("{{ route('dashboard.media.index') }}", function(data) {
                let html = '';
                data.forEach(item => {
                    html += `
                        <div class="masonry-item position-relative overflow-hidden border border-light rounded mb-2" data-path="${item.file_path}">
                            <img src="/storage/${item.file_path}" class="img-fluid media-image" style="cursor:pointer;">
                            <div class="media-actions position-absolute top-0 end-0 p-2" style="display: none;">
                                <button class="btn btn-sm btn-light border rounded-circle me-1 delete-btn" data-id="${item.id}" title="حذف">
                                    <i class="fas fa-trash text-danger"></i>
                                </button>
                            </div>
                            <div class="info text-center p-2">
                                <small>${item.name}</small>
                            </div>
                        </div>`;
                });
                $('#mediaGrid').html(html);
            });
        }

        // اختيار صورة
        $(document).on('click', '.masonry-item', function() {
            const path = $(this).data('path');

            if (mediaMode === 'single') {
                const input = document.getElementById('imageInput');
                input.value = path;
                input.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                $('#closeMediaModal').click();

            } else {
                // multiple mode
                if (!selectedImages.includes(path)) {
                    selectedImages.push(path);
                    $(this).addClass('border-primary border-2');
                } else {
                    selectedImages = selectedImages.filter(p => p !== path);
                    $(this).removeClass('border-primary border-2');
                }

            }
        });

        $(document).on('click', '#closeMediaModal, #selectMediaBtn', function() {
            if (mediaMode === 'multiple') {
                const input = document.getElementById('imagesInput');
                input.value = JSON.stringify(selectedImages);
                input.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                $('.modal').removeClass('show animate');
            }
        });

        // رفع صورة جديدة
        $('#uploadForm').submit(function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            $.ajax({
                url: "{{ route('dashboard.media.store') }}",
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    $('#imageInput').val('');
                    loadMedia();
                }
            });
        });
    });
</script>
