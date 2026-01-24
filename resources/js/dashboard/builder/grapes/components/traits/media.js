/**
 * تسجيل حقل اختيار الوسائط في GrapesJS
 */
export function registerMediaTrait(editor) {
    editor.TraitManager.addType('media-picker', {
        // إنشاء واجهة الحقل في السايدبار
        createInput({ trait }) {
            const el = document.createElement('div');
            el.className = 'pg-gjs-media-trait-container';

            el.innerHTML = `
                <div class="flex flex-col gap-2 p-2 border border-dashed border-slate-300 rounded-xl bg-slate-50">
                    <button type="button" class="pg-gjs-open-media btn-open-gjs-media text-[11px] font-bold bg-white border border-slate-200 py-2 px-3 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-all">
                         اختر صورة من المكتبة
                    </button>
                    <div class="pg-media-preview hidden">
                        <img src="" class="w-full h-24 object-cover rounded-lg shadow-sm border border-white">
                    </div>
                </div>
            `;

            const btn = el.querySelector('.btn-open-gjs-media');
            const previewContainer = el.querySelector('.pg-media-preview');
            const previewImg = el.querySelector('img');

            // تحديث المعاينة إذا كانت هناك قيمة مسبقة
            const currentValue = trait.getTargetValue();
            if (currentValue) {
                previewImg.src = currentValue;
                previewContainer.classList.remove('hidden');
            }

            // عند النقر على الزر
            btn.addEventListener('click', () => {
                // البحث عن زر مكون Laravel الأصلي في الصفحة ونقره
                const laravelPickerBtn = document.querySelector('#gjs_bridge_picker button');

                if (laravelPickerBtn) {
                    laravelPickerBtn.click();

                    // الاستماع لحدث اختيار الصورة من نظامك
                    // ملاحظة: تأكد من أن مكون Media Picker يرسل حدث 'media-selected'
                    const handleSelection = (e) => {
                        const fileUrl = e.detail.url || (e.detail.files && e.detail.files[0].url);

                        if (fileUrl) {
                            // 1. تحديث القيمة في GrapesJS
                            trait.setTargetValue(fileUrl);

                            // 2. تحديث واجهة المعاينة في السايدبار
                            previewImg.src = fileUrl;
                            previewContainer.classList.remove('hidden');
                        }
                        window.removeEventListener('media-selected', handleSelection);
                    };

                    window.addEventListener('media-selected', handleSelection);
                } else {
                    alert('خطأ: لم يتم العثور على مكون مكتبة الوسائط في الصفحة');
                }
            });

            return el;
        },
    });
}