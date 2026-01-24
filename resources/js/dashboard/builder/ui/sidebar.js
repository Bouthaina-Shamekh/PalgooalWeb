// resources/js/dashboard/builder/ui/sidebar.js
import { q, qa } from '../helpers/dom';

let lastElementTab = 'traits'; // محتوى افتراضي

function setPanel(panelName) {
    const panels = qa('.pg-sidebar-panel');
    panels.forEach((p) => {
        const on = p.dataset.panel === panelName;
        p.dataset.active = on ? 'true' : 'false';
    });

    // Search فقط في Widgets (بدون قفزات)
    const wrap = q('.pg-widgets-search-wrap');
    if (wrap) {
        const on = panelName === 'widgets';
        wrap.style.visibility = on ? 'visible' : 'hidden';
        wrap.style.pointerEvents = on ? 'auto' : 'none';
        wrap.style.opacity = on ? '1' : '0';
    }
}

function setElementTab(tabName) {
    lastElementTab = tabName;

    const btns = qa('.pg-props-tab-btn');
    const panels = qa('.pg-props-tab-content');

    btns.forEach((b) => (b.dataset.active = b.dataset.propTab === tabName ? 'true' : 'false'));
    panels.forEach((p) => (p.dataset.active = p.dataset.propContent === tabName ? 'true' : 'false'));
}

function setSelectedLabel(text) {
    const el = q('#pg-props-selected');
    if (el) el.textContent = text || 'لا يوجد تحديد';
}
function ensureAdvancedUI() {
    const host = q('#pg-advanced');
    if (!host) return;

    if (host.dataset.ready === '1') return;
    host.dataset.ready = '1';

    host.innerHTML = `
      <div class="pg-gjs-box">
        <div class="text-xs font-extrabold text-slate-700 mb-2">المسافات</div>

        <div class="grid grid-cols-2 gap-2">
          <label class="text-[11px] font-bold text-slate-600">
            Margin
            <input id="pg-adv-margin" type="text" placeholder="مثال: 0 0 16px 0"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs"/>
          </label>

          <label class="text-[11px] font-bold text-slate-600">
            Padding
            <input id="pg-adv-padding" type="text" placeholder="مثال: 12px 16px"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs"/>
          </label>
        </div>
      </div>

      <div class="pg-gjs-box">
        <div class="text-xs font-extrabold text-slate-700 mb-2">الترتيب</div>

        <label class="text-[11px] font-bold text-slate-600">
          Z-index
          <input id="pg-adv-z" type="number" placeholder="0"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs"/>
        </label>
      </div>

      <div class="pg-gjs-box">
        <div class="text-xs font-extrabold text-slate-700 mb-2">Responsive</div>

        <div class="grid grid-cols-3 gap-2">
          <label class="flex items-center gap-2 text-[11px] font-bold text-slate-700">
            <input id="pg-hide-desktop" type="checkbox" class="rounded border-slate-300"/>
            Desktop
          </label>
          <label class="flex items-center gap-2 text-[11px] font-bold text-slate-700">
            <input id="pg-hide-tablet" type="checkbox" class="rounded border-slate-300"/>
            Tablet
          </label>
          <label class="flex items-center gap-2 text-[11px] font-bold text-slate-700">
            <input id="pg-hide-mobile" type="checkbox" class="rounded border-slate-300"/>
            Mobile
          </label>
        </div>
      </div>

      <div class="pg-gjs-box">
        <div class="text-xs font-extrabold text-slate-700 mb-2">Custom</div>

        <label class="text-[11px] font-bold text-slate-600">
          Custom classes
          <input id="pg-adv-classes" type="text" placeholder="مثال: text-red-500 font-bold"
            class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs"/>
        </label>
      </div>
    `;
}

