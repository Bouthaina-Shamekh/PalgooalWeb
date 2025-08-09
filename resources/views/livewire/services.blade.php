<div class="p-6">
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
                            <button wire:click="showEdit({{ $service->id }})"
                                class="btn btn-sm btn-warning">تعديل</button>
                            <button onclick="confirmDeleteService({{ $service->id }})"
                                class="btn btn-danger">حذف</button>
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
                    <input type="text" id="iconInput" wire:model="service.icon" class="form-control hidden">
                    <button type="button" data-pc-toggle="modal"
                        data-pc-target="#mediaModal" id="openMediaModalBtn"
                        class="bg-primary text-white px-2 py-1 rounded text-sm">
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
                @error('service.order')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-span-6">
                <label class="block text-sm font-medium">رابط الخدمة</label>
                <input type="text" wire:model="service.url" class="form-control">
                @error('service.url')
                    <span class="text-red-600">{{ $message }}</span>
                @enderror
            </div>

            {{-- الترجمات --}}
            <div class="col-span-12 grid grid-cols-{{ count($languages) }} gap-4">
                @foreach ($languages as $index => $lang)
                    <div class="border p-4 rounded shadow-sm">
                        <h4 class="text-lg font-bold mb-2">{{ $lang->native }}</h4>

                        <input type="text" class="form-control mb-2" placeholder="العنوان"
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
                    {{-- <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn btn-secondary" data-pc-modal-dismiss="#editModal"
                            id="closeEditModal">إلغاء</button>
                        <button type="submit" class="btn btn-success">💾 حفظ التعديلات</button>
                    </div> --}}
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
                    Livewire.dispatch('deleteServiceConfirmed', {
                        id: serviceId
                    });
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $(document).on('click', '#openMediaModalBtn', function() {
            loadMedia();
            $('.modal').removeClass('show animate');
            $('#mediaModal').addClass('show animate');
        });
        $(document).on('click', '#closeMediaModal', function() {
            $('.modal').removeClass('show animate');
        });
        $(document).on('click', '#closeDeleteModal', function() {
            $('.modal').removeClass('show animate');
        });

        // رفع صورة
        $('#uploadForm').submit(function(e) {
            e.preventDefault();
            let formData = new FormData(this);
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

        // جلب الصور
        function loadMedia() {
            $.get("{{ route('dashboard.media.index') }}", function(data) {
                let html = '';
                data.forEach(item => {
                    html += `
                <div class="masonry-item position-relative overflow-hidden" data-path="${item.file_path}">
                    <img src="/storage/${item.file_path}" class="img-fluid media-image">
                    <div class="media-actions position-absolute top-0 end-0 p-2" style="display: none;">
                        <button class="btn btn-sm btn-light border rounded-circle me-1 delete-btn" data-id="${item.id}" title="حذف">
                            <i class="fas fa-trash text-danger"></i>
                        </button>
                    </div>
                    <div class="info text-center p-2">
                        <small>${item.name}</small>
                    </div>
                </div>
            `;
                });
                $('#mediaGrid').html(html);
            });
        }


        let deleteId = null;

        $(document).on('click', '.delete-btn', function() {
            deleteId = $(this).data('id');

            // افتح المودال بضغط الزر
            $('#openDeleteModalBtn').click();
        });

        $('#confirmDeleteBtn').click(function() {
            if (deleteId) {
                $.ajax({
                    url: `{{ route('dashboard.media.destroy', ':id') }}`.replace(':id',
                        deleteId),
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        $('#closeDeleteModal').click();
                        loadMedia();
                    }
                });
            }
        });
        // اختيار الصورة
        $(document).on('click', '.masonry-item', function() {
            let path = $(this).data('path');
            $('#closeMediaModal').click();
            const input = document.getElementById('iconInput');
            input.value = path;
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
</script>
