export function registerWysiwygTrait(editor) {
    const tm = editor.TraitManager;

    tm.addType('pg-wysiwyg', {
        createInput({ trait }) {
            const el = document.createElement('textarea');
            el.className = 'pg-wysiwyg-textarea';
            el.style.width = '100%';
            el.style.minHeight = '260px';
            el.value = trait.get('value') || '';
            return el;
        },

        onRender({ elInput, component, trait }) {
            // حماية من إعادة التهيئة عدة مرات لنفس textarea
            const id = `pg_wysiwyg_${Math.random().toString(16).slice(2)}`;
            elInput.id = id;

            const getHtmlFromComponent = () => {
                // محتوى العنصر الحالي كـ HTML
                return component?.toHTML?.() || '';
            };

            // خزّن html الحالي داخل trait إذا كان فارغ
            if (!trait.get('value')) {
                trait.set('value', component.get('pgHtml') || '');
            }

            // انتظر TinyMCE يكون جاهز
            const init = () => {
                if (!window.tinymce) return;

                // لو موجود instance قديم على نفس id
                const old = window.tinymce.get(id);
                if (old) old.remove();

                window.tinymce.init({
                    selector: `#${id}`,
                    height: 280,
                    menubar: false,
                    branding: false,
                    plugins: 'link lists code directionality',
                    toolbar:
                        'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | ltr rtl | code',
                    directionality: document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr',
                    setup: (ed) => {
                        // املأ المحرر بقيمة trait (pgHtml)
                        ed.on('init', () => {
                            const v = trait.get('value') || component.get('pgHtml') || '';
                            ed.setContent(v || '');
                        });

                        // أي تغيير => حدّث trait + component
                        const sync = () => {
                            const html = ed.getContent() || '';
                            trait.set('value', html);

                            // خزّن داخل component prop
                            component.set('pgHtml', html);

                            // طبّق داخل العنصر: استبدل المحتوى الداخلي
                            // نستخدم components(html) حتى GrapesJS يحوّل الـ HTML لمكونات
                            component.components(html);
                            component.view?.render?.();
                        };

                        ed.on('Change KeyUp SetContent Undo Redo', () => {
                            // خفف الضغط على الأداء
                            clearTimeout(ed.__pgTimer);
                            ed.__pgTimer = setTimeout(sync, 150);
                        });
                    },
                });
            };

            init();
        },

        onUpdate({ elInput, trait, component }) {
            const textarea = elInput.querySelector('textarea');
            if (!textarea) return;

            // unique id
            if (!textarea.id) textarea.id = `pgWys_${Math.random().toString(36).slice(2)}`;

            const initEditor = () => {
                if (!window.tinymce) return false;

                // إذا موجود محرر لنفس الـ id لا تعيد تهيئته
                if (tinymce.get(textarea.id)) return true;

                tinymce.init({
                    selector: `#${textarea.id}`,
                    menubar: false,
                    branding: false,
                    height: 260,
                    directionality: document.documentElement.dir || 'rtl',
                    plugins: 'link lists code',
                    toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link | code',
                    setup: (ed) => {
                        ed.on('init', () => ed.setContent(trait.get('value') || ''));

                        const sync = () => trait.set('value', ed.getContent());
                        ed.on('Change KeyUp SetContent Undo Redo', sync);

                        trait.on('change:value', () => {
                            const next = trait.get('value') || '';
                            if (ed.getContent() !== next) ed.setContent(next);
                        });

                        component.on('remove', () => {
                            try { ed.remove(); } catch { }
                        });
                    },
                });

                return true;
            };

            // جرّب فورًا
            if (initEditor()) return;

            // لو tinymce لسه مش جاهز: انتظر قليلًا (retry بسيط)
            let tries = 0;
            const t = setInterval(() => {
                tries++;
                if (initEditor() || tries > 20) clearInterval(t); // ~2s
            }, 100);
        }
    });
}
