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
async function setup(options = {}) {
    const { window, document } = parseHTML(`<html><body><main id="page" aria-hidden="false"><button id="one" class="btn-open-media-picker" data-target-input="value" data-target-preview="preview" data-store-value="id">One</button><button id="two" class="btn-open-media-picker" data-target-input="value" data-target-preview="preview" data-multiple="true" data-store-value="id">Two</button><input id="value"><div id="preview"></div><span id="preview_reorder_status" role="status"></span></main><aside id="already-inert" inert aria-hidden="true"></aside><section>${markup}</section></body></html>`);
    const modal = document.getElementById('media-picker-modal');
    for (const [key, value] of Object.entries(options.messages || {})) modal.dataset[key] = value;
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
    const items = options.items || [7, 12].map(id => ({ id, file_type: 'image', file_path: `media/${id}.png`, file_original_name: `Quote ' " <img onerror=bad()> ${id}` }));
    window.MEDIA_CONFIG = { csrfToken: 'test-token' };
    const context = { window, document, MutationObserver: window.MutationObserver,
        Event: window.Event, CustomEvent: window.CustomEvent, URLSearchParams, console: options.console || console,
        FormData: class { constructor() { this.values = []; } append(...value) { this.values.push(value); } },
        AbortController, setTimeout: options.setTimeout || setTimeout, clearTimeout: options.clearTimeout || clearTimeout, requestAnimationFrame: fn => fn(),
        getComputedStyle: () => ({ visibility: 'visible' }),
        fetch: options.fetch || (async () => ({ ok: true, json: async () => ({ data: items, current_page: 1, last_page: 1 }) })),
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

test('runtime labels come from safely rendered dialog data', async () => {
    const app = await setup({
        messages: {
            loadMoreLabel: 'Mehr laden',
            noMoreLabel: 'Keine weiteren Medien',
            unnamedLabel: 'Ohne Namen <img onerror=bad()>',
            fileLabel: 'DATEI',
        },
        fetch: async () => ({ ok: true, json: async () => ({
            data: [{ id: 31, file_type: 'document', file_path: 'media/31' }],
            current_page: 1,
            last_page: 2,
        }) }),
    });
    await app.open('one');
    const tile = app.modal.querySelector('[data-id="31"]');
    assert.equal(tile.querySelector('.truncate').textContent, 'Ohne Namen <img onerror=bad()>');
    assert.equal(tile.querySelector('.truncate').querySelector('img'), null);
    assert.equal(tile.querySelector('span').textContent, 'DATEI');
    assert.equal(app.get('media-picker-load-more').textContent, 'Mehr laden');
});

async function racingPicker() {
    const requests = [], timers = new Map();
    const app = await setup({
        console: { error() {}, warn() {} },
        fetch: (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject })),
        setTimeout: fn => { const id = Symbol(); timers.set(id, fn); return id; },
        clearTimeout: id => timers.delete(id),
    });
    app.type = value => {
        app.get('media-picker-search').value = value;
        const event = new app.window.Event('input', { bubbles: true });
        app.get('media-picker-search').dispatchEvent(event);
        // Browsers retain target after dispatch; linkedom resets it.
        event.target = app.get('media-picker-search');
    };
    app.flushDebounce = () => { const callbacks = [...timers.values()]; timers.clear(); callbacks.forEach(fn => fn()); };
    app.answer = async (index, ids) => {
        requests[index].resolve({ ok: true, json: async () => ({ data: ids.map(id => ({ id, file_type: 'image', file_path: `media/${id}.png` })), current_page: 1, last_page: 2 }) });
        await app.settle();
    };
    app.ids = () => [...app.modal.querySelectorAll('.media-picker-item')].map(el => el.dataset.id);
    app.upload = () => {
        const event = new app.window.Event('drop', { bubbles: true, cancelable: true });
        event.dataTransfer = { files: [{ name: 'image.png' }] };
        app.get('media-picker-dropzone').dispatchEvent(event);
    };
    return Object.assign(app, { requests });
}

