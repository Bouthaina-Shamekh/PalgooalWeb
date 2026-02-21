export function initCanvas(editor, { appDir, emptyHint, cssUrl }) {
    const GOOGLE_FONTS_URL =
        'https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@300;400;500;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&family=Montserrat:wght@300;400;500;600;700;800;900&family=Nunito:wght@300;400;500;600;700;800;900&family=Raleway:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700;900&family=Open+Sans:wght@300;400;500;600;700;800&family=Lato:wght@300;400;700;900&family=Source+Sans+3:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700;800&family=Merriweather:wght@300;400;700;900&family=Oswald:wght@300;400;500;600;700&display=swap';
    const bindOnce = (() => {
        let bound = false;
        return (fn) => {
            if (bound) return;
            bound = true;
            fn();
        };
    })();

    function setupFrameBasics() {
        const doc = editor.Canvas.getDocument();
        if (!doc) return null;

        const htmlEl = doc.documentElement;
        const bodyEl = editor.Canvas.getBody();
        const headEl = doc.head || doc.querySelector('head');

        htmlEl.setAttribute('dir', appDir || 'ltr');

        Object.assign(bodyEl.style, {
            background: 'transparent',
            fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif',
            color: '#0f172a',
            margin: '0',
            padding: '0',
        });

        // ✅ مهم: خلي wrapper يقبل drop دائما
        const wrapper = editor.getWrapper();
        wrapper?.set?.({ droppable: true });

        const wrapperEl = wrapper?.getEl?.();
        if (wrapperEl) {
            Object.assign(wrapperEl.style, {
                width: '100%',
                maxWidth: '100%',
                margin: '0',
                boxSizing: 'border-box',
                minHeight: '100vh',
            });
        }

        // Inject app.css once
        if (headEl && cssUrl && !doc.getElementById('pg-app-css')) {
            const link = doc.createElement('link');
            link.id = 'pg-app-css';
            link.rel = 'stylesheet';
            link.href = cssUrl;
            headEl.appendChild(link);
        }

        // Load Google fonts used by Style Manager font-family presets.
        if (headEl && !doc.getElementById('pg-google-fonts')) {
            const link = doc.createElement('link');
            link.id = 'pg-google-fonts';
            link.rel = 'stylesheet';
            link.href = GOOGLE_FONTS_URL;
            headEl.appendChild(link);
        }

        // Selection outline + empty hint
        if (headEl && !doc.getElementById('pg-canvas-style')) {
            const style = doc.createElement('style');
            style.id = 'pg-canvas-style';
            const safe = (emptyHint || '').replace(/"/g, '\\"');

            style.innerHTML = `
        [data-pg-selected]{outline:2px dashed #2563eb;outline-offset:4px;position:relative;}
        html[dir="rtl"] [data-pg-selected]::before, html[dir="ltr"] [data-pg-selected]::before{
          content:attr(data-pg-selected);position:absolute;top:-14px;background:#2563eb;color:#fff;
          font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;pointer-events:none;
        }
        html[dir="rtl"] [data-pg-selected]::before{right:0;left:auto;}
        html[dir="ltr"] [data-pg-selected]::before{left:0;right:auto;}
        html,body{height:100%;overflow-x:hidden;}
        html[dir="rtl"] body{text-align:right;}
        html[dir="ltr"] body{text-align:left;}
        img.pg-image{
          display:block;
          max-width:100%;
          height:auto;
        }
        a.pg-image-link{
          display:block;
          width:fit-content;
          max-width:100%;
        }
        a.pg-image-link > img.pg-image{
          display:block;
          max-width:100%;
          height:auto;
        }
        .gjs-wrapper:empty::before{content:"${safe}";display:block;text-align:center;color:#64748b;font-weight:600;padding-top:60px;}
        .pg-container .pg-container-inner:empty{
          min-height:140px;
          border:1px dashed #cbd5e1;
          border-radius:12px;
        }
      `;

            headEl.appendChild(style);
        }

        return { doc };
    }

    function setupSwiperInFrame() {
        const frameEl = editor.Canvas.getFrameEl();
        const frameDoc = frameEl?.contentDocument;
        const frameWin = frameEl?.contentWindow;
        if (!frameDoc || !frameWin) return null;

        // CSS (CDN)
        if (!frameDoc.querySelector('#pg-swiper-css')) {
            const link = frameDoc.createElement('link');
            link.id = 'pg-swiper-css';
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css';
            frameDoc.head.appendChild(link);
        }

        function ensureSwiper(cb) {
            if (frameWin.Swiper) return cb();
            if (frameDoc.querySelector('#pg-swiper-js')) return;

            const s = frameDoc.createElement('script');
            s.id = 'pg-swiper-js';
            s.src = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js';
            s.onload = cb;
            frameDoc.body.appendChild(s);
        }

        function initAllSwipers() {
            if (!frameWin.Swiper) return;

            frameDoc.querySelectorAll('.mySwiper').forEach((el) => {
                try {
                    if (el.__pgSwiper) el.__pgSwiper.destroy(true, true);
                } catch { }

                el.__pgSwiper = new frameWin.Swiper(el, {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    pagination: {
                        el: el.querySelector('.swiper-pagination'),
                        clickable: true,
                    },
                    breakpoints: {
                        640: { slidesPerView: 2 },
                        1024: { slidesPerView: 3 },
                    },
                });
            });
        }

        // أول مرة
        ensureSwiper(initAllSwipers);

        // Bind once (بدون تكرار listeners)
        bindOnce(() => {
            const refresh = () => ensureSwiper(initAllSwipers);

            editor.on('component:add', refresh);
            editor.on('component:remove', refresh);
            editor.on('component:update', refresh);
            editor.on('component:styleUpdate', refresh);
        });

        return true;
    }

    editor.on('load', () => {
        setupFrameBasics();
        setupSwiperInFrame();
    });
}
