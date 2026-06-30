<?php

use App\Support\SafeUrl;

test('safe url allows supported public href values', function (string $url): void {
    expect(SafeUrl::toHref($url))->toBe($url);
})->with([
    '/about',
    './about',
    '../about',
    '#',
    '#contact',
    'http://example.com',
    'https://example.com',
    'https://wa.me/123456',
    'mailto:support@example.com',
    'tel:+123456789',
    'sms:+123456789',
    'whatsapp://send?phone=123456',
]);

test('safe url rejects unsafe or empty href values', function (string $url): void {
    expect(SafeUrl::toHref($url))->toBe('#');
})->with([
    '',
    '   ',
    '//example.com',
    'javascript:alert(1)',
    'data:text/html;base64,PHNjcmlwdD4=',
    'vbscript:msgbox(1)',
    'file:///etc/passwd',
]);
