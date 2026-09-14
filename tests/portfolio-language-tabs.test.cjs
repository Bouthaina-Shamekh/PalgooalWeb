// Run with: node --test tests/portfolio-language-tabs.test.cjs
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const view = fs.readFileSync(path.join(__dirname, '../resources/views/dashboard/portfolios/_form.blade.php'), 'utf8');
const script = view.slice(view.indexOf('// Language tab switching (portfolio)'), view.lastIndexOf('</script>'))
    .replace('@json($languages->pluck(\'code\'))', '["ar","en","fr"]');

function setup(saved = 'ar', errors = [], direction = 'ltr') {
    let focused;
    const timers = new Map();
    const nodes = {};
    const tabs = [];
    const panels = [];
    const fields = [];
    const listeners = {};
    const form = { elements: fields, addEventListener(type, callback, capture) {
        if (type === 'invalid') assert.equal(capture, true);
        listeners[type] = callback;
    }};
    function node(id, classes = []) {
        const set = new Set(classes);
        return nodes[id] = { id, attributes: {}, classList: {
            add(...values) { values.forEach(v => set.add(v)); },
            remove(...values) { values.forEach(v => set.delete(v)); },
            contains(value) { return set.has(value); },
        }, setAttribute(key, value) { this.attributes[key] = value; }, focus() { focused = this; } };
    }
    const general = node('delivery_date');
    Object.assign(general, { willValidate: true, validity: { valid: true }, closest: () => null });
    fields.push(general);
    for (const code of ['ar', 'en', 'fr']) {
        tabs.push(node('lang-tab-' + code));
        const panel = node('lang-panel-' + code, ['hidden']);
        panel.activeLanguage = code !== 'fr';
        panels.push(panel);
        for (const fieldName of ['title', 'type', 'materials', 'link']) {
            const field = node(fieldName + '_' + code);
            Object.assign(field, {
                value: '', required: false, translationField: true,
                translationRequired: ['title', 'type', 'materials'].includes(fieldName),
                willValidate: true, validity: { valid: true }, closest: () => panel,
            });
            fields.push(field);
        }
        panel.querySelector = () => nodes['title_' + code];
        panel.querySelectorAll = selector => fields.filter(field => field.closest?.() === panel && (
            selector === '[data-translation-field]' ? field.translationField : field.translationRequired
        ));
    }
    nodes.portfolioLanguageTabs = { closest: () => form };
    const context = { getComputedStyle: () => ({ direction }), window: {}, document: {
        getElementById: id => nodes[id],
        querySelectorAll: selector => selector === '.lang-panel' ? panels
            : selector === '.lang-panel[data-active-language="true"]' ? panels.filter(panel => panel.activeLanguage)
            : tabs,
        querySelector: () => tabs.find(tab => errors.includes(tab.id.replace('lang-tab-', ''))),
        addEventListener: (type, callback) => callback(),
    }, localStorage: { getItem: () => saved, setItem() {} },
    setTimeout(callback) { const key = Symbol(); timers.set(key, callback); return key; },
    clearTimeout(key) { timers.delete(key); } };
    vm.runInNewContext(script, context);
    return { nodes, tabs, fields, window: context.window, focus: () => focused,
        flush() { for (const callback of timers.values()) callback(); timers.clear(); },
        validate() {
            const reports = [];
            for (const target of fields.filter(field => field.willValidate && !field.validity.valid)) {
                const event = { target, prevented: false, preventDefault() { this.prevented = true; } };
                listeners.invalid(event);
                if (!event.prevented) reports.push(target);
            }
            // Simulate the browser's reporting phase, after all invalid events.
            for (const field of reports) {
                assert.equal(field.closest()?.classList.contains('hidden') ?? false, false);
            }
            reports[0]?.focus();
            return reports;
        },
        input(field, value) {
            field.value = value;
            listeners.input?.({ target: field });
        },
    };
}

