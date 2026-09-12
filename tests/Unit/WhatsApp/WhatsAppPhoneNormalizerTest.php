<?php

use App\WhatsApp\Exceptions\InvalidWhatsAppPhoneException;
use App\WhatsApp\Support\WhatsAppPhoneNormalizer;

test('normalizes valid international phone numbers to digits-only format', function (string $raw, string $expected): void {
    expect(WhatsAppPhoneNormalizer::normalize($raw))->toBe($expected);
})->with([
    '+970599123456' => ['+970599123456', '970599123456'],
    '00970599123456' => ['00970599123456', '970599123456'],
    '+966 50 123 4567' => ['+966 50 123 4567', '966501234567'],
    '+1 (202) 555-0123' => ['+1 (202) 555-0123', '12025550123'],
    // Canonical, already-normalized, digits-only input (no "+"/"00") must
    // also be accepted -- this is exactly the shape normalize() itself
    // returns, and the shape Client::phone may already be stored in.
    'canonical Palestine number' => ['970599123456', '970599123456'],
    'canonical Saudi number' => ['966501234567', '966501234567'],
    'canonical US number' => ['12025550123', '12025550123'],
]);

test('rejects null, empty, local, and malformed phone numbers', function (?string $raw): void {
    expect(fn () => WhatsAppPhoneNormalizer::normalize($raw))
        ->toThrow(InvalidWhatsAppPhoneException::class);
})->with([
    'null' => [null],
    'empty string' => [''],
    'whitespace only' => ['   '],
    'local number, no country code' => ['0599123456'],
    'another local number, no country code' => ['0501234567'],
    'local number, no country code (3)' => ['012345678'],
    'alphabetic input' => ['abc'],
    'plus with letters' => ['+abc123'],
    '00-prefixed but too short' => ['00123'],
    'bare digits, too short' => ['123'],
    'overly long input' => ['+9705991234567890123'],
    'mixed garbage' => ['+97!05@99#12$34%56'],
    'leading zero after plus' => ['+0599123456'],
    'misplaced plus' => ['97+0599123456'],
]);

test('never infers or guesses a country code for an obviously local number', function (string $raw): void {
    expect(fn () => WhatsAppPhoneNormalizer::normalize($raw))
        ->toThrow(InvalidWhatsAppPhoneException::class, 'refusing to guess');
})->with([
    'starts with 0, no prefix' => ['0599123456'],
    'starts with 0, no prefix (2)' => ['0501234567'],
    'starts with 0, no prefix (3)' => ['012345678'],
]);

test('normalize() is idempotent for every accepted input', function (string $raw): void {
    $normalized = WhatsAppPhoneNormalizer::normalize($raw);

    expect(WhatsAppPhoneNormalizer::normalize($normalized))->toBe($normalized);
})->with([
    'Palestine, "+" input' => ['+970599123456'],
    'Palestine, "00" input' => ['00970599123456'],
    'Palestine, canonical input' => ['970599123456'],
    'Saudi Arabia, "+" input' => ['+966501234567'],
    'Saudi Arabia, formatted "+" input' => ['+966 50 123 4567'],
    'Saudi Arabia, canonical input' => ['966501234567'],
    'US, "+" input' => ['+12025550123'],
    'US, formatted "+" input' => ['+1 (202) 555-0123'],
    'US, canonical input' => ['12025550123'],
]);

test('documented limitation: a non-zero-leading bare number with no explicit prefix is accepted as-is', function (): void {
    // Known, accepted trade-off (see class docblock "Known limitations"):
    // without a full country-calling-code dataset, this cannot be reliably
    // distinguished from a genuinely ambiguous national number. It is NOT a
    // local number by the one signal this normalizer actually checks (a
    // leading "0"), so -- per the smallest-conservative-rule policy -- it is
    // accepted rather than guessed at or silently altered.
    expect(WhatsAppPhoneNormalizer::normalize('599123456'))->toBe('599123456');
});