test('latest debounced search starts while A is pending and slow A cannot replace fast B', async () => {
    const app = await racingPicker();
    await app.open('one'); await app.answer(0, [7]);
    app.type('A'); app.flushDebounce();
    app.type('B'); app.flushDebounce();
    assert.equal(app.requests.length, 3, 'isLoading must not suppress B');
    assert.equal(new URL(app.requests[2].url, 'https://example.test').searchParams.get('search'), 'B');
    await app.answer(2, [12]); await app.answer(1, [7]);
    assert.deepEqual(app.ids(), ['12']);
});

test('upload duplicates are rejected; busy UI, 422 text safety, retry and success recover', async () => {
    const app = await racingPicker();
    await app.open('one'); await app.answer(0, [7]);
    app.upload(); app.upload();
    assert.equal(app.requests.length, 2);
    assert.equal(app.requests[1].options.method, 'POST');
    assert.equal(app.requests[1].options.headers['X-CSRF-TOKEN'], 'test-token');
    assert.equal(app.get('media-picker-upload-btn').disabled, true);
    assert.equal(app.get('media-picker-confirm').disabled, true);
    assert.equal(app.get('media-picker-upload-btn').getAttribute('aria-busy'), 'true');
    assert.equal(app.get('media-picker-close').disabled, false);
    app.requests[1].resolve({ ok: false, status: 422, json: async () => ({ errors: { 'files.0': ['Too large <img src=x onerror=bad()>'] } }) });
    await app.settle();
    assert.equal(app.get('media-picker-upload-status').textContent, 'Too large <img src=x onerror=bad()>');
    assert.equal(app.get('media-picker-upload-status').querySelector('img'), null);
    assert.equal(app.get('media-picker-upload-btn').disabled, false);
    app.upload();
    app.requests[2].resolve({ ok: true, json: async () => ({ uploaded: [{ id: 15, file_path: 'new.png' }, { id: 16, file_path: 'last.png' }] }) });
    await app.settle();
    await app.answer(3, [15, 16]);
    assert.equal(app.get('media-picker-selection-count').textContent, '1');
    assert.equal(app.get('media-picker-upload-btn').disabled, false);
    app.click(app.get('media-picker-confirm'));
    assert.equal(app.get('value').value, '16');
});

for (const [first, next] of [['one', 'two'], ['two', 'one']]) {
    test(`late upload from ${first} cannot mutate reopened ${next}`, async () => {
        const app = await racingPicker();
        await app.open(first); await app.answer(0, [7]);
        app.upload(); app.key('Escape');
        assert.equal(app.requests[1].options.signal.aborted, true);
        app.get('value').value = '12';
        await app.open(next); await app.answer(2, [12]);
        app.upload();
        app.requests[1].resolve({ ok: true, json: async () => ({ uploaded: [{ id: 15, file_path: 'old.png' }] }) });
        await app.settle();
        assert.equal(app.get('media-picker-selection-count').textContent, '1');
        assert.equal(app.get('media-picker-upload-btn').disabled, true);
        const error = new Error('abort'); error.name = 'AbortError';
        app.requests[3].reject(error); await app.settle();
        assert.equal(app.get('media-picker-upload-btn').disabled, false);
        app.click(app.get('media-picker-confirm'));
        assert.equal(app.get('value').value, '12');
    });
}

test('portfolio submit locks once, skips prevented submissions and resets on pageshow', () => {
    const { document, window } = parseHTML('<html><body><form><input name="title" value="Entered"><button id="portfolio-save" data-pending-label="Saving">Save</button><span id="portfolio-submit-status"></span></form></body></html>');
    const form = document.querySelector('form'), button = document.getElementById('portfolio-save');
    Object.defineProperty(button, 'form', { value: form });
    const source = fs.readFileSync(path.join(root, 'resources/views/dashboard/portfolios/_form.blade.php'), 'utf8');
    const code = source.slice(source.indexOf('// Portfolio submit lifecycle'), source.indexOf('// Type suggestions autocomplete'));
    vm.runInNewContext(code, { document, window });
    document.dispatchEvent(new window.Event('DOMContentLoaded'));
    assert.equal(button.disabled, false); // Native invalid forms never dispatch submit.
    const blocked = new window.Event('submit', { cancelable: true }); blocked.preventDefault();
    form.dispatchEvent(blocked); assert.equal(button.disabled, false);
    const first = new window.Event('submit', { cancelable: true }); form.dispatchEvent(first);
    assert.equal(first.defaultPrevented, false); assert.equal(button.disabled, true);
    assert.equal(button.getAttribute('aria-busy'), 'true');
    assert.equal(document.querySelector('input').disabled, false);
    const second = new window.Event('submit', { cancelable: true }); form.dispatchEvent(second);
    assert.equal(second.defaultPrevented, true);
    window.dispatchEvent(new window.Event('pageshow'));
    assert.equal(button.disabled, false); assert.equal(button.textContent, 'Save');
});

