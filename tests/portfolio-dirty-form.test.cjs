const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const form = fs.readFileSync(path.resolve(__dirname, '../resources/views/dashboard/portfolios/_form.blade.php'), 'utf8');
const index = fs.readFileSync(path.resolve(__dirname, '../resources/views/dashboard/portfolios/index.blade.php'), 'utf8');

test('list links carry only named return context and the form returns through it', () => {
    assert.match(index, /route\('dashboard\.portfolios\.create', \$returnContext\)/);
    assert.match(index, /route\('dashboard\.portfolios\.edit',[\s\S]*?\+ \$returnContext\)/);
    assert.match(form, /name="return_\{\{ \$returnKey \}\}"/);
    assert.match(form, /route\('dashboard\.portfolios\.index', \$returnContext \?\? \[\]\)/);
    assert.doesNotMatch(form, /name="return_url"/);
});

test('dirty state snapshots real form data and excludes navigation metadata', () => {
    assert.match(form, /new FormData\(form\)\.entries\(\)/);
    assert.match(form, /'_token', '_method', 'return_search', 'return_page', 'return_per_page'/);
    assert.match(form, /snapshot\(\) === initialState/);
    assert.match(form, /addEventListener\('beforeunload'/);
    assert.match(form, /event\.returnValue = ''/);
});

test('submit protection waits for other handlers and remains active for prevented submissions', () => {
    assert.match(form, /queueMicrotask\(function \(\) \{/);
    assert.match(form, /!event\.defaultPrevented && form\.checkValidity\(\)/);
    assert.match(form, /addEventListener\('pageshow'[\s\S]*?submitting = false/);
});
