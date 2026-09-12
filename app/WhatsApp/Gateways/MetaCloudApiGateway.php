<?php

namespace App\WhatsApp\Gateways;

use App\WhatsApp\Contracts\WhatsAppGatewayInterface;
use App\WhatsApp\DTOs\WhatsAppDocumentMessage;
use App\WhatsApp\DTOs\WhatsAppSendResult;
use App\WhatsApp\Exceptions\WhatsAppConfigurationException;
use App\WhatsApp\Exceptions\WhatsAppConfirmedSendFailureException;
use App\WhatsApp\Exceptions\WhatsAppIndeterminateSendException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Meta WhatsApp Cloud API gateway implementation.
 *
 * Standard two-step Cloud API document-send flow:
 *   1. POST /{phone-number-id}/media    (multipart)  -> returns a media id.
 *   2. POST /{phone-number-id}/messages (JSON)        -> sends the document
 *      message referencing that media id, returns the WhatsApp message id.
 *
 * All Meta-specific concepts (access token, phone number ID, Graph API
 * version/URL shape, media id, Meta's {"error": {...}} payload shape) live
 * entirely inside this class and config('whatsapp.meta.*'). Nothing Meta-
 * specific appears in WhatsAppGatewayInterface, WhatsAppDocumentMessage, or
 * WhatsAppSendResult -- confirmed by reading all three: none of them
 * reference Meta at all.
 *
 * Configuration (config/whatsapp.php -> 'meta'):
 *   - access_token    (WHATSAPP_META_ACCESS_TOKEN)
 *   - phone_number_id (WHATSAPP_META_PHONE_NUMBER_ID)
 *   - graph_version   (WHATSAPP_META_GRAPH_VERSION) -- deliberately has NO
 *     guessed default. This codebase was inspected (grep across app/,
 *     config/, docs/) and does not pin a Meta Graph API version anywhere
 *     else, so hardcoding one here would be an invented fact, not a reused
 *     one. All three keys are validated BEFORE any HTTP call is made (see
 *     resolveConfig()) and throw WhatsAppConfigurationException if missing
 *     -- consistent with WhatsAppManager never silently falling back to a
 *     working-looking default (see WhatsAppManager's docblock).
 *
 * Retry policy: deliberately does NOT use Http::retry(). Mirrors the
 * documented reasoning in App\Services\Domains\Clients\EnomClient: neither
 * the media upload nor the message send is idempotent (a retried media
 * upload could create a duplicate uploaded file; a retried message send
 * could deliver the same document to the customer twice), so a failure
 * must surface as a clear, correctly-classified exception rather than
 * being silently retried.
 *
 * Failure classification (Phase 4 hardening): every failure this class can
 * throw is one of exactly three types, chosen so a future orchestration
 * service can decide what is safe to do next by catching a type, never by
 * parsing a message string:
 *   - WhatsAppConfigurationException  -- config missing; no HTTP call was
 *     ever made (see resolveConfig()).
 *   - WhatsAppConfirmedSendFailureException -- Meta returned a non-2xx
 *     response. Positive proof nothing was accepted; safe for a fresh
 *     attempt later. Carries providerErrorCode() when Meta's response
 *     included a parseable error.code/error.error_subcode (see
 *     parseError()).
 *   - WhatsAppIndeterminateSendException -- a connection failure, or a 2xx
 *     response missing the id we need to confirm acceptance. The request
 *     may have reached Meta; this must NEVER be automatically retried.
 *
 * Logging: deliberately does not log anything (unlike LahzaGateway, which
 * logs to a dedicated channel). This keeps the surface area for accidental
 * secret leakage at zero rather than relying on remembering to scrub every
 * log call -- callers get full failure context from the thrown exception's
 * message/providerErrorCode() and, on success, from WhatsAppSendResult::raw.
 *
 * Security: the access token is passed only via Http::withToken() (an
 * Authorization header) and is never interpolated into any exception
 * message, providerErrorCode(), WhatsAppSendResult::raw entry, or log call
 * (there are none). See parseError() -- it extracts only Meta's own
 * error.message/error.code/error.error_subcode fields, never headers,
 * never request context, never the raw response body verbatim.
 */
class MetaCloudApiGateway implements WhatsAppGatewayInterface
{
    /** The canonical provider identifier. See WhatsAppGatewayInterface::name(). */
    public const GATEWAY_NAME = 'meta';

    /**
     * HTTP request timeout in seconds. Matches the precedent set by
     * App\Payments\Gateways\LahzaGateway::TIMEOUT_SECONDS for a real
     * third-party API call over the Laravel Http client.
     */
    private const TIMEOUT_SECONDS = 30;

    public function name(): string
    {
        return self::GATEWAY_NAME;
    }

    /**
     * @throws WhatsAppConfigurationException when access_token, phone_number_id,
     *         or graph_version is missing -- checked before any HTTP call.
     * @throws WhatsAppConfirmedSendFailureException when Meta returns a non-2xx
     *         response for the media upload or message send.
     * @throws WhatsAppIndeterminateSendException when a connection failure occurs,
     *         or Meta's response is missing the expected media/message id.
     */
    public function sendDocument(WhatsAppDocumentMessage $message): WhatsAppSendResult
    {
        ['access_token' => $accessToken, 'phone_number_id' => $phoneNumberId, 'graph_version' => $graphVersion]
            = $this->resolveConfig();

        $baseUrl = "https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}";

        $mediaId = $this->uploadMedia($baseUrl, $accessToken, $message);

        return $this->sendMessage($baseUrl, $accessToken, $message, $mediaId);
    }

    /**
     * Read and validate Meta configuration. Every key is required -- none of
     * them silently default to a guessed value.
     *
     * @return array{access_token: string, phone_number_id: string, graph_version: string}
     * @throws WhatsAppConfigurationException
     */
    private function resolveConfig(): array
    {
        $accessToken = config('whatsapp.meta.access_token');
        $phoneNumberId = config('whatsapp.meta.phone_number_id');
        $graphVersion = config('whatsapp.meta.graph_version');

        if (blank($accessToken)) {
            throw new WhatsAppConfigurationException(
                'MetaCloudApiGateway: access token is not configured. Set WHATSAPP_META_ACCESS_TOKEN in .env.',
            );
        }

        if (blank($phoneNumberId)) {
            throw new WhatsAppConfigurationException(
                'MetaCloudApiGateway: phone number ID is not configured. Set WHATSAPP_META_PHONE_NUMBER_ID in .env.',
            );
        }

        if (blank($graphVersion)) {
            throw new WhatsAppConfigurationException(
                'MetaCloudApiGateway: Graph API version is not configured. Set WHATSAPP_META_GRAPH_VERSION in .env ' .
                '(e.g. the version shown in your Meta app dashboard). No version is guessed or defaulted.',
            );
        }

        return [
            'access_token' => (string) $accessToken,
            'phone_number_id' => (string) $phoneNumberId,
            'graph_version' => (string) $graphVersion,
        ];
    }

    /**
     * Step 1: upload the document bytes to Meta and return the resulting media id.
     *
     * @throws WhatsAppConfirmedSendFailureException
     * @throws WhatsAppIndeterminateSendException
     */
    private function uploadMedia(string $baseUrl, string $accessToken, WhatsAppDocumentMessage $message): string
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->attach('file', $message->contents, $message->filename, [
                    'Content-Type' => $message->mimeType,
                ])
                ->post($baseUrl . '/media', [
                    'messaging_product' => 'whatsapp',
                ]);
        } catch (ConnectionException $e) {
            // Indeterminate: the request may have reached Meta before the
            // connection failed -- never classify this as confirmed.
            throw new WhatsAppIndeterminateSendException(
                'MetaCloudApiGateway: connection to the Meta media upload endpoint failed: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        if ($response->failed()) {
            // Confirmed: Meta returned a definite HTTP-level rejection.
            $error = $this->parseError($response);
            throw new WhatsAppConfirmedSendFailureException(
                'MetaCloudApiGateway: media upload returned HTTP ' . $response->status() . '. ' . $error['message'],
                $error['code'],
            );
        }

        $body = $response->json();
        $mediaId = is_array($body) ? ($body['id'] ?? null) : null;

        if (!is_string($mediaId) || $mediaId === '') {
            // Indeterminate: HTTP succeeded but the response didn't confirm
            // acceptance the way we expect -- we cannot safely tell whether
            // Meta created something on its side.
            throw new WhatsAppIndeterminateSendException(
                'MetaCloudApiGateway: media upload succeeded (HTTP ' . $response->status() . ') but the response did not include a media id.',
            );
        }

        return $mediaId;
    }

    /**
     * Step 2: send the document message referencing the uploaded media id.
     *
     * @throws WhatsAppConfirmedSendFailureException
     * @throws WhatsAppIndeterminateSendException
     */
    private function sendMessage(string $baseUrl, string $accessToken, WhatsAppDocumentMessage $message, string $mediaId): WhatsAppSendResult
    {
        $document = [
            'id' => $mediaId,
            'filename' => $message->filename,
        ];

        if ($message->caption !== null) {
            $document['caption'] = $message->caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $message->recipient,
            'type' => 'document',
            'document' => $document,
        ];

        try {
            $response = Http::withToken($accessToken)
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->post($baseUrl . '/messages', $payload);
        } catch (ConnectionException $e) {
            // Indeterminate: the request may have reached Meta before the
            // connection failed -- never classify this as confirmed.
            throw new WhatsAppIndeterminateSendException(
                'MetaCloudApiGateway: connection to the Meta messages endpoint failed: ' . $e->getMessage(),
                0,
                $e,
            );
        }

        if ($response->failed()) {
            // Confirmed: Meta returned a definite HTTP-level rejection.
            $error = $this->parseError($response);
            throw new WhatsAppConfirmedSendFailureException(
                'MetaCloudApiGateway: message send returned HTTP ' . $response->status() . '. ' . $error['message'],
                $error['code'],
            );
        }

        $body = $response->json();
        $providerMessageId = is_array($body) ? ($body['messages'][0]['id'] ?? null) : null;

        if (!is_string($providerMessageId) || $providerMessageId === '') {
            // Indeterminate: HTTP succeeded but we cannot confirm Meta
            // actually created/queued a message from this response shape.
            throw new WhatsAppIndeterminateSendException(
                'MetaCloudApiGateway: message send succeeded (HTTP ' . $response->status() . ') but the response did not include a WhatsApp message id.',
            );
        }

        return new WhatsAppSendResult(
            success: true,
            providerMessageId: $providerMessageId,
            providerStatus: WhatsAppSendResult::STATUS_SENT,
            raw: [
                'media_id' => $mediaId,
                'response' => $body,
            ],
        );
    }

    /**
     * Parse a failed Meta response into a human-readable message and a
     * provider-neutral normalized error code, without dumping the entire
     * response body or any request context (no headers, no access token --
     * the token is never part of a response body in the first place).
     *
     * Meta's documented error shape is:
     *   {"error": {"message": "...", "type": "...", "code": ..., "error_subcode": ..., "fbtrace_id": "..."}}
     *
     * The normalized code is "{code}" when only error.code is present, or
     * "{code}:{error_subcode}" when both are present, or null when neither
     * is available. No business meaning is attached to any code value here
     * (e.g. this never decides a code means "conversation window closed")
     * -- that interpretation belongs to future orchestration/domain logic.
     *
     * @return array{message: string, code: ?string}
     */
    private function parseError(Response $response): array
    {
        $body = $response->json();
        $error = is_array($body) ? ($body['error'] ?? null) : null;

        $rawMessage = is_array($error) ? ($error['message'] ?? null) : null;
        $message = is_string($rawMessage) && $rawMessage !== ''
            ? 'Meta error: ' . $rawMessage
            : 'No further error details were returned.';

        $code = null;

        if (is_array($error) && isset($error['code']) && (is_string($error['code']) || is_int($error['code']))) {
            $code = (string) $error['code'];

            if (isset($error['error_subcode']) && (is_string($error['error_subcode']) || is_int($error['error_subcode']))) {
                $code .= ':' . $error['error_subcode'];
            }
        }

        return ['message' => $message, 'code' => $code];
    }
}
