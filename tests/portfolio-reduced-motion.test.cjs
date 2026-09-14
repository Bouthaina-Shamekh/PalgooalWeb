const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const css = fs.readFileSync(
    path.resolve(__dirname, '../public/assets/dashboard/css/style.css'),
    'utf8'
);
const pickerJs = fs.readFileSync(path.resolve(__dirname, '../public/assets/dashboard/js/media-picker.js'), 'utf8');
const form = fs.readFileSync(path.resolve(__dirname, '../resources/views/dashboard/portfolios/_form.blade.php'), 'utf8');
const reducedMotionCss = css.slice(
    css.indexOf('@media (prefers-reduced-motion: reduce)'),
    css.indexOf('.text-muted{', css.indexOf('@media (prefers-reduced-motion: reduce)'))
);

test('portfolio and media picker expose a scoped reduced-motion mode', () => {
    assert.ok(reducedMotionCss, 'missing reduced-motion media query');
    assert.match(reducedMotionCss, /\.portfolio-index \*/);
    assert.match(reducedMotionCss, /\.portfolio-editor \*/);
    assert.match(reducedMotionCss, /\.portfolio-language-tabs \.lang-tab-btn/);
    assert.match(reducedMotionCss, /#media-picker-modal \*/);
    assert.match(reducedMotionCss, /#toastContainer > div/);
    assert.match(reducedMotionCss, /transition-duration: 0\.01ms !important;/);
    assert.match(reducedMotionCss, /animation-duration: 0\.01ms !important;/);
    assert.match(reducedMotionCss, /animation-iteration-count: 1 !important;/);
    assert.match(reducedMotionCss, /scroll-behavior: auto !important;/);
});

test('reduced motion removes decorative image and toast transforms', () => {
    assert.match(reducedMotionCss, /#media-picker-modal \.media-picker-item img,[\s\S]*?transform: none !important;/);
});

test('normal-motion transition classes remain available outside the override', () => {
    assert.match(form, /transition-all duration-200/);
    assert.match(pickerJs, /transition-transform duration-200/);
    assert.match(pickerJs, /transition-all duration-200/);
});
