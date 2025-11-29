document.addEventListener('DOMContentLoaded', () => {
    // إعدادات عامة (نستخدم نفس MEDIA_CONFIG المستخدمة في مكتبة الوسائط)
    const mediaConfig = window.MEDIA_CONFIG || {};
    const baseUrl = mediaConfig.baseUrl || '/admin/media';
    const csrfToken =
        mediaConfig.csrfToken ||
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // عناصر المودال
    const backdropEl = document.getElementById('media-picker-backdrop');
    const modalEl = document.getElementById('media-picker-modal');
    const gridEl = document.getElementById('media-picker-grid');
    const loadingEl = document.getElementById('media-picker-loading');
    const emptyEl = document.getElementById('media-picker-empty');
    const loadMoreBtnEl = document.getElementById('media-picker-load-more');

    const searchInputEl = document.getElementById('media-picker-search');
    const filterButtons = document.querySelectorAll('.media-picker-filter-btn');

    const selectionCountEl = document.getElementById('media-picker-selection-count');
    const clearSelectionBtnEl = document.getElementById('media-picker-clear');
    const cancelBtnEl = document.getElementById('media-picker-cancel');
    const closeBtnEl = document.getElementById('media-picker-close');
    const confirmBtnEl = document.getElementById('media-picker-confirm');

    const openButtons = document.querySelectorAll('.btn-open-media-picker');

    // عناصر الرفع من داخل الـ popup
    const uploadBtnEl = document.getElementById('media-picker-upload-btn');
    const fileInputEl = document.getElementById('media-picker-file-input');

    if (!modalEl || !gridEl || !openButtons.length) {
        // لا يوجد picker مستخدم في الصفحة
        return;
    }

    // الحالة الداخلية للـ Picker
    let pickerOpen = false;
    let currentPage = 1;
    let lastPage = 1;
    let currentFilterType = '';
    let currentSearch = '';
    let isLoading = false;

    // إعداد الزر الذي فتح الـ Picker
    let currentTargetInputId = null;
    let currentPreviewContainerId = null;
    let isMultiple = false;

    // العناصر المحددة
    const selectedItems = new Map(); // id → item

    // 🔹 Helper: Debounce
    const debounce = (fn, delay = 300) => {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), delay);
        };
    };

    // 🔹 Helper: Toast بسيط (مرئي)
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
                'fixed bottom-4 right-4 rtl:left-4 rtl:right-auto z-[99999] space-y-2';
            document.body.appendChild(container);
        }

        const el = document.createElement('div');
        el.className =
            `pointer-events-auto min-w-[200px] max-w-xs rounded-xl px-4 py-3 text-sm shadow-lg ring-1 ring-black/5 opacity-0 translate-y-2 transition-all duration-200 ${colors[type] || colors.info}`;
        el.innerHTML = `
            <div class="flex items-start gap-3">
                <span class="mt-0.5">${message}</span>
                <button class="ml-auto text-white/70 hover:text-white" aria-label="إغلاق">&times;</button>
            </div>
        `;

        container.appendChild(el);

        requestAnimationFrame(() => {
            el.classList.remove('opacity-0', 'translate-y-2');
            el.classList.add('opacity-100', 'translate-y-0');
        });

        const timeout = setTimeout(dismiss, 2500);
        function dismiss() {
            el.classList.remove('opacity-100', 'translate-y-0');
            el.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => el.remove(), 200);
        }

        el.querySelector('button')?.addEventListener('click', () => {
            clearTimeout(timeout);
            dismiss();
        });
    };

    // 🔹 إدارة المودال
    const openPicker = (config) => {
        currentTargetInputId = config.targetInputId;
        currentPreviewContainerId = config.previewContainerId;
        isMultiple = config.multiple;

        // إعادة الضبط
        currentPage = 1;
        lastPage = 1;
        currentFilterType = '';
        currentSearch = '';
        selectedItems.clear();
        updateSelectionUI();
        gridEl.innerHTML = '';
        if (emptyEl) emptyEl.classList.add('hidden');

        if (loadMoreBtnEl) {
            loadMoreBtnEl.classList.add('hidden'); // نخلي JS يتحكم فيها بعد أول تحميل
        }

        backdropEl.classList.remove('hidden');
        modalEl.classList.remove('hidden');
        modalEl.classList.add('flex');
        pickerOpen = true;

        loadMedia(1, false);
    };

    const closePicker = () => {
        pickerOpen = false;
        backdropEl.classList.add('hidden');
        modalEl.classList.add('hidden');
        modalEl.classList.remove('flex');
    };

    // 🔹 حالة التحميل
    const setLoading = (state, reset = false) => {
        isLoading = state;
        if (reset) {
            gridEl.innerHTML = '';
        }

        if (state) {
            if (loadingEl) loadingEl.classList.remove('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');
            if (loadMoreBtnEl) loadMoreBtnEl.classList.add('hidden');
        } else {
            if (loadingEl) loadingEl.classList.add('hidden');
        }
    };

    // 🔹 تحميل الوسائط من الـ API
    const loadMedia = async (page = 1, append = false) => {
        if (isLoading) return;
        setLoading(true, !append);

        const params = new URLSearchParams();
        params.set('page', page);
        // نجعل الـ per_page صغير عشان يظهر معنا أكثر من صفحة بسهولة
        params.set('per_page', '8');
        if (currentFilterType) params.set('type', currentFilterType);
        if (currentSearch) params.set('search', currentSearch);
        params.set('_', Date.now().toString()); // كسر الكاش

        try {
            const res = await fetch(`${baseUrl}?${params.toString()}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!res.ok) {
                throw new Error('Failed to load media for picker');
            }

            const json = await res.json();

            currentPage = json.current_page || 1;
            lastPage = json.last_page || 1;
            const items = json.data || [];

            if (!append) {
                gridEl.innerHTML = '';
            }

            if (!items.length && currentPage === 1) {
                if (emptyEl) emptyEl.classList.remove('hidden');
            } else {
                if (emptyEl) emptyEl.classList.add('hidden');
            }

            renderMediaItems(items);

            // ✅ التحكم في زر "تحميل المزيد" (يظهر دائماً بعد أول تحميل)
            if (loadMoreBtnEl) {
                loadMoreBtnEl.classList.remove('hidden');

                if (currentPage < lastPage && items.length > 0) {
                    loadMoreBtnEl.disabled = false;
                    loadMoreBtnEl.textContent = 'تحميل المزيد من الوسائط';
                } else {
                    loadMoreBtnEl.disabled = true;
                    loadMoreBtnEl.textContent = 'لا يوجد المزيد من الوسائط';
                }
            }
        } catch (e) {
            console.error(e);
            showToast('حدث خطأ أثناء تحميل الوسائط.', 'error');
        } finally {
            setLoading(false);
        }
    };

    // 🔹 رسم العناصر داخل الـ Grid
    const renderMediaItems = (items) => {
        items.forEach((item) => {
            const isImage =
                item.file_type === 'image' ||
                (item.mime_type && item.mime_type.startsWith('image/'));

            const imageUrl = item.url || `/storage/${item.file_path}`;
            const name =
                item.file_original_name || item.file_name || 'بدون اسم';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className =
                'media-picker-item group relative w-full aspect-square rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden bg-gray-50 dark:bg-gray-900 text-left';
            btn.dataset.id = item.id;

            if (selectedItems.has(item.id)) {
                btn.classList.add('ring-2', 'ring-indigo-500');
            }

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
                const alreadySelected = selectedItems.has(item.id);

                if (isMultiple) {
                    if (alreadySelected) {
                        selectedItems.delete(item.id);
                        btn.classList.remove('ring-2', 'ring-indigo-500');
                    } else {
                        selectedItems.set(item.id, {
                            id: item.id,
                            url: imageUrl,
                            name,
                            file_type: item.file_type,
                            mime_type: item.mime_type,
                        });
                        btn.classList.add('ring-2', 'ring-indigo-500');
                    }
                } else {
                    // وضع اختيار واحد فقط
                    selectedItems.clear();
                    document
                        .querySelectorAll('.media-picker-item')
                        .forEach((el) =>
                            el.classList.remove('ring-2', 'ring-indigo-500')
                        );

                    selectedItems.set(item.id, {
                        id: item.id,
                        url: imageUrl,
                        name,
                        file_type: item.file_type,
                        mime_type: item.mime_type,
                    });
                    btn.classList.add('ring-2', 'ring-indigo-500');
                }

                updateSelectionUI();
            });

            gridEl.appendChild(btn);
        });
    };

    // 🔹 تحديث واجهة التحديد
    const updateSelectionUI = () => {
        const count = selectedItems.size;
        if (selectionCountEl) {
            selectionCountEl.textContent = String(count);
        }

        if (clearSelectionBtnEl) {
            if (count > 0) {
                clearSelectionBtnEl.classList.remove('hidden');
            } else {
                clearSelectionBtnEl.classList.add('hidden');
            }
        }

        // تعطيل زر "استخدام العناصر المحددة" إذا لا يوجد أي عنصر
        if (confirmBtnEl) {
            confirmBtnEl.disabled = count === 0;
        }
    };

    const clearSelection = () => {
        selectedItems.clear();
        document
            .querySelectorAll('.media-picker-item')
            .forEach((el) =>
                el.classList.remove('ring-2', 'ring-indigo-500')
            );
        updateSelectionUI();
    };

    // 🔹 عند الضغط على "استخدام العناصر المحددة"
    const applySelection = () => {
        if (!currentTargetInputId) {
            closePicker();
            return;
        }

        const targetInput = document.getElementById(currentTargetInputId);
        const previewContainer = currentPreviewContainerId
            ? document.getElementById(currentPreviewContainerId)
            : null;

        const items = Array.from(selectedItems.values());

        if (!items.length || !targetInput) {
            closePicker();
            return;
        }

        let idsValue = '';

        // ✅ لو الحقل single نخزن ID واحد فقط
        if (!isMultiple) {
            idsValue = String(items[0].id);
        } else {
            const ids = items.map((item) => item.id);
            idsValue = ids.join(',');
        }

        // نخزن القيمة في الـ input (مثل featured_image_id)
        targetInput.value = idsValue;

        // تعبئة الـ preview بالصور
        if (previewContainer) {
            previewContainer.innerHTML = '';
            items.forEach((item) => {
                const wrapper = document.createElement('div');
                wrapper.className =
                    'relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900';

                const img = document.createElement('img');
                img.src = item.url;
                img.alt = item.name || '';
                img.className = 'w-full h-full object-cover';

                wrapper.appendChild(img);
                previewContainer.appendChild(wrapper);
            });
        }

        closePicker();
    };

    // 🔹 رفع ملفات من داخل الـ popup (مع تحديد آخر المرفوع تلقائيًا)
    const uploadFilesFromPicker = async (files) => {
        if (!files || !files.length) return;
        if (!csrfToken) {
            console.error('CSRF token missing');
            showToast('تعذر رفع الملف: مشكلة في الحماية (CSRF).', 'error');
            return;
        }

        const formData = new FormData();
        Array.from(files).forEach((file) => formData.append('files[]', file));

        try {
            const res = await fetch(baseUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: formData,
            });

            if (!res.ok) {
                throw new Error('Upload failed');
            }

            const data = await res.json();

            let newlyUploaded = [];
            if (Array.isArray(data)) {
                newlyUploaded = data;
            } else if (Array.isArray(data.uploaded)) {
                newlyUploaded = data.uploaded;
            } else if (data && typeof data === 'object' && data.id) {
                newlyUploaded = [data];
            }

            showToast('تم رفع الصورة بنجاح.', 'success');

            if (newlyUploaded.length > 0) {
                if (!isMultiple) {
                    newlyUploaded = [newlyUploaded[newlyUploaded.length - 1]];
                }

                selectedItems.clear();

                newlyUploaded.forEach((item) => {
                    const imageUrl = item.url || `/storage/${item.file_path}`;
                    const name =
                        item.file_original_name || item.file_name || 'بدون اسم';

                    selectedItems.set(item.id, {
                        id: item.id,
                        url: imageUrl,
                        name,
                        file_type: item.file_type,
                        mime_type: item.mime_type,
                    });
                });

                updateSelectionUI();
            }

            currentPage = 1;
            lastPage = 1;
            await loadMedia(1, false);
        } catch (e) {
            console.error(e);
            showToast('فشل رفع الصورة، حاول مرة أخرى.', 'error');
        }
    };

    // 🔹 أحداث الأزرار

    // فتح الـ Picker من الأزرار
    openButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetInputId = btn.dataset.targetInput;
            const previewContainerId = btn.dataset.targetPreview || null;
            const multiple = btn.dataset.multiple === 'true';

            if (!targetInputId) {
                console.warn(
                    '[MediaPicker] data-target-input غير محدد على الزر:',
                    btn
                );
                return;
            }

            openPicker({
                targetInputId,
                previewContainerId,
                multiple,
            });
        });
    });

    // إغلاق
    if (cancelBtnEl) {
        cancelBtnEl.addEventListener('click', () => closePicker());
    }
    if (closeBtnEl) {
        closeBtnEl.addEventListener('click', () => closePicker());
    }
    if (backdropEl) {
        backdropEl.addEventListener('click', () => closePicker());
    }

    // زر إلغاء التحديد
    if (clearSelectionBtnEl) {
        clearSelectionBtnEl.addEventListener('click', (e) => {
            e.preventDefault();
            clearSelection();
        });
    }

    // تأكيد الاختيار
    if (confirmBtnEl) {
        confirmBtnEl.addEventListener('click', (e) => {
            e.preventDefault();
            applySelection();
        });
    }

    // البحث
    if (searchInputEl) {
        searchInputEl.addEventListener(
            'input',
            debounce((e) => {
                currentSearch = e.target.value.trim();
                currentPage = 1;
                loadMedia(1, false);
            }, 400)
        );
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
                btn.classList.add(
                    'bg-indigo-50',
                    'border-indigo-500',
                    'text-indigo-600'
                );

                currentFilterType = btn.dataset.type || '';
                currentPage = 1;
                loadMedia(1, false);
            });
        });
    }

    // زر "تحميل المزيد" داخل الـ popup
    if (loadMoreBtnEl) {
        loadMoreBtnEl.addEventListener('click', () => {
            if (!isLoading && currentPage < lastPage) {
                loadMedia(currentPage + 1, true);
            }
        });
    }

    // زر "رفع صورة جديدة" داخل الـ popup
    if (uploadBtnEl && fileInputEl) {
        uploadBtnEl.addEventListener('click', () => {
            fileInputEl.click();
        });

        fileInputEl.addEventListener('change', (e) => {
            uploadFilesFromPicker(e.target.files);
            e.target.value = '';
        });
    }
});
