<?php

use App\WhatsApp\Contracts\WhatsAppGatewayInterface;
use App\WhatsApp\DTOs\WhatsAppDocumentMessage;
use App\WhatsApp\DTOs\WhatsAppSendResult;
use App\WhatsApp\Exceptions\WhatsAppConfigurationException;
use App\WhatsApp\Exceptions\WhatsAppConfirmedSendFailureException;
use App\WhatsApp\Exceptions\WhatsAppIndeterminateSendException;
use App\WhatsApp\Exceptions\WhatsAppSendException;
use App\WhatsApp\Gateways\MetaCloudApiGateway;
use App\WhatsApp\WhatsAppManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

const META_MEDIA_URL = 'https://graph.facebook.com/v21.0/1234567890/media';
const META_MESSAGES_URL = 'https://graph.facebook.com/v21.0/1234567890/messages';

function metaFakePdfBytes(): string
{
    // Real magic header + non-UTF-8 bytes, the way a real rendered PDF's
    // binary stream does -- proves the adapter does not mangle binary
    // content on its way into the multipart body.
    return "%PDF-1.4\n" . random_bytes(32) . "\x00\xFF\x00" . "%%EOF";
}

function metaMakeMessage(array $overrides = []): WhatsAppDocumentMessage
{
    $defaults = [
        'recipient' => '+970599123456',
        'filename' => 'invoice-INV-TEST.pdf',
        'mimeType' => 'application/pdf',
        'contents' => metaFakePdfBytes(),
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

function configureMetaProvider(array $overrides = []): void
{
    config(array_merge([
        'whatsapp.default_provider' => 'meta',
        'whatsapp.meta.access_token' => 'test-access-token-do-not-leak',
        'whatsapp.meta.phone_number_id' => '1234567890',
        'whatsapp.meta.graph_version' => 'v21.0',
    ], $overrides));
}

// ── 1. Manager resolves MetaCloudApiGateway when provider=meta ─────────────

test('manager resolves MetaCloudApiGateway when provider=meta', function (): void {
    configureMetaProvider();

    $gateway = app(WhatsAppManager::class)->gateway();

    expect($gateway)->toBeInstanceOf(MetaCloudApiGateway::class);
    expect($gateway->name())->toBe('meta');
});

// ── 2-4. Missing config fails BEFORE any HTTP call ──────────────────────────

test('missing access token fails before any HTTP call', function (): void {
    Http::fake();
    configureMetaProvider(['whatsapp.meta.access_token' => null]);

    expect(fn () => app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage()))
        ->toThrow(WhatsAppConfigurationException::class);

    Http::assertNothingSent();
});

test('missing phone number id fails before any HTTP call', function (): void {
    Http::fake();
    configureMetaProvider(['whatsapp.meta.phone_number_id' => null]);

    expect(fn () => app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage()))
        ->toThrow(WhatsAppConfigurationException::class);

    Http::assertNothingSent();
});

test('missing graph version fails before any HTTP call', function (): void {
    Http::fake();
    configureMetaProvider(['whatsapp.meta.graph_version' => null]);

    expect(fn () => app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage()))
        ->toThrow(WhatsAppConfigurationException::class);

    Http::assertNothingSent();
});

// ── 5-8, 13. Full happy path: request shape + response mapping (unchanged) ──