test('network failure restores controls and a subsequent multiple upload succeeds', async () => {
    const app = await racingPicker();
    await app.open('two'); await app.answer(0, [7]);
    app.upload(); app.requests[1].reject(new Error('private network detail'));
    await app.settle();
    const message = app.get('media-picker-upload-status').textContent;
    assert.ok(message.length > 0); assert.equal(message.includes('private network detail'), false);
    assert.equal(app.get('media-picker-upload-btn').disabled, false);
    assert.equal(app.get('media-picker-file-input').disabled, false);
    assert.equal(app.get('media-picker-upload-btn').getAttribute('aria-busy'), 'false');
    app.upload();
    app.requests[2].resolve({ ok: true, json: async () => ({ uploaded: [{ id: 15, file_path: 'one.png' }, { id: 16, file_path: 'two.png' }] }) });
    await app.settle(); await app.answer(3, [15, 16]);
    assert.equal(app.get('media-picker-selection-count').textContent, '2');
    app.click(app.get('media-picker-confirm'));
    assert.equal(app.get('value').value, '15,16');
    await app.open('two'); await app.answer(4, [15, 16]);
    app.upload(); assert.equal(app.requests[5].options.method, 'POST');
    app.key('Escape');
    app.requests[5].reject(Object.assign(new Error('abort'), { name: 'AbortError' }));
    await app.settle();
    assert.equal(app.get('media-picker-upload-btn').disabled, false);
});

test('image-only configuration filters requests and selectable results without changing generic pickers', async () => {
    const app = await racingPicker();
    app.get('one').dataset.acceptedType = 'image';
    await app.open('one');
    assert.equal(new URL(app.requests[0].url, 'https://example.test').searchParams.get('type'), 'image');
    assert.equal(app.get('media-picker-file-input').getAttribute('accept'), 'image/*');
    assert.equal(app.modal.querySelector('[data-type="video"]').hidden, true);
    const data = [{ id: 7, file_type: 'image', file_path: 'image.png' }, { id: 19, file_type: 'video', mime_type: 'video/mp4', file_path: 'misleading.png' }];
    app.requests[0].resolve({ ok: true, json: async () => ({ data }) }); await app.settle();
    assert.deepEqual(app.ids(), ['7']);
    app.click(app.modal.querySelector('[data-type="video"]'));
    assert.equal(app.requests.length, 1);
    app.click(app.modal.querySelector('[data-id="7"]')); app.click(app.get('media-picker-confirm'));
    assert.equal(app.get('value').value, '7');
    await app.open('two');
    assert.equal(app.modal.querySelector('[data-type="video"]').hidden, false);
    assert.equal(new URL(app.requests[1].url, 'https://example.test').searchParams.has('type'), false);
    app.requests[1].resolve({ ok: true, json: async () => ({ data }) }); await app.settle();
    assert.deepEqual(app.ids(), ['7', '19']);
    app.key('Escape');
});

test('typing invalidates immediately, before debounce, and stale completion cannot stop current loading', async () => {
    const app = await racingPicker();
    await app.open('one');
    app.type('B');
    assert.equal(app.requests[0].options.signal.aborted, true);
    await app.answer(0, [7]);
    assert.deepEqual(app.ids(), []);
    app.flushDebounce();
    assert.equal(app.requests.length, 2);
    app.type('C'); app.flushDebounce();
    await app.answer(1, []);
    assert.equal(app.get('media-picker-loading').classList.contains('hidden'), false);
    assert.equal(app.get('media-picker-empty').classList.contains('hidden'), true);
    await app.answer(2, [12]);
    assert.equal(app.get('media-picker-loading').classList.contains('hidden'), true);
    assert.deepEqual(app.ids(), ['12']);
});

