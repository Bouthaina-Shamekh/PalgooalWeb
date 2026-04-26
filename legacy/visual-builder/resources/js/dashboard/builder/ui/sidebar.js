// resources/js/dashboard/builder/ui/sidebar.js
import { q, qa } from '../helpers/dom';

let lastElementTab = 'traits'; // default content tab
let activeAdvancedComponent = null;
let advancedEventsBound = false;

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
        wrap.style.display = on ? 'block' : 'none';
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
function getStyleValueFromComponent(cmp, key) {
    return cmp?.getStyle?.()?.[key] ?? '';
}

function setStyleValueOnActiveComponent(key, value) {
    const cmp = activeAdvancedComponent;
    if (!cmp) return;

    const next = { ...(cmp.getStyle?.() || {}) };
    if (value === '' || value == null) delete next[key];
    else next[key] = String(value);
    cmp.setStyle(next);
}

function getAttrValueFromComponent(cmp, key) {
    return cmp?.getAttributes?.()?.[key] ?? '';
}

function setAttrValueOnActiveComponent(key, value) {
    const cmp = activeAdvancedComponent;
    if (!cmp) return;

    const attrs = { ...(cmp.getAttributes?.() || {}) };
    if (value === '' || value == null) delete attrs[key];
    else attrs[key] = value;
    cmp.setAttributes(attrs);
}