test('successful send performs exactly the documented two-step flow and maps the result', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['id' => 'MEDIA123'], 200),
        META_MESSAGES_URL => Http::response(['messages' => [['id' => 'wamid.TEST123']]], 200),
    ]);

    configureMetaProvider();
    $gateway = app(WhatsAppManager::class)->gateway();

    $bytes = metaFakePdfBytes();
    $message = metaMakeMessage([
        'recipient' => '+970 59 912 3456',
        'filename' => 'invoice-INV-2026-0001.pdf',
        'mimeType' => 'application/pdf',
        'contents' => $bytes,
        'caption' => 'Your invoice is attached',
    ]);

    $result = $gateway->sendDocument($message);

    // -- Response mapping (7, 8) --
    expect($result)->toBeInstanceOf(WhatsAppSendResult::class);
    expect($result->success)->toBeTrue();
    expect($result->providerMessageId)->toBe('wamid.TEST123');
    expect($result->providerStatus)->toBe(WhatsAppSendResult::STATUS_SENT);
    expect($result->raw['media_id'])->toBe('MEDIA123');

    // -- Exactly two calls, nothing more (13) --
    Http::assertSentCount(2);

    // -- Media upload request contract (5) --
    Http::assertSent(function (Request $request) use ($bytes, $message) {
        if ($request->url() !== META_MEDIA_URL) {
            return false;
        }

        expect($request->method())->toBe('POST');
        expect($request->hasHeader('Authorization', 'Bearer test-access-token-do-not-leak'))->toBeTrue();
        expect($request->isMultipart())->toBeTrue();
        expect($request->hasFile('messaging_product', 'whatsapp'))->toBeTrue();
        expect($request->hasFile('file', $bytes, $message->filename))->toBeTrue();

        return true;
    });

    // -- Message send request contract (6 covered by the media assertion above; 7 here) --
    Http::assertSent(function (Request $request) use ($message) {
        if ($request->url() !== META_MESSAGES_URL) {
            return false;
        }

        expect($request->method())->toBe('POST');
        expect($request->hasHeader('Authorization', 'Bearer test-access-token-do-not-leak'))->toBeTrue();
        expect($request->isJson())->toBeTrue();
        expect($request['messaging_product'])->toBe('whatsapp');
        expect($request['to'])->toBe($message->recipient);
        expect($request['to'])->toBe('970599123456');
        expect($request['type'])->toBe('document');
        expect($request['document']['id'])->toBe('MEDIA123');
        expect($request['document']['filename'])->toBe($message->filename);
        expect($request['document']['caption'])->toBe('Your invoice is attached');

        return true;
    });
});

test('successful send omits document.caption entirely when no caption was given', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['id' => 'MEDIA123'], 200),
        META_MESSAGES_URL => Http::response(['messages' => [['id' => 'wamid.TEST123']]], 200),
    ]);

    configureMetaProvider();
    app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage(['caption' => null]));

    Http::assertSent(function (Request $request) {
        if ($request->url() !== META_MESSAGES_URL) {
            return false;
        }

        expect(array_key_exists('caption', $request['document']))->toBeFalse();

        return true;
    });
});

// ── 3, 4. Confirmed failure: non-2xx, exposes provider error code if present ─

test('media upload non-2xx response throws a CONFIRMED failure and exposes the provider error code', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['error' => ['message' => 'Invalid OAuth access token', 'code' => 190]], 401),
    ]);

    configureMetaProvider();

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppConfirmedSendFailureException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown)->not->toBeInstanceOf(WhatsAppIndeterminateSendException::class);
    expect($thrown->providerErrorCode())->toBe('190');

    Http::assertSentCount(1);
});

test('message send non-2xx response throws a CONFIRMED failure and exposes the provider error code', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['id' => 'MEDIA123'], 200),
        META_MESSAGES_URL => Http::response(['error' => ['message' => 'Recipient phone number not in allowed list', 'code' => 100]], 400),
    ]);

    configureMetaProvider();

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppConfirmedSendFailureException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown->providerErrorCode())->toBe('100');

    Http::assertSentCount(2);
});

test('confirmed failure is still thrown when Meta returns non-2xx with no parseable error body', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response('Service Unavailable', 503),
    ]);

    configureMetaProvider();

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppConfirmedSendFailureException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown->providerErrorCode())->toBeNull();
});

// ── 5. Provider error subcode is preserved safely if present ───────────────

test('provider error code includes the subcode when Meta returns both code and error_subcode', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response([
            'error' => [
                'message' => 'Message failed to send because more than 24 hours have passed since the customer last replied',
                'code' => 131047,
                'error_subcode' => 2494055,
            ],
        ], 400),
    ]);

    configureMetaProvider();

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppConfirmedSendFailureException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown->providerErrorCode())->toBe('131047:2494055');

    // This exception class attaches no business meaning to that code (e.g.
    // it does not decide this means "the conversation window is closed") --
    // it only preserves the raw provider-neutral value.
    expect($thrown->getMessage())->not->toContain('outside_window');
});

test('provider error code omits the subcode segment when only code is present', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['error' => ['message' => 'Unsupported post request', 'code' => 4]], 400),
    ]);

    configureMetaProvider();

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppConfirmedSendFailureException $e) {
        $thrown = $e;
    }

    expect($thrown->providerErrorCode())->toBe('4');
});

// ── 6, 7. Indeterminate: connection failure (never confirmed) ──────────────

test('connection failure during media upload throws an INDETERMINATE exception', function (): void {
    Http::fake([
        META_MEDIA_URL => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    configureMetaProvider();

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppIndeterminateSendException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown)->not->toBeInstanceOf(WhatsAppConfirmedSendFailureException::class);
});

test('connection failure during message send throws an INDETERMINATE exception', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['id' => 'MEDIA123'], 200),
        META_MESSAGES_URL => fn () => throw new ConnectionException('Connection reset by peer'),
    ]);

    configureMetaProvider();

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppIndeterminateSendException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
});

