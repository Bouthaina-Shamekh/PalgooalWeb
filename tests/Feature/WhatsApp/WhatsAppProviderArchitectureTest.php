<?php

use App\WhatsApp\DTOs\WhatsAppDocumentMessage;
use App\WhatsApp\DTOs\WhatsAppSendResult;
use App\WhatsApp\Exceptions\InvalidWhatsAppPhoneException;
use App\WhatsApp\Exceptions\WhatsAppConfigurationException;
use App\WhatsApp\Exceptions\WhatsAppException;
use App\WhatsApp\Gateways\MockWhatsAppGateway;
use App\WhatsApp\Support\WhatsAppPhoneNormalizer;
use App\WhatsApp\WhatsAppManager;
use Illuminate\Support\Facades\Http;

function fakePdfBytes(): string
{
    // A byte-for-byte stand-in for a real PDF: starts with the real PDF
    // magic header and includes non-UTF-8 / null bytes, the way a real
    // rendered PDF's binary stream does -- proves the DTO does not mangle
    // binary content (e.g. by accidentally running it through any text
    // encoding/normalization step).
    return "%PDF-1.4\n" . random_bytes(64) . "\x00\xFF\x00" . "%%EOF";
}

function makeMessage(array $overrides = []): WhatsAppDocumentMessage
{
    $defaults = [
        'recipient' => '+970599123456',
        'filename' => 'invoice-INV-TEST.pdf',
        'mimeType' => 'application/pdf',
        'contents' => fakePdfBytes(),
        'caption' => null,
    ];

    $args = array_merge($defaults, $overrides);

    return new WhatsAppDocumentMessage(
        recipient: $args['recipient'],
        filename: $args['filename'],
        mimeType: $args['mimeType'],
        contents: $args['contents'],
        caption: $args['caption'],
    );
}

// ── 1. Manager resolves MockWhatsAppGateway correctly ───────────────────────

test('manager resolves MockWhatsAppGateway when explicitly configured', function (): void {
    config(['whatsapp.default_provider' => 'mock']);

    $gateway = app(WhatsAppManager::class)->gateway();

    expect($gateway)->toBeInstanceOf(MockWhatsAppGateway::class);
    expect($gateway->name())->toBe('mock_whatsapp');
});

// ── 2. Mock sendDocument returns a deterministic success result ────────────

test('mock gateway sendDocument returns a deterministic success result', function (): void {
    config(['whatsapp.default_provider' => 'mock']);
    $gateway = app(WhatsAppManager::class)->gateway();

    $message = makeMessage(['contents' => "%PDF-1.4\nfixed-body\n%%EOF"]);

    $first = $gateway->sendDocument($message);
    $second = $gateway->sendDocument($message);

    expect($first)->toBeInstanceOf(WhatsAppSendResult::class);
    expect($first->success)->toBeTrue();
    expect($first->isSuccess())->toBeTrue();
    expect($first->providerStatus)->toBe(WhatsAppSendResult::STATUS_SENT);
    expect($first->providerMessageId)->not->toBeNull();

    // Deterministic: identical input -> identical provider message ID.
    expect($second->providerMessageId)->toBe($first->providerMessageId);
});

test('mock gateway never performs any network I/O', function (): void {
    Http::fake();

    config(['whatsapp.default_provider' => 'mock']);
    $gateway = app(WhatsAppManager::class)->gateway();

    $gateway->sendDocument(makeMessage());

    Http::assertNothingSent();
});

// ── 3. Document DTO carries PDF bytes safely ────────────────────────────────

test('document DTO carries binary contents byte-for-byte, unmangled', function (): void {
    $bytes = fakePdfBytes();

    $message = makeMessage(['contents' => $bytes]);

    expect($message->contents)->toBe($bytes);
    expect(strlen($message->contents))->toBe(strlen($bytes));
    expect(str_starts_with($message->contents, '%PDF-1.4'))->toBeTrue();
});

test('mock gateway result raw payload reports the correct byte length for the document sent', function (): void {
    config(['whatsapp.default_provider' => 'mock']);
    $gateway = app(WhatsAppManager::class)->gateway();

    $bytes = fakePdfBytes();
    $message = makeMessage(['contents' => $bytes]);

    $result = $gateway->sendDocument($message);

    expect($result->raw['byte_length'])->toBe(strlen($bytes));
});

// ── 4. Invalid/empty document input fails clearly ───────────────────────────

test('document DTO rejects invalid non-phone input with WhatsAppException', function (array $overrides): void {
    expect(fn () => makeMessage($overrides))->toThrow(WhatsAppException::class);
})->with([
    'empty filename' => [['filename' => '']],
    'blank filename' => [['filename' => '   ']],
    'empty mime type' => [['mimeType' => '']],
    'malformed mime type (no slash)' => [['mimeType' => 'pdf']],
    'empty contents' => [['contents' => '']],
    'blank caption' => [['caption' => '   ']],
]);

test('document DTO rejects an invalid phone with InvalidWhatsAppPhoneException, not the generic WhatsAppException', function (): void {
    $thrown = null;

    try {
        makeMessage(['recipient' => '0599123456']);
    } catch (\Throwable $e) {
        $thrown = $e;
    }

    expect($thrown)->toBeInstanceOf(InvalidWhatsAppPhoneException::class);
    // InvalidWhatsAppPhoneException predates this phase and is intentionally
    // NOT a subclass of WhatsAppException -- see WhatsAppException's docblock.
    expect($thrown)->not->toBeInstanceOf(WhatsAppException::class);
});

// ── 5. Unknown/unconfigured provider fails clearly ──────────────────────────

test('manager throws WhatsAppConfigurationException when no provider is configured', function (): void {
    config(['whatsapp.default_provider' => null]);

    expect(fn () => app(WhatsAppManager::class)->gateway())
        ->toThrow(WhatsAppConfigurationException::class);
});

test('manager throws WhatsAppConfigurationException for an unknown provider key', function (): void {
    config(['whatsapp.default_provider' => 'definitely_not_a_real_provider']);

    expect(fn () => app(WhatsAppManager::class)->gateway())
        ->toThrow(WhatsAppConfigurationException::class);
});

// ── 6. No network request occurs (see also the dedicated test in block 2) ──

test('resolving an unconfigured provider never reaches out to any gateway', function (): void {
    Http::fake();
    config(['whatsapp.default_provider' => null]);

    try {
        app(WhatsAppManager::class)->gateway();
    } catch (WhatsAppConfigurationException) {
        // expected
    }

    Http::assertNothingSent();
});

// ── 7. Phone normalizer remains independent and is reused, not duplicated ──

test('document DTO normalizes recipient using WhatsAppPhoneNormalizer, not its own logic', function (): void {
    $raw = '+966 50 123 4567';

    $message = makeMessage(['recipient' => $raw]);

    expect($message->recipient)->toBe(WhatsAppPhoneNormalizer::normalize($raw));
    expect($message->recipient)->toBe('966501234567');
});

test('document DTO fails closed on a local number exactly like the normalizer does directly', function (): void {
    $local = '0501234567';

    $directException = null;
    try {
        WhatsAppPhoneNormalizer::normalize($local);
    } catch (InvalidWhatsAppPhoneException $e) {
        $directException = $e;
    }

    $dtoException = null;
    try {
        makeMessage(['recipient' => $local]);
    } catch (InvalidWhatsAppPhoneException $e) {
        $dtoException = $e;
    }

    expect($directException)->not->toBeNull();
    expect($dtoException)->not->toBeNull();
    expect($dtoException->getMessage())->toBe($directException->getMessage());
});