for (const [active, invalid] of [['ar', 'en'], ['en', 'ar']]) {
    test(`hidden required ${invalid} field opens from ${active} before native focus`, () => {
        const app = setup(active);
        app.nodes['materials_' + invalid].validity.valid = false;
        assert.equal(app.validate()[0], app.nodes['materials_' + invalid]);
        app.flush();
        assert.equal(app.focus(), app.nodes['materials_' + invalid]);
    });
}
test('multiple invalid panels keep first field visible; later attempts advance after correction', () => {
    const app = setup('en');
    app.nodes.title_ar.validity.valid = false;
    app.nodes.title_en.validity.valid = false;
    assert.deepEqual(app.validate().map(field => field.id), ['title_ar']);
    app.nodes.title_ar.validity.valid = true;
    assert.deepEqual(app.validate().map(field => field.id), ['title_en']);
});
test('general invalid field retains priority without delayed tab autofocus', () => {
    const app = setup('en');
    app.nodes.delivery_date.validity.valid = false;
    app.nodes.title_ar.validity.valid = false;
    assert.equal(app.validate()[0], app.nodes.delivery_date);
    app.flush();
    assert.equal(app.focus(), app.nodes.delivery_date);
});
for (const errors of [['en'], ['ar'], ['en', 'ar']]) {
    test(`server errors ${errors} override saved tab in DOM language order`, () => {
        const app = setup('fr', errors);
        const expected = errors.includes('ar') ? 'ar' : 'en';
        assert.equal(app.nodes['lang-tab-' + expected].attributes['aria-selected'], 'true');
        app.flush();
        assert.equal(app.focus(), undefined);
    });
}
test('arrow navigation leaves focus on tab even after timers run', () => {
    const app = setup();
    app.window.portfolioHandleTabKeydown({ key: 'ArrowRight', preventDefault() {} }, 'ar');
    app.flush();
    assert.equal(app.focus(), app.nodes['lang-tab-en']);
});
test('valid controls including empty optional fields do not interrupt submission', () => {
    const app = setup();
    assert.deepEqual(app.validate(), []);
    assert.equal(app.nodes['lang-tab-ar'].attributes['aria-selected'], 'true');
});
test('native required state follows used active languages without requiring every language', () => {
    const app = setup();
    assert.equal(app.nodes.title_ar.required, true);
    assert.equal(app.nodes.type_ar.required, false);
    assert.equal(app.nodes.title_en.required, false);

    app.input(app.nodes.title_en, 'English title');
    assert.equal(app.nodes.title_ar.required, false);
    assert.equal(app.nodes.title_en.required, true);
    assert.equal(app.nodes.type_en.required, true);
    assert.equal(app.nodes.materials_en.required, true);

    app.input(app.nodes.link_ar, 'https://example.test');
    assert.equal(app.nodes.title_ar.required, true);
    assert.equal(app.nodes.type_ar.required, true);
    assert.equal(app.nodes.materials_ar.required, true);
});

for (const saved of [null, 'en', 'unknown']) {
    test(`normal load with saved=${saved} activates intended tab without moving focus`, () => {
        const app = setup(saved);
        app.flush();
        assert.equal(app.focus(), undefined);
        assert.equal(app.nodes['lang-tab-' + (saved === 'en' ? 'en' : 'ar')].attributes['aria-selected'], 'true');
        app.window.portfolioSwitchLanguageTab('fr');
        app.flush();
        assert.equal(app.focus(), undefined);
    });
}
for (const direction of ['rtl', 'ltr']) {
    test(`${direction} arrows follow visual order, Home/End and rapid switches preserve tab focus`, () => {
        const app = setup('ar', [], direction);
        const codes = ['ar', 'en', 'fr'];
        let index = 0;
        for (const key of ['ArrowLeft', 'ArrowRight', 'Home', 'End', ...Array(30).fill('ArrowLeft')]) {
            let prevented = false;
            app.window.portfolioHandleTabKeydown({ key, preventDefault() { prevented = true; } }, codes[index]);
            index = key === 'Home' ? 0 : key === 'End' ? 2
                : (index + ((key === 'ArrowLeft') === (direction === 'rtl') ? 1 : -1) + 3) % 3;
            assert.equal(prevented, true);
            app.flush();
            assert.equal(app.focus(), app.nodes['lang-tab-' + codes[index]]);
            app.tabs.forEach((tab, i) => {
                assert.equal(tab.attributes['aria-selected'], i === index ? 'true' : 'false');
                assert.equal(tab.attributes.tabindex, i === index ? '0' : '-1');
                assert.equal(app.nodes['lang-panel-' + codes[i]].classList.contains('hidden'), i !== index);
            });
        }
        for (const shiftKey of [false, true]) {
            app.window.portfolioHandleTabKeydown({ key: 'Tab', shiftKey, preventDefault() { assert.fail('Tab must stay native'); } }, codes[index]);
        }
    });
}