// ── 8, 9. Indeterminate: 2xx but missing the expected id ────────────────────

test('media upload 2xx with no id throws an INDETERMINATE exception, not confirmed', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['unexpected' => 'shape'], 200),
    ]);

    configureMetaProvider();

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppIndeterminateSendException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown)->not->toBeInstanceOf(WhatsAppConfirmedSendFailureException::class);

    Http::assertSentCount(1);
});

test('message send 2xx with no message id throws an INDETERMINATE exception, not confirmed', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['id' => 'MEDIA123'], 200),
        META_MESSAGES_URL => Http::response(['messages' => []], 200),
    ]);

    configureMetaProvider();

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppIndeterminateSendException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown)->not->toBeInstanceOf(WhatsAppConfirmedSendFailureException::class);

    Http::assertSentCount(2);
});

// ── Both subclasses remain catchable as the shared base type ───────────────

test('both confirmed and indeterminate exceptions are still catchable as WhatsAppSendException', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['error' => ['message' => 'rejected']], 400),
    ]);
    configureMetaProvider();
    expect(fn () => app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage()))
        ->toThrow(WhatsAppSendException::class);

    Http::fake([
        META_MEDIA_URL => Http::response(['no' => 'id'], 200),
    ]);
    configureMetaProvider();
    expect(fn () => app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage()))
        ->toThrow(WhatsAppSendException::class);
});

// ── 10. Security: the access token never leaks into exceptions or result data ──

test('access token never appears in any exception message on failure', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['error' => ['message' => 'Invalid OAuth access token', 'code' => 190]], 401),
    ]);

    configureMetaProvider(['whatsapp.meta.access_token' => 'super-secret-token-value']);

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppSendException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown->getMessage())->not->toContain('super-secret-token-value');
});

test('access token never appears in the indeterminate exception message either', function (): void {
    Http::fake([
        META_MEDIA_URL => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    configureMetaProvider(['whatsapp.meta.access_token' => 'super-secret-token-value']);

    $thrown = null;
    try {
        app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());
    } catch (WhatsAppIndeterminateSendException $e) {
        $thrown = $e;
    }

    expect($thrown)->not->toBeNull();
    expect($thrown->getMessage())->not->toContain('super-secret-token-value');
});

test('access token never appears in WhatsAppSendResult raw data on success', function (): void {
    Http::fake([
        META_MEDIA_URL => Http::response(['id' => 'MEDIA123'], 200),
        META_MESSAGES_URL => Http::response(['messages' => [['id' => 'wamid.TEST123']]], 200),
    ]);

    configureMetaProvider(['whatsapp.meta.access_token' => 'super-secret-token-value']);

    $result = app(WhatsAppManager::class)->gateway()->sendDocument(metaMakeMessage());

    expect(json_encode($result->raw))->not->toContain('super-secret-token-value');
});

// ── 11. Generic interface/DTOs remain Meta-agnostic ─────────────────────────

test('the generic contract carries no Meta-specific vocabulary', function (): void {
    $metaTerms = ['meta', 'phone_number_id', 'access_token', 'media_id', 'graph', 'template_id'];

    $symbols = [];

    $interface = new ReflectionClass(WhatsAppGatewayInterface::class);
    foreach ($interface->getMethods() as $method) {
        $symbols[] = $method->getName();
        foreach ($method->getParameters() as $param) {
            $symbols[] = $param->getName();
        }
    }

    foreach ([WhatsAppDocumentMessage::class, WhatsAppSendResult::class] as $dtoClass) {
        $dto = new ReflectionClass($dtoClass);
        foreach ($dto->getProperties() as $property) {
            $symbols[] = $property->getName();
        }
        $constructor = $dto->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $symbols[] = $param->getName();
            }
        }
    }

    foreach ($symbols as $symbol) {
        foreach ($metaTerms as $term) {
            expect(stripos($symbol, $term))->toBe(
                false,
                "Expected \"{$symbol}\" not to reference Meta-specific term \"{$term}\".",
            );
        }
    }
});

// ── 14. Zero real network calls across this whole file (structural guarantee) ──
//
// Every test above either calls Http::fake() before invoking the gateway, or
// (the config-validation tests) throws before any HTTP client call is even
// built. Http::fake() replaces the underlying HTTP handler for the whole
// test, so no request in this file can leave the process -- there is no
// separate "real network" code path to accidentally exercise.
