// Requires linkedom on NODE_PATH. Run: node --test tests/media-picker-dialog.test.cjs
// Uses the real partial and full picker script; layout/focus are simulated, not a browser.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const { parseHTML } = require('linkedom');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '..');
const markup = fs.readFileSync(path.join(root, 'resources/views/dashboard/partials/media-picker.blade.php'), 'utf8');
const script = fs.readFileSync(path.join(root, 'public/assets/dashboard/js/media-picker.js'), 'utf8');
async function setup() {
    const { window, document } = parseHTML(`<html><body><main id="page" aria-hidden="false"><button id="one" class="btn-open-media-picker" data-target-input="value" data-target-preview="preview">One</button><button id="two" class="btn-open-media-picker" data-target-input="value" data-target-preview="preview" data-multiple="true">Two</button><input id="value"><div id="preview"></div></main><aside id="already-inert" inert aria-hidden="true"></aside><section>${markup}</section></body></html>`);
    let active = document.body;
    Object.defineProperty(document, 'activeElement', { get: () => active });
    const visible = el => !el.closest('.hidden, [hidden]');
    window.HTMLElement.prototype.getClientRects = function () { return visible(this) ? [{}] : []; };
    Object.defineProperty(window.HTMLElement.prototype, 'tabIndex', { configurable: true, get() {
        return this.hasAttribute('tabindex') ? Number(this.getAttribute('tabindex')) : (this.matches('button,input,select,textarea,a[href]') ? 0 : -1);
    }});
    window.HTMLElement.prototype.focus = function () {
        active = this;
        this.dispatchEvent(new window.Event('focusin', { bubbles: true }));
    };
    const items = [7, 12].map(id => ({ id, file_type: 'image', file_path: `media/${id}.png`, file_original_name: `Quote ' " <img onerror=bad()> ${id}` }));
    const context = { window, document, MutationObserver: window.MutationObserver,
        Event: window.Event, CustomEvent: window.CustomEvent, URLSearchParams, console,
        setTimeout, clearTimeout, requestAnimationFrame: fn => fn(),
        getComputedStyle: () => ({ visibility: 'visible' }),
        fetch: async () => ({ ok: true, json: async () => ({ data: items, current_page: 1, last_page: 1 }) }),
    };
    vm.runInNewContext(script, context);
    document.dispatchEvent(new window.Event('DOMContentLoaded'));
    const get = id => document.getElementById(id);
    const click = el => el.dispatchEvent(new window.Event('click', { bubbles: true, cancelable: true }));
    const key = (key, shiftKey = false) => {
        const event = new window.Event('keydown', { bubbles: true, cancelable: true });
        Object.assign(event, { key, shiftKey });
        active.dispatchEvent(event);
    };
    const settle = () => new Promise(resolve => setImmediate(resolve));
    const open = async id => { get(id).focus(); click(get(id)); await settle(); };
    return { window, document, get, click, key, open, settle, modal: get('media-picker-modal') };
}
test('named dialog enters search, traps both directions, and restores isolation on Escape', async () => {
    const app = await setup();
    await app.open('one');
    assert.equal(app.modal.getAttribute('role'), 'dialog');
    assert.equal(app.modal.getAttribute('aria-modal'), 'true');
    assert.ok(app.get(app.modal.getAttribute('aria-labelledby')).textContent.trim());
    assert.equal(app.document.activeElement, app.get('media-picker-search'));
    assert.equal(app.get('page').hasAttribute('inert'), true);
    assert.equal(app.get('page').getAttribute('aria-hidden'), 'true');
    for (let i = 0; i < 60; i++) {
        app.key('Tab', i >= 30);
        assert.ok(app.modal.contains(app.document.activeElement));
        assert.equal(app.document.activeElement.matches(':disabled'), false);
    }
    app.get('media-picker-upload-btn').focus();
    app.key('Tab', true);
    assert.equal(app.document.activeElement, app.get('media-picker-cancel'));
    app.key('Tab');
    assert.equal(app.document.activeElement, app.get('media-picker-upload-btn'));
    app.get('two').focus(); // A script trying to escape the dialog is redirected.
    assert.equal(app.document.activeElement, app.get('media-picker-search'));
    app.key('Escape');
    assert.equal(app.document.activeElement, app.get('one'));
    assert.ok(app.modal.classList.contains('hidden'));
    assert.equal(app.get('page').hasAttribute('inert'), false);
    assert.equal(app.get('page').getAttribute('aria-hidden'), 'false');
    assert.equal(app.get('already-inert').hasAttribute('inert'), true);
});
test('close, cancel, backdrop and overlay work repeatedly with the current trigger', async () => {
    const app = await setup();
    for (const closeId of ['media-picker-close', 'media-picker-cancel', 'media-picker-backdrop', 'media-picker-modal']) {
        for (const trigger of ['one', 'two']) {
            await app.open(trigger);
            app.click(app.get(closeId));
            assert.equal(app.document.activeElement, app.get(trigger));
            assert.equal(app.get('page').hasAttribute('inert'), false);
        }
    }
});
test('zero controls falls back to dialog; removed trigger does not retain stale focus', async () => {
    const app = await setup();
    await app.open('one');
    app.modal.querySelectorAll('button,input').forEach(el => el.setAttribute('disabled', ''));
    app.key('Tab');
    assert.equal(app.document.activeElement, app.modal);
    app.key('Tab', true);
    assert.equal(app.document.activeElement, app.modal);
    app.get('one').remove();
    app.key('Escape');
    assert.equal(app.get('page').hasAttribute('inert'), false);
});
test('single/multiple mouse selection preserves IDs, safe captions, previews and confirmation cleanup', async () => {
    const app = await setup();
    for (const trigger of ['one', 'two']) {
        await app.open(trigger);
        const tiles = app.modal.querySelectorAll('.media-picker-item');
        assert.equal(tiles.length, 2);
        assert.equal(tiles[0].querySelectorAll('img').length, 1);
        assert.ok(tiles[0].textContent.includes('<img onerror=bad()>'));
        app.click(tiles[0]); app.click(tiles[1]);
        app.click(app.get('media-picker-confirm'));
        assert.equal(app.get('value').value, trigger === 'one' ? '12' : '7,12');
        assert.equal(app.get('preview').querySelectorAll('img').length, trigger === 'one' ? 1 : 2);
        assert.equal(app.document.activeElement, app.get(trigger));
        assert.equal(app.get('page').hasAttribute('inert'), false);
    }
});
test('new background content is isolated and restored', async () => {
    const app = await setup();
    await app.open('one');
    const added = app.document.createElement('button');
    app.document.body.appendChild(added);
    await app.settle();
    assert.equal(added.hasAttribute('inert'), true);
    app.key('Escape');
    assert.equal(added.hasAttribute('inert'), false);
    assert.equal(added.hasAttribute('aria-hidden'), false);
});
