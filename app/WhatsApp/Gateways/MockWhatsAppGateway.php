<?php

namespace App\WhatsApp\Gateways;

use App\WhatsApp\Contracts\WhatsAppGatewayInterface;
use App\WhatsApp\DTOs\WhatsAppDocumentMessage;
use App\WhatsApp\DTOs\WhatsAppSendResult;

/**
 * Mock WhatsApp provider for local development and automated tests.
 *
 * Unlike App\Payments\Gateways\MockGateway (which throws for every method
 * beyond name() -- it is a legacy settlement-name provider only, and must
 * never fake a successful payment), this mock DOES fake a successful send.
 * That is intentional and safe here for a different reason than it would be
 * unsafe there: sending a WhatsApp document has no financial/state side
 * effects to falsely represent, no network I/O ever happens, nothing is
 * persisted, and -- critically -- WhatsAppManager never resolves this class
 * as a silent fallback (see WhatsAppManager's docblock): it is only ever
 * returned when a caller has explicitly configured WHATSAPP_PROVIDER=mock.
 * A production environment that has configured nothing gets
 * WhatsAppConfigurationException, not a silently-successful fake send.
 *
 * Behaviour:
 *  - name()          → 'mock_whatsapp'
 *  - sendDocument()   → never performs network I/O or persists anything;
 *                        returns a deterministic fake success result so
 *                        tests can assert on a stable provider message ID
 *                        for the same input.
 */
class MockWhatsAppGateway implements WhatsAppGatewayInterface
{
    /** The canonical provider identifier. See WhatsAppGatewayInterface::name(). */
    public const GATEWAY_NAME = 'mock_whatsapp';

    public function name(): string
    {
        return self::GATEWAY_NAME;
    }

    /**
     * Fakes a successful send. Deterministic: the same message (same
     * recipient/filename/contents/caption) always produces the same
     * providerMessageId, so tests can assert on it directly instead of only
     * asserting "some non-null string came back".
     */
    public function sendDocument(WhatsAppDocumentMessage $message): WhatsAppSendResult
    {
        $fingerprint = hash('sha256', implode('|', [
            $message->recipient,
            $message->filename,
            $message->mimeType,
            (string) strlen($message->contents),
            (string) $message->caption,
        ]));

        return new WhatsAppSendResult(
            success: true,
            providerMessageId: 'mock-' . substr($fingerprint, 0, 24),
            providerStatus: WhatsAppSendResult::STATUS_SENT,
            raw: [
                'mock' => true,
                'recipient' => $message->recipient,
                'filename' => $message->filename,
                'mime_type' => $message->mimeType,
                'byte_length' => strlen($message->contents),
                'caption' => $message->caption,
            ],
        );
    }
}
