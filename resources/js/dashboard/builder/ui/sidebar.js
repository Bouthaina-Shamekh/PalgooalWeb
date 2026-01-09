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

    editor.on('component:selected', (cmp) => {
        if (cmp?.is?.('text')) {
            const parent = cmp.parent && cmp.parent();
            if (parent) {
                const pType = parent.get?.('type');
                // لو الأب Heading مخصص عندنا
                if (pType === 'pg-heading') {
                    editor.select(parent);
                    return;
                }
            }
        }
        if (isWrapper(cmp)) {
            setPanel('widgets');
            setSelectedLabel('لا يوجد تحديد');
            return;
        }

        // افتح لوحة العنصر
        setPanel('element');

        // افتراضي: محتوى
        setElementTab('traits');

        const name =
            cmp?.getAttributes?.()?.['data-gjs-name'] ||
            cmp?.get?.('name') ||
            cmp?.get?.('type') ||
            'Element';

        setSelectedLabel(name);
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
