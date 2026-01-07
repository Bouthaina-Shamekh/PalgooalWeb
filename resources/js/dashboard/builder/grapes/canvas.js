export function initCanvas(editor, { appDir, emptyHint, cssUrl }) {
    editor.on('load', () => {
        const doc = editor.Canvas.getDocument();
        if (!doc) return;

        const htmlEl = doc.documentElement;
        const bodyEl = editor.Canvas.getBody();
        const headEl = doc.head || doc.querySelector('head');

        htmlEl.setAttribute('dir', appDir);

        Object.assign(bodyEl.style, {
            background: 'transparent',
            fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif',
            color: '#0f172a',
            margin: '0',
            padding: '0',
        });

        const wrapper = editor.getWrapper();
        const wrapperEl = wrapper?.getEl?.();
        wrapper?.set?.({ droppable: true });

        if (wrapperEl) {
            Object.assign(wrapperEl.style, {
                width: '100%',
                maxWidth: '100%',
                margin: '0',
                boxSizing: 'border-box',
            });
        }

        // Inject Tailwind/app.css once
        if (headEl && cssUrl && !doc.getElementById('pg-app-css')) {
            const link = doc.createElement('link');
            link.id = 'pg-app-css';
            link.rel = 'stylesheet';
            link.href = cssUrl;
            headEl.appendChild(link);
        }

        // Selection outline + empty hint
        const style = doc.createElement('style');
        const safe = (emptyHint || '').replace(/"/g, '\\"');

        style.innerHTML = `
      [data-pg-selected]{outline:2px dashed #2563eb;outline-offset:4px;position:relative;}
      html[dir="rtl"] [data-pg-selected]::before, html[dir="ltr"] [data-pg-selected]::before{
        content:attr(data-pg-selected);position:absolute;top:-14px;background:#2563eb;color:#fff;
        font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;pointer-events:none;
      }
      html[dir="rtl"] [data-pg-selected]::before{right:0;left:auto;}
      html[dir="ltr"] [data-pg-selected]::before{left:0;right:auto;}
      html,body{height:100%;}
      html[dir="rtl"] body{margin:0!important;padding:0!important;text-align:right;}
      html[dir="ltr"] body{margin:0!important;padding:0!important;text-align:left;}
      .gjs-wrapper{width:100%!important;max-width:100%!important;margin:0!important;min-height:100vh;padding:0;box-sizing:border-box;}
      .gjs-wrapper:empty::before{content:"${safe}";display:block;text-align:center;color:#64748b;font-weight:600;padding-top:60px;}
    `;

        headEl?.appendChild(style);
    });
}