test('filter supersedes in-flight search and queued debounce without losing the current query', async () => {
    const app = await racingPicker();
    await app.open('two'); await app.answer(0, [7]);
    app.type('A'); app.flushDebounce();
    app.type('B');
    app.click(app.modal.querySelector('[data-type="image"]'));
    app.flushDebounce();
    assert.equal(app.requests.length, 3);
    const params = new URL(app.requests[2].url, 'https://example.test').searchParams;
    assert.equal(params.get('search'), 'B'); assert.equal(params.get('type'), 'image');
    await app.answer(2, []); await app.answer(1, [7]);
    assert.deepEqual(app.ids(), []);
    assert.equal(app.get('media-picker-empty').classList.contains('hidden'), false);
    assert.equal(app.get('media-picker-status').textContent, app.get('media-picker-empty').textContent.trim());
});

test('old cycle and stale failures cannot affect a reopened picker or its restored selection', async () => {
    const app = await racingPicker();
    await app.open('one');
    app.type('queued'); app.key('Escape');
    app.get('value').value = '12';
    await app.open('two'); app.flushDebounce();
    assert.equal(app.requests.length, 2);
    assert.equal(app.get('media-picker-search').value, '');
    assert.equal(new URL(app.requests[1].url, 'https://example.test').searchParams.has('search'), false);
    app.requests[0].reject(new Error('stale failure')); await app.settle();
    assert.equal(app.get('toastContainer'), null);
    assert.equal(app.get('media-picker-loading').classList.contains('hidden'), false);
    await app.answer(1, [12]);
    assert.equal(app.modal.querySelector('[data-id="12"]').getAttribute('aria-pressed'), 'true');
    app.click(app.get('media-picker-confirm'));
    assert.equal(app.get('value').value, '12');
    assert.equal(app.document.activeElement, app.get('two'));
});

test('stale JSON parsing completion and pagination cannot append over newer filter results', async () => {
    const app = await racingPicker();
    await app.open('one');
    let finishJson;
    app.requests[0].resolve({ ok: true, json: () => new Promise(resolve => { finishJson = resolve; }) });
    await app.settle();
    app.type('B'); app.flushDebounce();
    await app.answer(1, [12]);
    finishJson({ data: [{ id: 7 }], current_page: 1, last_page: 5 }); await app.settle();
    assert.deepEqual(app.ids(), ['12']);
    app.click(app.get('media-picker-load-more'));
    assert.equal(new URL(app.requests[2].url, 'https://example.test').searchParams.get('page'), '2');
    app.click(app.modal.querySelector('[data-type="video"]'));
    await app.answer(3, [15]); await app.answer(2, [7]);
    assert.deepEqual(app.ids(), ['15']);
});

