const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const css = read('public/assets/dashboard/css/style.css');
const index = read('resources/views/dashboard/portfolios/index.blade.php');
const form = read('resources/views/dashboard/portfolios/_form.blade.php');
const picker = read('resources/views/dashboard/partials/media-picker.blade.php');

test('portfolio touch sizing is mobile-only and keeps desktop density', () => {
    const mediaRule = css.match(/@media \(max-width: 767px\) \{([\s\S]*?)\n\}/);
    assert.ok(mediaRule);
    assert.match(mediaRule[1], /min-width: 44px;/);
    assert.match(mediaRule[1], /min-height: 44px;/);
    assert.match(mediaRule[1], /\.portfolio-row-actions/);
    assert.match(mediaRule[1], /\.portfolio-language-tabs \.lang-tab-btn/);
    assert.match(mediaRule[1], /#media-picker-close/);
    assert.doesNotMatch(css.slice(0, mediaRule.index), /\.portfolio-row-actions[^}]+min-height: 44px/s);
});

test('icon-only portfolio actions retain explicit accessible names', () => {
    assert.ok(index.includes(`aria-label="{{ t('dashboard.Edit', 'Edit') }}"`));
    assert.ok(index.includes(`aria-label="{{ t('dashboard.Delete', 'Delete') }}"`));
    assert.match(picker, /id="media-picker-close"[^>]+aria-label=/);
});

test('touch hooks cover toolbar, pagination, form actions and language tabs', () => {
    assert.match(index, /class="portfolio-toolbar /);
    assert.match(index, /class="portfolio-row-actions /);
    assert.match(index, /class="portfolio-pagination /);
    assert.match(form, /class="portfolio-form-actions /);
    assert.match(form, /class="portfolio-language-tabs /);
});