function bindAdvancedToComponent(editor, cmp) {
    const host = q('#pg-advanced');
    if (!host || !editor || !cmp) return;

    ensureAdvancedUI();

    const getStyle = (k) => (cmp.getStyle?.()[k] ?? '');
    const setStyle = (k, v) => {
        const next = { ...(cmp.getStyle?.() || {}) };
        if (v === '' || v == null) delete next[k];
        else next[k] = String(v);
        cmp.setStyle(next);
    };

    const getAttr = (k) => (cmp.getAttributes?.()[k] ?? '');
    const setAttr = (k, v) => {
        const attrs = { ...(cmp.getAttributes?.() || {}) };
        if (v === '' || v == null) delete attrs[k];
        else attrs[k] = v;
        cmp.setAttributes(attrs);
    };

    // Fill current values
    const marginEl = q('#pg-adv-margin', host);
    const paddingEl = q('#pg-adv-padding', host);
    const zEl = q('#pg-adv-z', host);
    const clsEl = q('#pg-adv-classes', host);

    const hd = q('#pg-hide-desktop', host);
    const ht = q('#pg-hide-tablet', host);
    const hm = q('#pg-hide-mobile', host);

    if (marginEl) marginEl.value = getStyle('margin') || '';
    if (paddingEl) paddingEl.value = getStyle('padding') || '';
    if (zEl) zEl.value = getStyle('z-index') || '';
    if (clsEl) clsEl.value = getAttr('class') || '';

    // hide flags stored as data attrs
    if (hd) hd.checked = getAttr('data-pg-hide-desktop') === '1';
    if (ht) ht.checked = getAttr('data-pg-hide-tablet') === '1';
    if (hm) hm.checked = getAttr('data-pg-hide-mobile') === '1';

    // Apply handlers
    marginEl?.addEventListener('input', () => setStyle('margin', marginEl.value.trim()));
    paddingEl?.addEventListener('input', () => setStyle('padding', paddingEl.value.trim()));
    zEl?.addEventListener('input', () => setStyle('z-index', zEl.value));

    clsEl?.addEventListener('input', () => setAttr('class', clsEl.value.trim()));

    const syncHide = () => {
        setAttr('data-pg-hide-desktop', hd?.checked ? '1' : '');
        setAttr('data-pg-hide-tablet', ht?.checked ? '1' : '');
        setAttr('data-pg-hide-mobile', hm?.checked ? '1' : '');
    };

    hd?.addEventListener('change', syncHide);
    ht?.addEventListener('change', syncHide);
    hm?.addEventListener('change', syncHide);
}


// ابحث عن هذه الدالة في sidebar.js واستبدلها بهذا الكود
function traitNameFromRow(row) {
    // محاولة الحصول على الاسم من موديل GrapesJS مباشرة (للحقول المخصصة)
    try {
        if (row.model && typeof row.model.get === 'function') {
            return row.model.get('name');
        }
    } catch (e) { }

    const field =
        row.querySelector('input[name]') ||
        row.querySelector('select[name]') ||
        row.querySelector('textarea[name]') ||
        row.querySelector('[data-name]'); // دعم إضافي

    return field?.getAttribute('name') || field?.dataset.name || '';
}

function pickBucketByTraitName(name) {
    // أي خاصية تبدأ بـ pgAdv تذهب للمتقدم
    if (name.startsWith('pgAdv') || name.startsWith('pgHide')) return 'advanced';

    // أي شيء آخر (بما في ذلك الصور) يذهب لتبويب المحتوى (traits)
    return 'traits';
}

function distributeTraits() {
    const src = q('#gjs-traits');
    const contentHost = q('#pg-el-content-fields');
    const advancedHost = q('#pg-el-advanced-fields');

    if (!src || !contentHost || !advancedHost) return;

    // ✅ اجمع rows من src (قبل نقلها)
    const rows = qa('#gjs-traits .gjs-trt-trait');
    contentHost.innerHTML = '';
    advancedHost.innerHTML = '';

    if (!rows.length) return;

    rows.forEach((row) => {
        const name = traitNameFromRow(row);
        const bucket = pickBucketByTraitName(name);
        if (bucket === 'advanced') advancedHost.appendChild(row);
        else contentHost.appendChild(row);
    });
}



