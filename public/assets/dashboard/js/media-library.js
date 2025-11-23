document.addEventListener('DOMContentLoaded', () => {
    const { baseUrl, csrfToken } = window.MEDIA_CONFIG || {};

    // عناصر DOM
    const gridEl = document.getElementById('media-grid');
    const dropzoneEl = document.getElementById('dropzone');
    const fileInputEl = document.getElementById('file-input');
    const uploadBtnEl = document.getElementById('btn-upload');
    const loadMoreBtnEl = document.getElementById('btn-load-more');
    const loadingEl = document.getElementById('media-loading');
    const emptyEl = document.getElementById('media-empty');
    const searchInputEl = document.getElementById('search-input');
    const filterButtons = document.querySelectorAll('.filter-btn');

    // عناصر التفاصيل (Sidebar)
    const detailsEmptyEl = document.getElementById('details-empty');
    const detailsPanelEl = document.getElementById('details-panel');
    const detailsPreviewEl = document.getElementById('details-preview');
    const detailsTypeEl = document.getElementById('details-type');
    const detailsSizeEl = document.getElementById('details-size');
    const detailsDimensionsEl = document.getElementById('details-dimensions');
    const detailsPathEl = document.getElementById('details-path');
    const detailsOriginalNameEl = document.getElementById('details-original-name');
    const detailsTitleEl = document.getElementById('details-title');
    const detailsAltEl = document.getElementById('details-alt');
    const detailsCaptionEl = document.getElementById('details-caption');
    const detailsDescriptionEl = document.getElementById('details-description');
    const detailsFormEl = document.getElementById('details-form');
    const deleteBtnEl = document.getElementById('btn-delete');

    // لو الصفحة مش هي صفحة المكتبة أو الـ config ناقص
    if (!gridEl || !baseUrl) {
        return;
    }

    // الحالة الداخلية
    let currentPage = 1;
    let lastPage = 1;
    let currentFilterType = '';
    let currentSearch = '';
    let isLoading = false;       // خاص بتحميل القائمة فقط
    let selectedMedia = null;

    // 🔹 Helper: Debounce
    const debounce = (fn, delay = 300) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    };

    // 🔹 Helper: Toast (بدون jQuery)
    const showToast = (message, type = 'info') => {
        const colors = {
            info: 'bg-slate-900 text-white',
            success: 'bg-emerald-600 text-white',
            warning: 'bg-amber-500 text-white',
            error: 'bg-rose-600 text-white',
        };

        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className =
                'fixed bottom-4 right-4 z-[100] space-y-2 ltr:right-4 rtl:left-4';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className =
            `pointer-events-auto min-w-[200px] max-w-xs rounded-xl px-4 py-3 text-sm shadow-lg ring-1 ring-black/5 opacity-0 translate-y-2 transition-all duration-200 ${colors[type] || colors.info}`;
        toast.innerHTML = `
            <div class="flex items-start gap-3">
                <span class="mt-0.5">${message}</span>
                <button class="ml-auto text-white/70 hover:text-white" aria-label="Close">&times;</button>
            </div>
        `;

        container.appendChild(toast);

        // دخول سلس
        requestAnimationFrame(() => {
            toast.classList.remove('opacity-0', 'translate-y-2');
            toast.classList.add('opacity-100', 'translate-y-0');
        });

        const timeout = setTimeout(dismiss, 2500);

        function dismiss() {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', 'translate-y-2');

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 200);
        }

        const closeBtn = toast.querySelector('button');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                clearTimeout(timeout);
                dismiss();
            });
        }
    };

    // 🔹 Helper: إدارة حالة التحميل للقائمة
    const setLoading = (state, reset = false) => {
        isLoading = state;

        if (reset) {
            gridEl.innerHTML = '';
        }

        if (state) {
            loadingEl.classList.remove('hidden');
            emptyEl.classList.add('hidden');
            loadMoreBtnEl.classList.add('hidden');
        } else {
            loadingEl.classList.add('hidden');
        }
    };

    // 🔹 Helper: تنسيق حجم الملف
    const formatBytes = (bytes) => {
        if (!bytes && bytes !== 0) return '';
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 Bytes';
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return `${(bytes / Math.pow(1024, i)).toFixed(2)} ${sizes[i]}`;
    };

    // 🔹 تحميل البيانات من الـ API
    const loadMedia = async (page = 1, append = false) => {
        if (isLoading) return;
        setLoading(true, !append);

        const params = new URLSearchParams();
        params.set('page', page);
        if (currentFilterType) params.set('type', currentFilterType);
        if (currentSearch) params.set('search', currentSearch);
        // كسر الكاش
        params.set('_', Date.now().toString());

        try {
            const res = await fetch(`${baseUrl}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!res.ok) {
                throw new Error('Failed to load media');
            }

            const json = await res.json();
            currentPage = json.current_page || 1;
            lastPage = json.last_page || 1;

            const items = json.data || [];

            if (!append) {
                gridEl.innerHTML = '';
            }

            if (!items.length && currentPage === 1) {
                emptyEl.classList.remove('hidden');
            } else {
                emptyEl.classList.add('hidden');
            }

            renderMediaItems(items);

            if (currentPage < lastPage) {
                loadMoreBtnEl.classList.remove('hidden');
            } else {
                loadMoreBtnEl.classList.add('hidden');
            }
        } catch (e) {
            console.error(e);
            showToast('حدث خطأ أثناء تحميل الوسائط، حاول مجددًا.', 'error');
        } finally {
            setLoading(false);
        }
    };

    // 🔹 رسم العناصر في الشبكة
    const renderMediaItems = (items) => {
        items.forEach((item) => {
            const isImage =
                item.file_type === 'image' ||
                (item.mime_type && item.mime_type.startsWith('image/'));

            const imageUrl = item.url || `/storage/${item.file_path}`;
            const name =
                item.file_original_name ||
                item.file_name ||
                'بدون اسم';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className =
                'media-item group relative w-full aspect-square rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden bg-gray-50 dark:bg-gray-900 text-left';
            btn.dataset.id = item.id;

            let inner = '';

            if (isImage) {
                inner += `
                    <img src="${imageUrl}" alt="${name}"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                `;
            } else {
                inner += `
                    <div class="w-full h-full flex items-center justify-center text-[11px] text-gray-500 dark:text-gray-300">
                        <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            ${(item.file_extension || '').toUpperCase() || 'FILE'}
                        </span>
                    </div>
                `;
            }

            inner += `
                <div class="absolute inset-x-0 bottom-0 bg-black/40 text-[10px] text-white px-2 py-1 truncate">
                    ${name}
                </div>
            `;

            btn.innerHTML = inner;

            btn.addEventListener('click', () => {
                document
                    .querySelectorAll('.media-item')
                    .forEach((el) =>
                        el.classList.remove('ring-2', 'ring-indigo-500')
                    );
                btn.classList.add('ring-2', 'ring-indigo-500');

                selectMedia({
                    id: item.id,
                    url: imageUrl,
                    name,
                    mime_type: item.mime_type,
                    size: item.size,
                    width: item.width,
                    height: item.height,
                    path: item.file_path,
                    file_type: item.file_type,
                    alt: item.alt,
                    title: item.title,
                    caption: item.caption,
                    description: item.description,
                    readable_size: item.readable_size,
                });
            });

            gridEl.appendChild(btn);
        });
    };

    // 🔹 اختيار عنصر وتعبئة الـ Sidebar
    const selectMedia = (item) => {
        selectedMedia = item;

        if (!detailsPanelEl || !detailsEmptyEl) return;

        detailsEmptyEl.classList.add('hidden');
        detailsPanelEl.classList.remove('hidden');

        if (item.file_type === 'image' || (item.mime_type && item.mime_type.startsWith('image/'))) {
            detailsPreviewEl.src = item.url;
        } else {
            detailsPreviewEl.src = '';
        }

        detailsTypeEl.textContent = item.mime_type || item.file_type || '—';
        detailsSizeEl.textContent = item.readable_size || formatBytes(item.size);
        if (item.width && item.height) {
            detailsDimensionsEl.textContent = `${item.width} × ${item.height}`;
        } else {
            detailsDimensionsEl.textContent = '—';
        }
        detailsPathEl.textContent = item.path || '';

        detailsOriginalNameEl.value = item.name || '';
        detailsTitleEl.value = item.title || '';
        detailsAltEl.value = item.alt || '';
        detailsCaptionEl.value = item.caption || '';
        detailsDescriptionEl.value = item.description || '';
    };

    // 🔹 رفع الملفات
    const uploadFiles = async (files) => {
        if (!files || !files.length) return;

        const formData = new FormData();
        Array.from(files).forEach((file) => formData.append('files[]', file));

        // نعرض مؤشر التحميل فقط، بدون تغيير isLoading
        if (loadingEl) {
            loadingEl.classList.remove('hidden');
        }

        try {
            const res = await fetch(baseUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            if (!res.ok) {
                throw new Error('Upload failed');
            }

            showToast('تم رفع الملفات بنجاح.', 'success');

            // بعد الرفع نعيد تحميل الصفحة الأولى مباشرة
            currentPage = 1;
            lastPage = 1;
            await loadMedia(1, false);
        } catch (e) {
            console.error(e);
            showToast('فشل رفع الملفات، حاول مرة أخرى.', 'error');
        } finally {
            if (loadingEl) {
                loadingEl.classList.add('hidden');
            }
        }
    };

    // 🔹 تحديث بيانات الوسيط (الميتا)
    const updateDetails = async () => {
        if (!selectedMedia) return;

        const payload = {
            alt: detailsAltEl.value || null,
            title: detailsTitleEl.value || null,
            caption: detailsCaptionEl.value || null,
            description: detailsDescriptionEl.value || null,
        };

        try {
            const res = await fetch(`${baseUrl}/${selectedMedia.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!res.ok) throw new Error('Update failed');

            showToast('تم حفظ التعديلات.', 'success');
            await loadMedia(currentPage);
        } catch (e) {
            console.error(e);
            showToast('فشل حفظ التعديلات، حاول مجددًا.', 'error');
        }
    };

    // 🔹 حذف ملف
    const deleteSelected = async () => {
        if (!selectedMedia) return;

        if (!confirm('هل أنت متأكد من حذف هذا الملف؟')) return;

        try {
            const res = await fetch(`${baseUrl}/${selectedMedia.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            if (!res.ok) throw new Error('Delete failed');

            showToast('تم حذف الملف.', 'success');
            selectedMedia = null;
            detailsPanelEl.classList.add('hidden');
            detailsEmptyEl.classList.remove('hidden');

            currentPage = 1;
            await loadMedia(1);
        } catch (e) {
            console.error(e);
            showToast('فشل حذف الملف، حاول مجددًا.', 'error');
        }
    };

    // 🔹 أحداث الواجهة

    // زر رفع
    if (uploadBtnEl && fileInputEl) {
        uploadBtnEl.addEventListener('click', () => fileInputEl.click());
        fileInputEl.addEventListener('change', (e) => uploadFiles(e.target.files));
    }

    // Drag & Drop
    if (dropzoneEl) {
        ['dragenter', 'dragover'].forEach((evt) =>
            dropzoneEl.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzoneEl.classList.add('border-indigo-500', 'bg-indigo-50/60');
            })
        );

        ['dragleave', 'drop'].forEach((evt) =>
            dropzoneEl.addEventListener(evt, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzoneEl.classList.remove('border-indigo-500', 'bg-indigo-50/60');
            })
        );

        dropzoneEl.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            uploadFiles(files);
        });
    }

    // تحميل المزيد
    if (loadMoreBtnEl) {
        loadMoreBtnEl.addEventListener('click', () => {
            if (currentPage < lastPage) {
                loadMedia(currentPage + 1, true);
            }
        });
    }

    // الفلاتر
    if (filterButtons.length) {
        filterButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                filterButtons.forEach((b) =>
                    b.classList.remove(
                        'bg-indigo-50',
                        'border-indigo-500',
                        'text-indigo-600'
                    )
                );
                btn.classList.add('bg-indigo-50', 'border-indigo-500', 'text-indigo-600');

                currentFilterType = btn.dataset.filterType || '';
                currentPage = 1;
                loadMedia(1);
            });
        });
    }

    // البحث
    if (searchInputEl) {
        searchInputEl.addEventListener(
            'input',
            debounce((e) => {
                currentSearch = e.target.value.trim();
                currentPage = 1;
                loadMedia(1);
            }, 400)
        );
    }

    // حفظ التعديلات
    if (detailsFormEl) {
        detailsFormEl.addEventListener('submit', (e) => {
            e.preventDefault();
            updateDetails();
        });
    }

    // حذف
    if (deleteBtnEl) {
        deleteBtnEl.addEventListener('click', (e) => {
            e.preventDefault();
            deleteSelected();
        });
    }

    // 🔹 تحميل أولي
    loadMedia(1);
});
