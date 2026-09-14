const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const css = fs.readFileSync(path.join(root, 'public/assets/dashboard/css/style.css'), 'utf8');
const index = fs.readFileSync(path.join(root, 'resources/views/dashboard/portfolios/index.blade.php'), 'utf8');
const form = fs.readFileSync(path.join(root, 'resources/views/dashboard/portfolios/_form.blade.php'), 'utf8');

function luminance(hex) {
    return hex.match(/[0-9a-f]{2}/gi)
        .map(channel => parseInt(channel, 16) / 255)
        .map(channel => channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4)
        .reduce((total, channel, index) => total + channel * [0.2126, 0.7152, 0.0722][index], 0);
}

function contrast(foreground, background) {
    const values = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
    return (values[0] + 0.05) / (values[1] + 0.05);
}

test('portfolio dark theme uses the dashboard data attribute and scoped module hooks', () => {
    assert.match(index, /class="portfolio-index grid/);
    assert.match(form, /class="portfolio-language-tabs /);
    assert.match(css, /\[data-pc-theme="dark"\] \.portfolio-index/);
    assert.match(css, /\[data-pc-theme="dark"\] #media-picker-modal > div/);
    assert.doesNotMatch(css, /\.dark \.portfolio-index/);
});

test('finding 8 light contrast values remain intact', () => {
    assert.match(css, /:root:not\(\[data-pc-theme="dark"\]\) \.portfolio-secondary \{\s*color: #4b5563;/);
    assert.match(css, /:root:not\(\[data-pc-theme="dark"\]\) \.portfolio-placeholder[^}]+color: #6b7280;/s);
});

test('dark portfolio text and status pairs meet WCAG AA normal-text contrast', () => {
    const pairs = [
        ['#f3f4f6', '#263240'],
        ['#9ca3af', '#1b232d'],
        ['#9ca3af', '#263240'],
        ['#6ee7b7', '#022c22'],
        ['#93c5fd', '#172554'],
        ['#d1d5db', '#1f2937'],
        ['#9ca3af', '#030712'],
    ];

    for (const [foreground, background] of pairs) {
        assert.ok(contrast(foreground, background) >= 4.5, `${foreground} on ${background}`);
    }
});