test('aborted current fetch does not emit an error notification', async () => {
    const app = await racingPicker();
    await app.open('one');
    const error = new Error('aborted'); error.name = 'AbortError';
    app.requests[0].reject(error); await app.settle();
    assert.equal(app.get('toastContainer'), null);
    assert.equal(app.get('media-picker-loading').classList.contains('hidden'), true);
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
        app.get('value').value = '';
        await app.open(trigger);
        const tiles = app.modal.querySelectorAll('.media-picker-item');
        assert.equal(tiles.length, 2);
        assert.equal(tiles[0].querySelectorAll('img').length, 1);
        assert.ok(tiles[0].textContent.includes('<img onerror=bad()>'));
        app.click(tiles[0]); app.click(tiles[1]);
        assert.equal(tiles[0].getAttribute('aria-pressed'), trigger === 'one' ? 'false' : 'true');
        assert.equal(tiles[1].getAttribute('aria-pressed'), 'true');
        assert.equal(app.get('media-picker-selection-count').parentElement.getAttribute('role'), 'status');
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

test('autocomplete states track existing keyboard, mouse and dismissal behavior safely', () => {
    const { document, window } = parseHTML('<html><body><div class="relative"><input id="type_input_en"><ul id="type_suggestions_en"></ul></div><button id="outside"></button></body></html>');
    const source = fs.readFileSync(path.join(root, 'resources/views/dashboard/portfolios/_form.blade.php'), 'utf8');
    const start = source.indexOf('const _typeSuggestionsData');
    const end = source.indexOf('// Language tab switching (portfolio)', start);
    const code = source.slice(start, end).replace('@json($typeSuggestions ?? [])', JSON.stringify({ en: ['Website', `Quote ' " <img onerror=bad()>`] }));
    const context = { document };
    vm.createContext(context); vm.runInContext(code, context);
    const input = document.getElementById('type_input_en');
    const list = document.getElementById('type_suggestions_en');
    input.value = '';
    context.showSuggestions('en');
    assert.equal(input.getAttribute('aria-expanded'), 'true');
    assert.equal(list.querySelectorAll('[role="option"]').length, 2);
    assert.equal(list.querySelectorAll('img').length, 0);
    context.handleTypeKeydown({ key: 'ArrowDown', preventDefault() {} }, 'en');
    assert.equal(input.getAttribute('aria-activedescendant'), list.firstElementChild.id);
    assert.equal(list.firstElementChild.getAttribute('aria-selected'), 'true');
    context.handleTypeKeydown({ key: 'Enter', preventDefault() {} }, 'en');
    assert.equal(input.value, 'Website');
    assert.equal(input.getAttribute('aria-expanded'), 'false');
    assert.equal(input.hasAttribute('aria-activedescendant'), false);
    for (const key of ['Escape', 'Tab']) {
        input.value = ''; context.showSuggestions('en');
        context.handleTypeKeydown({ key }, 'en');
        assert.equal(input.getAttribute('aria-expanded'), 'false');
    }
    input.value = ''; context.showSuggestions('en');
    list.lastElementChild.dispatchEvent(new window.Event('click', { bubbles: true }));
    assert.ok(input.value.includes('<img onerror=bad()>'));
    assert.equal(input.getAttribute('aria-expanded'), 'false');
    input.value = ''; context.showSuggestions('en');
    document.getElementById('outside').dispatchEvent(new window.Event('click', { bubbles: true }));
    assert.equal(input.getAttribute('aria-expanded'), 'false');
    input.value = 'no match'; context.showSuggestions('en');
    assert.equal(list.style.display, 'none');
});

test('restores current ordered selection; cancel is temporary; additions and removals preserve other IDs', async () => {
    const app = await setup();
    app.get('value').value = '15,7,12';
    await app.open('two');
    const tile = id => app.modal.querySelector(`[data-id="${id}"]`);
    assert.equal(tile(7).getAttribute('aria-pressed'), 'true');
    assert.equal(tile(12).getAttribute('aria-pressed'), 'true');
    assert.equal(app.get('media-picker-selection-count').textContent, '3');
    app.click(app.get('media-picker-confirm'));
    assert.equal(app.get('value').value, '15,7,12');
    await app.open('two');
    app.click(tile(7));
    app.key('Escape');
    assert.equal(app.get('value').value, '15,7,12');
    await app.open('two');
    app.click(tile(7));
    app.click(app.get('media-picker-confirm'));
    assert.equal(app.get('value').value, '15,12');
    await app.open('two');
    app.click(tile(7));
    app.click(app.get('media-picker-confirm'));
    assert.equal(app.get('value').value, '15,12,7');
    await app.open('two');
    app.click(app.get('media-picker-clear'));
    assert.equal(app.get('value').value, '15,12,7');
    assert.equal(app.get('media-picker-confirm').disabled, false);
    app.click(app.get('media-picker-confirm'));
    assert.equal(app.get('value').value, '');
    assert.equal(app.get('preview').children.length, 0);
    assert.equal(app.get('value').dataset.mediaPickerCleared, 'true');
});

test('single restoration, legacy explicit clear intent and separate fields remain isolated', async () => {
    const app = await setup();
    const remove = app.document.createElement('input'); remove.id = 'remove'; remove.value = '0';
    app.get('page').appendChild(remove); app.get('one').dataset.removeInput = 'remove';
    app.get('value').value = '7';
    await app.open('one');
    assert.equal(app.modal.querySelector('[data-id="7"]').getAttribute('aria-pressed'), 'true');
    app.click(app.get('media-picker-confirm'));
    assert.equal(app.get('value').value, '7');
    const other = app.document.createElement('input'); other.id = 'gallery'; other.value = '12';
    app.get('page').appendChild(other); app.get('two').dataset.targetInput = 'gallery';
    await app.open('two');
    assert.equal(app.modal.querySelector('[data-id="7"]').getAttribute('aria-pressed'), 'false');
    assert.equal(app.modal.querySelector('[data-id="12"]').getAttribute('aria-pressed'), 'true');
    app.key('Escape');
    app.get('value').value = '';
    const before = app.get('preview').innerHTML; // Legacy preview with no submitted ID.
    await app.open('one');
    assert.equal(app.get('media-picker-confirm').disabled, true);
    app.click(app.get('media-picker-clear'));
    app.key('Escape');
    assert.equal(remove.value, '0');
    assert.equal(app.get('preview').innerHTML, before);
    await app.open('one');
    app.click(app.get('media-picker-clear')); app.click(app.get('media-picker-confirm'));
    assert.equal(remove.value, '1');
    assert.equal(app.get('value').value, '');
    assert.equal(app.get('preview').children.length, 0);
    await app.open('one');
    app.click(app.modal.querySelector('[data-id="12"]')); app.click(app.get('media-picker-confirm'));
    assert.equal(remove.value, '0');
    assert.equal(app.get('value').value, '12');
});

test('gallery reorder controls synchronize ordered CSV, boundaries, focus and picker reopen', async () => {
    const items = [12, 3, 9].map(id => ({ id, file_type: 'image', file_path: `media/${id}.png`, file_original_name: `Image ${id}` }));
    const app = await setup({
        items,
        messages: { moveEarlierLabel: 'Move earlier', moveLaterLabel: 'Move later', movedPositionLabel: 'Moved to :position' },
    });
    await app.open('two');
    for (const id of [12, 3, 9]) app.click(app.modal.querySelector(`[data-id="${id}"]`));
    app.click(app.get('media-picker-confirm'));

    const previewIds = () => [...app.get('preview').querySelectorAll('.media-picker-preview-item')].map(item => item.dataset.mediaId);
    assert.equal(app.get('value').value, '12,3,9');
    assert.deepEqual(previewIds(), ['12', '3', '9']);
    let nine = app.get('preview').querySelector('[data-media-id="9"]');
    assert.equal(app.get('preview').querySelector('[data-media-id="12"] [data-reorder="earlier"]').disabled, true);
    assert.equal(nine.querySelector('[data-reorder="later"]').disabled, true);
    assert.equal(nine.querySelector('[data-reorder="earlier"]').getAttribute('aria-label'), 'Move earlier');

    app.click(nine.querySelector('[data-reorder="earlier"]'));
    assert.equal(app.get('value').value, '12,9,3');
    assert.equal(app.document.activeElement, nine.querySelector('[data-reorder="earlier"]'));
    app.click(nine.querySelector('[data-reorder="earlier"]'));
    assert.equal(app.get('value').value, '9,12,3');
    assert.deepEqual(previewIds(), ['9', '12', '3']);
    assert.equal(app.get('preview_reorder_status').textContent, 'Moved to 1');

    app.click(nine.querySelector('[data-reorder="later"]'));
    assert.equal(app.get('value').value, '12,9,3');
    await app.open('two');
    app.click(app.get('media-picker-cancel'));
    assert.equal(app.get('value').value, '12,9,3');
});

test('a one-item gallery exposes safe disabled reorder boundaries', async () => {
    const app = await setup({
        items: [{ id: 12, file_type: 'image', file_path: 'media/12.png', file_original_name: 'Only image' }],
        messages: { moveEarlierLabel: 'Move earlier', moveLaterLabel: 'Move later' },
    });
    await app.open('two');
    app.click(app.modal.querySelector('[data-id="12"]'));
    app.click(app.get('media-picker-confirm'));
    const preview = app.get('preview').querySelector('[data-media-id="12"]');
    assert.equal(preview.querySelector('[data-reorder="earlier"]').disabled, true);
    assert.equal(preview.querySelector('[data-reorder="later"]').disabled, true);
    assert.equal(app.get('value').value, '12');
});
