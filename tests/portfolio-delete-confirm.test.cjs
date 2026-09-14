const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const view = fs.readFileSync(path.join(__dirname, '../resources/views/dashboard/portfolios/index.blade.php'), 'utf8');
const scripts = [...view.matchAll(/<script>([\s\S]*?)<\/script>/g)];
const script = scripts.at(-1)[1];

function setup(confirmResult) {
    let handler;
    const messages = [];
    const document = { addEventListener(type, callback) {
        assert.equal(type, 'submit');
        assert.equal(handler, undefined, 'Only one delegated listener may be registered');
        handler = callback;
    }};
    const window = { confirm(message) { messages.push(message); return confirmResult; } };
    vm.runInNewContext(script, { document, window });
    return { messages, submit(form) {
        const event = { target: form, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
        handler(event);
        return event;
    }};
}

function form(id, message) {
    return {
        id, dataset: { confirm: message },
        closest(selector) {
            assert.equal(selector, '.portfolio-delete-form[data-confirm]');
            return this;
        },
    };
}

const specialMessage = `Don't delete "Alpha" <img src=x onerror=alert(1)> C:\\portfolio\\'quoted' — هل أنت متأكد؟`;

test('special translated text is passed unchanged as data to native confirm', () => {
    const app = setup(false);
    const event = app.submit(form('first', specialMessage));
    assert.deepEqual(app.messages, [specialMessage]);
    assert.equal(event.defaultPrevented, true);
});

test('confirm allows the intended form once and multiple rows stay isolated', () => {
    const app = setup(true);
    const first = form('first', `First's "message"`);
    const second = form('second', 'ثاني <b>كنص</b> \\');
    assert.equal(app.submit(first).defaultPrevented, false);
    assert.equal(app.submit(second).defaultPrevented, false);
    assert.deepEqual(app.messages, [first.dataset.confirm, second.dataset.confirm]);
});

test('unrelated forms are ignored without confirmation or cancellation', () => {
    const app = setup(false);
    const event = app.submit({ closest: () => null });
    assert.equal(event.defaultPrevented, false);
    assert.deepEqual(app.messages, []);
});

test('delete action has no executable inline translation interpolation', () => {
    assert.doesNotMatch(view, /onsubmit\s*=\s*["'][^"']*confirm/i);
    assert.doesNotMatch(view, /onclick\s*=\s*["'][^"']*confirm/i);
    assert.match(view, /data-confirm="\{\{\s*t\('dashboard\.Confirm_Delete_Portfolio'/);
    assert.match(script, /window\.confirm\(form\.dataset\.confirm\)/);
});