function bindAdvancedEventsOnce() {
    if (advancedEventsBound) return;

    const host = q('#pg-advanced');
    if (!host) return;

    const marginEl = q('#pg-adv-margin', host);
    const paddingEl = q('#pg-adv-padding', host);
    const zEl = q('#pg-adv-z', host);
    const clsEl = q('#pg-adv-classes', host);

    const hd = q('#pg-hide-desktop', host);
    const ht = q('#pg-hide-tablet', host);
    const hm = q('#pg-hide-mobile', host);

    marginEl?.addEventListener('input', () => setStyleValueOnActiveComponent('margin', marginEl.value.trim()));
    paddingEl?.addEventListener('input', () => setStyleValueOnActiveComponent('padding', paddingEl.value.trim()));
    zEl?.addEventListener('input', () => setStyleValueOnActiveComponent('z-index', zEl.value));

    clsEl?.addEventListener('input', () => setAttrValueOnActiveComponent('class', clsEl.value.trim()));

    const syncHide = () => {
        setAttrValueOnActiveComponent('data-pg-hide-desktop', hd?.checked ? '1' : '');
        setAttrValueOnActiveComponent('data-pg-hide-tablet', ht?.checked ? '1' : '');
        setAttrValueOnActiveComponent('data-pg-hide-mobile', hm?.checked ? '1' : '');
    };

    hd?.addEventListener('change', syncHide);
    ht?.addEventListener('change', syncHide);
    hm?.addEventListener('change', syncHide);

    advancedEventsBound = true;
}
function ensureAdvancedUI() {
    const host = q('#pg-advanced');
    if (!host) return;

    if (host.dataset.ready === '1') return;
    host.dataset.ready = '1';

    host.innerHTML = `
      <div class="pg-gjs-box">
        <div class="text-xs font-extrabold text-slate-700 mb-2">المسافات</div>

        <div class="pg-adv-spacing">
          <label class="pg-adv-space-group">
            <div class="pg-adv-space-head">
              <select data-box-unit="margin" class="pg-adv-space-unit" aria-label="Margin Unit">
                <option value="px">px</option>
                <option value="%">%</option>
                <option value="em">em</option>
                <option value="rem">rem</option>
              </select>
              <span class="pg-adv-space-label">Margin</span>
            </div>

            <input id="pg-adv-margin" type="text" placeholder="0 0 16px 0" class="hidden"/>

            <div class="pg-adv-space-row">
              <button type="button" data-box-link="margin" class="pg-adv-space-link is-linked" data-linked="true" aria-label="Link margin values" title="Link values">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11 4"></path>
                  <path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 1 0 7.07 7.07L13 20"></path>
                </svg>
              </button>

              <div class="pg-adv-space-grid">
                <input data-box="margin" data-side="left" placeholder="" class="pg-box-input pg-box-input--side"/>
                <input data-box="margin" data-side="bottom" placeholder="" class="pg-box-input pg-box-input--side"/>
                <input data-box="margin" data-side="right" placeholder="" class="pg-box-input pg-box-input--side"/>
                <input data-box="margin" data-side="top" placeholder="" class="pg-box-input pg-box-input--side"/>
              </div>
            </div>

            <div class="pg-adv-space-sides">
              <span>يسار</span>
              <span>أسفل</span>
              <span>يمين</span>
              <span>أعلى</span>
            </div>
          </label>

          <label class="pg-adv-space-group">
            <div class="pg-adv-space-head">
              <select data-box-unit="padding" class="pg-adv-space-unit" aria-label="Padding Unit">
                <option value="px">px</option>
                <option value="%">%</option>
                <option value="em">em</option>
                <option value="rem">rem</option>
              </select>
              <span class="pg-adv-space-label">Padding</span>
            </div>

            <input id="pg-adv-padding" type="text" placeholder="12px 16px" class="hidden"/>

            <div class="pg-adv-space-row">
              <button type="button" data-box-link="padding" class="pg-adv-space-link is-linked" data-linked="true" aria-label="Link padding values" title="Link values">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11 4"></path>
                  <path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 1 0 7.07 7.07L13 20"></path>
                </svg>
              </button>

              <div class="pg-adv-space-grid">
                <input data-box="padding" data-side="left" placeholder="" class="pg-box-input pg-box-input--side"/>
                <input data-box="padding" data-side="bottom" placeholder="" class="pg-box-input pg-box-input--side"/>
                <input data-box="padding" data-side="right" placeholder="" class="pg-box-input pg-box-input--side"/>
                <input data-box="padding" data-side="top" placeholder="" class="pg-box-input pg-box-input--side"/>
              </div>
            </div>

            <div class="pg-adv-space-sides">
              <span>يسار</span>
              <span>أسفل</span>
              <span>يمين</span>
              <span>أعلى</span>
            </div>
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
    bindAdvancedEventsOnce();
    activeAdvancedComponent = cmp;

    const marginEl = q('#pg-adv-margin', host);
    const paddingEl = q('#pg-adv-padding', host);
    const zEl = q('#pg-adv-z', host);
    const clsEl = q('#pg-adv-classes', host);

    const hd = q('#pg-hide-desktop', host);
    const ht = q('#pg-hide-tablet', host);
    const hm = q('#pg-hide-mobile', host);

    if (marginEl) marginEl.value = getStyleValueFromComponent(cmp, 'margin') || '';
    if (paddingEl) paddingEl.value = getStyleValueFromComponent(cmp, 'padding') || '';
    if (zEl) zEl.value = getStyleValueFromComponent(cmp, 'z-index') || '';
    if (clsEl) clsEl.value = getAttrValueFromComponent(cmp, 'class') || '';

    if (hd) hd.checked = getAttrValueFromComponent(cmp, 'data-pg-hide-desktop') === '1';
    if (ht) ht.checked = getAttrValueFromComponent(cmp, 'data-pg-hide-tablet') === '1';
    if (hm) hm.checked = getAttrValueFromComponent(cmp, 'data-pg-hide-mobile') === '1';
}

function traitNameFromRow(row) {
    const field =
        row.querySelector('input[name]') ||
        row.querySelector('select[name]') ||
        row.querySelector('textarea[name]');
    return field?.getAttribute('name') || '';
}

function pickBucketByTraitName(name) {
    // ✅ Advanced traits
    if (name.startsWith('pgAdv') || name.startsWith('pgHide')) return 'advanced';

    // ✅ default => Content
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
    const validElementTabs = new Set(['traits', 'styles', 'advanced', 'layers']);

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
        // If text node is selected inside known text-like components, select the parent component.
        const cmpType = String(cmp?.get?.('type') || '').toLowerCase();
        const isTextLikeNode = cmp?.is?.('text') || cmpType === 'textnode';
        if (isTextLikeNode) {
            const parent = cmp.parent && cmp.parent();
            const parentType = String(parent?.get?.('type') || '');
            if (parent && ['pg-heading', 'pg-text', 'pg-button'].includes(parentType)) {
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
        const desiredTab = validElementTabs.has(lastElementTab) ? lastElementTab : 'traits';
        setElementTab(desiredTab);


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
        activeAdvancedComponent = null;
        if (lastElementTab === 'layers') {
            setPanel('element');
            setSelectedLabel('الطبقات');
            return;
        }
        setPanel('widgets');
        setSelectedLabel('لا يوجد تحديد');
    });

    document.getElementById('btn-open-layout')
        .addEventListener('click', () => {
            setPanel('widgets');
            setSelectedLabel('لا يوجد تحديد');
        });

}


/* Widgets toggle */
export function initWidgetsToggle() {
    const STORAGE_KEY = 'pg_widgets_state';

    const getState = () => {
        try {
            return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || {};
        } catch {
            return {};
        }
    };

    const saveState = (state) => {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    };

    const state = getState();
    const wraps = document.querySelectorAll('.pg-widgets-wrap');

    // === استرجاع الحالة عند التحميل ===
    wraps.forEach((wrap, idx) => {
        const index = wrap.dataset.index;

        if (state[index] === undefined) {
            // الافتراضي: أول واحد مفتوح
            if (idx === 0) wrap.classList.remove('is-collapsed');
            else wrap.classList.add('is-collapsed');
        } else {
            wrap.classList.toggle('is-collapsed', !state[index]);
        }
    });

    // === زر التبديل ===
    document.querySelectorAll('.pg-widgets-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const index = btn.dataset.target;
            const wrap = document.querySelector(
                `.pg-widgets-wrap[data-index="${index}"]`
            );

            if (!wrap) return;

            wrap.classList.toggle('is-collapsed');

            const isOpen = !wrap.classList.contains('is-collapsed');
            state[index] = isOpen;
            saveState(state);
        });
    });
}


/* Widgets search */
export function initWidgetsSearch() {
    const input = q('#pg-widgets-search');
    if (!input) return;

    const STORAGE_KEY = 'pg_widgets_state';

    const getState = () => {
        try {
            return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || {};
        } catch {
            return {};
        }
    };

    input.addEventListener('input', () => {
        const term = (input.value || '').trim().toLowerCase();
        const items = qa('[data-widget-item]');
        const wraps = qa('.pg-widgets-wrap');

        // === فلترة العناصر ===
        items.forEach((el) => {
            const name = (el.dataset.widgetName || el.textContent || '').toLowerCase();
            el.classList.toggle('is-hidden', !!term && !name.includes(term));
        });

        // === فتح كل الأقسام أثناء البحث ===
        if (term) {
            wraps.forEach(wrap => {
                wrap.classList.remove('is-collapsed');
            });
        }
        // === إعادة الحالة المحفوظة بعد مسح البحث ===
        else {
            const state = getState();

            wraps.forEach((wrap, idx) => {
                const index = wrap.dataset.index;

                if (state[index] === undefined) {
                    if (idx === 0) wrap.classList.remove('is-collapsed');
                    else wrap.classList.add('is-collapsed');
                } else {
                    wrap.classList.toggle('is-collapsed', !state[index]);
                }
            });
        }
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

export function initBoxSpacingInputs() {
    const IDS = {
        margin: 'pg-adv-margin',
        padding: 'pg-adv-padding',
    };

    const SIDES = ['top', 'right', 'bottom', 'left'];
    const SUPPORTED_UNITS = ['px', '%', 'em', 'rem', 'vw', 'vh'];

    function isPureNumber(v) {
        return /^-?(?:\d+|\d*\.\d+)$/.test(v);
    }

    function parseValueWithUnit(v) {
        const val = String(v ?? '').trim();
        const match = val.match(/^(-?(?:\d+|\d*\.\d+))(px|%|em|rem|vw|vh)?$/i);
        if (!match) return null;

        const next = Number(match[1]);
        if (!Number.isFinite(next)) return null;

        return {
            number: String(match[1]),
            unit: String(match[2] || '').toLowerCase(),
        };
    }

    function normalizeSideValue(box, raw, unit) {
        const val = String(raw ?? '').trim();
        if (!val) return '0';

        if (box === 'margin' && val.toLowerCase() === 'auto') return 'auto';

        const parsed = parseValueWithUnit(val);
        if (parsed) {
            return parsed.unit ? `${parsed.number}${parsed.unit}` : `${parsed.number}${unit}`;
        }

        if (isPureNumber(val)) return `${val}${unit}`;
        return val;
    }

    function getHidden(box) {
        return document.getElementById(IDS[box]);
    }

    function getUnitSelect(box) {
        return document.querySelector(`[data-box-unit="${box}"]`);
    }

    function getLinkButton(box) {
        return document.querySelector(`[data-box-link="${box}"]`);
    }

    function getSideInput(box, side) {
        return document.querySelector(`[data-box="${box}"][data-side="${side}"]`);
    }

    function isLinked(box) {
        return getLinkButton(box)?.dataset.linked !== 'false';
    }

    function setLinked(box, linked) {
        const linkEl = getLinkButton(box);
        if (!linkEl) return;
        linkEl.dataset.linked = linked ? 'true' : 'false';
        linkEl.classList.toggle('is-linked', linked);
    }

    function expandCssShorthand(parts) {
        const p = parts.filter(Boolean);
        if (!p.length) return ['0', '0', '0', '0'];
        if (p.length === 1) return [p[0], p[0], p[0], p[0]];
        if (p.length === 2) return [p[0], p[1], p[0], p[1]];
        if (p.length === 3) return [p[0], p[1], p[2], p[1]];
        return [p[0], p[1], p[2], p[3]];
    }

    function fillInputsFromHidden(box) {
        const hidden = getHidden(box);
        if (!hidden) return;

        const unitEl = getUnitSelect(box);
        const raw = String(hidden.value || '').trim();

        if (!raw) {
            SIDES.forEach((side) => {
                const input = getSideInput(box, side);
                if (input) input.value = '';
            });
            if (unitEl) unitEl.value = 'px';
            setLinked(box, true);
            return;
        }

        const parts = raw.split(/\s+/);
        const [t, r, b, l] = expandCssShorthand(parts);
        const map = { top: t, right: r, bottom: b, left: l };

        const allValues = [t, r, b, l];
        const parsedValues = allValues.map((value) => parseValueWithUnit(value));
        const firstUnit = parsedValues.find((item) => item?.unit)?.unit || 'px';
        const hasMixedUnits = parsedValues.some((item) => item?.unit && item.unit !== firstUnit);
        const resolvedUnit = !hasMixedUnits && SUPPORTED_UNITS.includes(firstUnit)
            ? firstUnit
            : (unitEl?.value || 'px');

        if (unitEl && SUPPORTED_UNITS.includes(resolvedUnit)) {
            unitEl.value = resolvedUnit;
        }

        Object.entries(map).forEach(([side, val]) => {
            const input = getSideInput(box, side);
            if (!input) return;

            const parsed = parseValueWithUnit(val);
            if (parsed && (!parsed.unit || parsed.unit === resolvedUnit)) {
                input.value = parsed.number;
                return;
            }

            input.value = String(val || '');
        });

        const linked = allValues.every((value) => String(value).trim() === String(allValues[0]).trim());
        setLinked(box, linked);
    }

    function updateHiddenFromInputs(box) {
        const hidden = getHidden(box);
        if (!hidden) return;

        const unit = getUnitSelect(box)?.value || 'px';
        const top = normalizeSideValue(box, getSideInput(box, 'top')?.value, unit);
        const right = normalizeSideValue(box, getSideInput(box, 'right')?.value, unit);
        const bottom = normalizeSideValue(box, getSideInput(box, 'bottom')?.value, unit);
        const left = normalizeSideValue(box, getSideInput(box, 'left')?.value, unit);

        hidden.value = `${top} ${right} ${bottom} ${left}`;
        hidden.dispatchEvent(new Event('input', { bubbles: true }));
        hidden.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function hasAllInputs(box) {
        return (
            !!getHidden(box) &&
            !!getUnitSelect(box) &&
            !!getLinkButton(box) &&
            !!getSideInput(box, 'top') &&
            !!getSideInput(box, 'right') &&
            !!getSideInput(box, 'bottom') &&
            !!getSideInput(box, 'left')
        );
    }

    function syncWhenReady() {
        if (hasAllInputs('margin')) fillInputsFromHidden('margin');
        if (hasAllInputs('padding')) fillInputsFromHidden('padding');
    }

    document.addEventListener(
        'input',
        (e) => {
            const el = e.target;
            if (!(el instanceof HTMLElement)) return;

            const sideField = el.closest('[data-box][data-side]');
            if (sideField) {
                const box = sideField.getAttribute('data-box');
                if (box !== 'margin' && box !== 'padding') return;

                const side = sideField.getAttribute('data-side');
                if (isLinked(box) && side) {
                    const sourceValue = getSideInput(box, side)?.value ?? '';
                    SIDES.forEach((nextSide) => {
                        if (nextSide === side) return;
                        const input = getSideInput(box, nextSide);
                        if (input) input.value = sourceValue;
                    });
                }

                updateHiddenFromInputs(box);
                return;
            }

            const unitField = el.closest('[data-box-unit]');
            if (!unitField) return;

            const box = unitField.getAttribute('data-box-unit');
            if (box !== 'margin' && box !== 'padding') return;
            updateHiddenFromInputs(box);
        },
        true
    );

    document.addEventListener(
        'change',
        (e) => {
            const el = e.target;
            if (!(el instanceof HTMLElement)) return;

            const sideField = el.closest('[data-box][data-side]');
            if (sideField) {
                const box = sideField.getAttribute('data-box');
                if (box !== 'margin' && box !== 'padding') return;
                updateHiddenFromInputs(box);
                return;
            }

            const unitField = el.closest('[data-box-unit]');
            if (!unitField) return;

            const box = unitField.getAttribute('data-box-unit');
            if (box !== 'margin' && box !== 'padding') return;
            updateHiddenFromInputs(box);
        },
        true
    );

    document.addEventListener(
        'click',
        (e) => {
            const target = e.target;
            if (!(target instanceof HTMLElement)) return;

            const linkBtn = target.closest('[data-box-link]');
            if (!linkBtn) return;

            const box = linkBtn.getAttribute('data-box-link');
            if (box !== 'margin' && box !== 'padding') return;

            const next = !isLinked(box);
            setLinked(box, next);

            if (next) {
                const sourceValue = getSideInput(box, 'top')?.value ?? '';
                SIDES.forEach((side) => {
                    const input = getSideInput(box, side);
                    if (input) input.value = sourceValue;
                });
            }

            updateHiddenFromInputs(box);
        },
        true
    );

    const obs = new MutationObserver(() => {
        syncWhenReady();
    });
    obs.observe(document.body, { childList: true, subtree: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            syncWhenReady();
            setTimeout(syncWhenReady, 0);
            setTimeout(syncWhenReady, 50);
        });
    } else {
        syncWhenReady();
        setTimeout(syncWhenReady, 0);
        setTimeout(syncWhenReady, 50);
    }

    ['margin', 'padding'].forEach((box) => {
        const hidden = getHidden(box);
        if (!hidden) return;
        hidden.addEventListener('input', () => fillInputsFromHidden(box));
        hidden.addEventListener('change', () => fillInputsFromHidden(box));
    });
}