export function initSidebarTabs() {
    // Element inner tabs only
    qa('.pg-props-tab-btn').forEach((btn) => {
        btn.addEventListener('click', () => setElementTab(btn.dataset.propTab));
    });

    // Default
    setPanel('widgets');
    setElementTab('traits');
    setSelectedLabel('لا يوجد تحديد');
}

/**
 * ✅ Elementor behavior:
 * - Select component => show Element Settings (traits by default)
 * - Deselect => back to Widgets
 */
export function bindEditorSidebarTabs(editor) {
    if (!editor) return;

    const isWrapper = (cmp) => {
        const wrapper = editor.getWrapper?.();
        return !cmp || cmp === wrapper || cmp.get?.('type') === 'wrapper';
    };

    const refresh = () => requestAnimationFrame(() => distributeTraits());

    // ✅ سجّل مرة واحدة فقط
    editor.on('component:toggled', refresh);
    editor.on('component:update', refresh);
    editor.on('trait:update', refresh);

    editor.on('component:selected', (cmp) => {
        // لو ضغط على Text داخل heading -> اختار الأب
        if (cmp?.is?.('text')) {
            const parent = cmp.parent && cmp.parent();
            if (parent && parent.get?.('type') === 'pg-heading') {
                editor.select(parent);
                return;
            }
        }

        if (isWrapper(cmp)) {
            setPanel('widgets');
            setSelectedLabel('لا يوجد تحديد');
            return;
        }

        setPanel('element');
        setElementTab('traits');

        const name =
            cmp?.getAttributes?.()?.['data-gjs-name'] ||
            cmp?.get?.('name') ||
            cmp?.get?.('type') ||
            'Element';

        setSelectedLabel(name);
        bindAdvancedToComponent(editor, cmp);


        // ✅ بعد ما Grapes يرسم traits
        setTimeout(refresh, 0);
    });

    editor.on('component:deselected', () => {
        setPanel('widgets');
        setSelectedLabel('لا يوجد تحديد');
    });
}


/* Widgets toggle */
export function initWidgetsToggle() {
    const btn = q('#pg-widgets-toggle');
    const wrap = q('#pg-widgets-wrap');
    if (!btn || !wrap) return;

    btn.addEventListener('click', () => {
        wrap.classList.toggle('is-collapsed');
    });
}

/* Widgets search */
export function initWidgetsSearch() {
    const input = q('#pg-widgets-search');
    if (!input) return;

    input.addEventListener('input', () => {
        const term = (input.value || '').trim().toLowerCase();
        const items = qa('[data-widget-item]');
        items.forEach((el) => {
            const name = (el.dataset.widgetName || el.textContent || '').toLowerCase();
            el.classList.toggle('is-hidden', !!term && !name.includes(term));
        });
    });
}

export function simplifyBlocksPalette() {
    const host = q('#gjs-blocks');
    if (!host) return;

    const blocks = qa('#gjs-blocks .gjs-block');
    if (!blocks.length) return;

    let grid = q('#gjs-blocks .pg-blocks-grid');
    if (!grid) {
        grid = document.createElement('div');
        grid.className = 'pg-blocks-grid';
        host.appendChild(grid);
    }

    blocks.forEach((block) => {
        const label =
            block.querySelector('.gjs-block-label')?.textContent?.trim() ||
            block.textContent?.trim() ||
            'Block';

        if (!block.classList.contains('pg-widget-tile')) {
            block.classList.add('pg-widget-tile');
            block.setAttribute('tabindex', '0');
            block.dataset.widgetItem = '1';
            block.dataset.widgetName = label;
            block.setAttribute('aria-label', label);

            block.innerHTML = `
        <div class="gjs-block-label">
          <div class="pg-block-card">
            <div class="pg-block-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M6 7h12M6 12h12M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </div>
            <div class="pg-block-title">${label}</div>
          </div>
        </div>
      `;
        }

        if (block.parentElement !== grid) grid.appendChild(block);
    });
}
