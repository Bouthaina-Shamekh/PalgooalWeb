/**
 * تسجيل حقل اختيار الوسائط (media-picker) في GrapesJS
 * هذا الملف هو المسؤول عن شكل الزر في السايدبار وعملية النقر عليه
 */
export function registerMediaTrait(editor) {
    editor.TraitManager.addType('media-picker', {
        // إنشاء واجهة الحقل في السايدبار
        createInput({ trait }) {
            const el = document.createElement('div');
            el.className = 'pg-gjs-media-trait-container';

            // استرجاع القيمة الحالية (رابط الصورة)
            const currentValue = trait.getTargetValue() || '';

            // تصميم الحقل مع إضافة input مخفي لكي يقرأه السايدبار (sidebar.js)
            // استخدام خاصية name تجعل دالة distributeTraits تضعه في تبويب المحتوى تلقائياً
            el.innerHTML = `
                <input type="hidden" name="${trait.get('name')}" value="${currentValue}">
                <div class="flex flex-col gap-2 p-2 border border-dashed border-slate-300 rounded-xl bg-slate-50">
                    <button type="button" class="btn-open-gjs-media text-[11px] font-bold bg-white border border-slate-200 py-2 px-3 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-all">
                         اختر صورة من المكتبة
                    </button>
                    <div class="pg-media-preview ${currentValue ? '' : 'hidden'}">
                        <img src="${currentValue}" class="w-full h-24 object-cover rounded-lg shadow-sm border border-white">
                    </div>
                </div>
            `;

            const btn = el.querySelector('.btn-open-gjs-media');
            const hiddenInput = el.querySelector('input[type="hidden"]');
            const previewContainer = el.querySelector('.pg-media-preview');
            const previewImg = el.querySelector('img');

            // عند النقر على الزر داخل GrapesJS
            btn.addEventListener('click', () => {
                // نبحث عن الزر المخفي الخاص بمكون Laravel في الصفحة (Bridge) الذي يفتح مكتبة الوسائط
                const laravelPickerBtn = document.querySelector('#gjs_bridge_picker button');

                if (laravelPickerBtn) {
                    laravelPickerBtn.click(); // فتح مكتبة الوسائط

                    // الاستماع لحدث اختيار الصورة من المكون الخارجي
                    const handleSelection = (e) => {
                        // استخراج الرابط سواء كان اختيار فردي أو مصفوفة
                        const fileUrl = e.detail.url || (e.detail.files && e.detail.files[0].url);

                        if (fileUrl) {
                            // 1. تحديث القيمة في موديل GrapesJS لكي يحفظها المحرر
                            trait.setTargetValue(fileUrl);

                            // 2. تحديث الحقل المخفي لكي يتمكن السايدبار من قراءة القيمة إذا لزم الأمر
                            hiddenInput.value = fileUrl;

                            // 3. تحديث واجهة المعاينة للمستخدم
                            previewImg.src = fileUrl;
                            previewContainer.classList.remove('hidden');
                        }
                        // إزالة المستمع بعد الاختيار لضمان عدم حدوث تداخل في المرات القادمة
                        window.removeEventListener('media-selected', handleSelection);
                    };

                    window.addEventListener('media-selected', handleSelection);
                } else {
                    console.error('مكون x-dashboard.media-picker غير موجود في الصفحة (Bridge)');
                }
            });

            return el;
        },
    });
}