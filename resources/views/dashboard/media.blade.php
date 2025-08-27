<x-dashboard-layout>
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

    <div class="p-6 bg-white dark:bg-gray-900 min-h-screen">
        <h1 class="text-2xl font-bold mb-6">📁 مكتبة الوسائط</h1>
        @can('create', 'App\\Models\\Media')
            <form id="uploadForm" enctype="multipart/form-data" class="mb-3">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="file" name="image" id="imageInput" class="form-control mb-2" required>
                <button type="submit" class="btn btn-primary">رفع صورة</button>
            </form>
        @endcan

        <div id="mediaGrid" class="masonry">
            {{-- الصور ستُملأ تلقائيًا عبر jQuery --}}
        </div>
    </div>

    @can('delete', 'App\\Models\\Media')
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog"
            aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تأكيد الحذف</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
    @endcan
    <!-- مودال التعديل -->
    @can('edit', 'App\\Models\\Media')
        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <form id="editForm" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل بيانات الوسيط</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="grid grid-cols-12 gap-6">

                            <!-- صورة المعاينة -->
                            <div class="col-span-6">
                                <img id="editPreview" src="" alt="preview" class="img-fluid rounded shadow border"
                                    style="max-height: 300px;">

                                <h6 class="fw-bold mb-3" style="font-size: 1.1rem; color: #333;">تفاصيل الوسيط</h6>

                                <div class="mb-4"
                                    style="font-size: 0.9rem; color: #555; background-color: #f8f9fa; padding: 1rem; border-radius: 8px; border: 1px solid #dee2e6;">
                                    <div style="margin-bottom: 0.5rem;">
                                        <strong style="min-width: 60px; display: inline-block;">الاسم:</strong>
                                        <span id="infoName">---</span>
                                    </div>
                                    <div style="margin-bottom: 0.5rem;">
                                        <strong style="min-width: 60px; display: inline-block;">النوع:</strong>
                                        <span id="infoMime">---</span>
                                    </div>
                                    <div style="margin-bottom: 0.5rem;">
                                        <strong style="min-width: 60px; display: inline-block;">الحجم:</strong>
                                        <span id="infoSize">---</span> KB
                                    </div>
                                    <div style="margin-bottom: 0;">
                                        <strong style="min-width: 60px; display: inline-block;">الرابط:</strong>
                                        <input type="text" id="infoURL"
                                            class="form-control form-control-sm d-inline-block mt-1"
                                            style="width: 100%; font-size: 0.8rem; color: #6c757d; background-color: #e9ecef;"
                                            readonly onclick="navigator.clipboard.writeText(this.value)">
                                    </div>
                                </div>
                            </div>

                            <!-- التفاصيل -->
                            <div class="col-span-6">
                                <input type="hidden" id="editId">

                                <div class="mb-3">
                                    <label class="form-label">Alt Text</label>
                                    <input type="text" id="editAlt" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" id="editTitle" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Caption</label>
                                    <textarea id="editCaption" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea id="editDescription" class="form-control" rows="2"></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn btn-secondary" data-pc-modal-dismiss="#editModal"
                            id="closeEditModal">إلغاء</button>
                        <button type="submit" class="btn btn-success">💾 حفظ التعديلات</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan


    <!-- زر سري لفتح مودال التعديل -->
    @can('edit', 'App\\Models\\Media')
        <button type="button" class="btn btn-primary d-none hidden" data-pc-toggle="modal" data-pc-target="#editModal"
            id="openEditModalBtn"></button>
    @endcan

    <!-- زر سري لفتح مودال الحذف -->
    @can('delete', 'App\\Models\\Media')
        <button type="button" class="btn btn-primary d-none hidden" data-pc-toggle="modal"
            data-pc-target="#confirmDeleteModal" id="openDeleteModalBtn"></button>
    @endcan

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                loadMedia();

                console.log($().modal); // لازم تطلع function

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
    <div class="masonry-item position-relative overflow-hidden">
        <img src="/storage/${item.file_path}" class="img-fluid media-image">
        <div class="media-actions position-absolute top-0 end-0 p-2" style="display: none;">
            @can('edit', 'App\\Models\\Media')
            <button class="btn btn-sm btn-light border rounded-circle edit-btn" data-id="${item.id}" data-name="${item.name}" title="تعديل">
                <i class="fas fa-pen text-secondary"></i>
            </button>
            @endcan
            @can('delete', 'App\\Models\\Media')
            <button class="btn btn-sm btn-light border rounded-circle me-1 delete-btn" data-id="${item.id}" title="حذف">
                <i class="fas fa-trash text-danger"></i>
            </button>
            @endcan
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

                // فتح مودال تعديل
                $(document).on('click', '.edit-btn', function() {
                    const id = $(this).data('id');

                    $.get(`{{ route('dashboard.media.edit', ':id') }}`.replace(':id', id), function(data) {
                        $('#editId').val(data.id);
                        $('#infoName').text(data.name);
                        $('#infoMime').text(data.mime_type);
                        $('#infoSize').text((data.size / 1024).toFixed(2));
                        $('#infoURL').val('/storage/' + data.file_path);

                        $('#editPreview').attr('src', '/storage/' + data.file_path).attr('alt', data
                            .alt || '');
                        $('#editAlt').val(data.alt || '');
                        $('#editTitle').val(data.title || '');
                        $('#editCaption').val(data.caption || '');
                        $('#editDescription').val(data.description || '');
                        // فتح المودال باستخدام الزر السري
                        document.getElementById('openEditModalBtn').click();
                    });
                });


                // تنفيذ التعديل
                $('#editForm').submit(function(e) {
                    e.preventDefault();
                    const id = $('#editId').val();
                    $.ajax({
                        url: `{{ route('dashboard.media.update', ':id') }}`.replace(':id', id),
                        method: 'PUT',
                        data: {
                            _token: '{{ csrf_token() }}',
                            alt: $('#editAlt').val(),
                            title: $('#editTitle').val(),
                            caption: $('#editCaption').val(),
                            description: $('#editDescription').val()
                        },
                        success: function() {
                            // $('#editModal').modal('hide');
                            $('#closeEditModal').click();
                            loadMedia();
                        }
                    });
                });
            });
        </script>
    @endpush
</x-dashboard-layout>
