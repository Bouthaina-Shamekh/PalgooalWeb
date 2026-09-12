<?php

namespace App\WhatsApp\DTOs;

use App\WhatsApp\Exceptions\WhatsAppException;
use App\WhatsApp\Support\WhatsAppPhoneNormalizer;

/**
 * A provider-agnostic request to send one document (e.g. an invoice PDF) to
 * one WhatsApp recipient.
 *
 * Deliberately has no Meta-specific concepts (no phone_number_id, media_id,
 * access_token, template ID, Graph API shape, ...) -- those belong inside a
 * future concrete Meta adapter, translated from this DTO, never leaking
 * into the WhatsAppGatewayInterface contract itself.
 *
 * THE single phone-normalization boundary: the constructor is the only
 * place in the whole WhatsApp transport layer that calls
 * WhatsAppPhoneNormalizer::normalize(). By the time a WhatsAppDocumentMessage
 * exists, `recipient` is guaranteed already normalized (digits-only
 * international format, e.g. "970599123456"). No gateway, the manager, or
 * any other layer should ever call the normalizer again on
 * $message->recipient -- doing so would be redundant at best and, per
 * WhatsAppPhoneNormalizer's own documented idempotency guarantee, a no-op
 * at worst, so callers should treat re-normalizing here as a code smell.
 * Callers pass the RAW phone value (whatever shape Client::phone happens to
 * hold) into this constructor and let it fail closed via
 * InvalidWhatsAppPhoneException, rather than normalizing upstream
 * themselves.
 */
class WhatsAppDocumentMessage
{
    public readonly string $recipient;

    /**
     * @param  string       $recipient  RAW phone value, normalized internally (see class docblock).
     * @param  string       $filename   Non-empty display filename, e.g. "invoice-INV-2026-0001.pdf".
     * @param  string       $mimeType   Non-empty MIME type, e.g. "application/pdf".
     * @param  string       $contents   Non-empty raw binary document bytes.
     * @param  string|null  $caption    Optional short message text sent alongside the document.
     *
     * @throws \App\WhatsApp\Exceptions\InvalidWhatsAppPhoneException when $recipient cannot be
     *         confidently normalized (see WhatsAppPhoneNormalizer's fail-closed policy).
     * @throws WhatsAppException when $filename, $mimeType, or $contents is empty/malformed.
     */
    public function __construct(
        string $recipient,
        public readonly string $filename,
        public readonly string $mimeType,
        public readonly string $contents,
        public readonly ?string $caption = null,
    ) {
        // Fails closed via InvalidWhatsAppPhoneException on anything it
        // cannot confidently normalize -- intentionally left unwrapped so
        // callers can catch that specific, already-established exception
        // type rather than a generic WhatsAppException.
        $this->recipient = WhatsAppPhoneNormalizer::normalize($recipient);

        if (trim($this->filename) === '') {
            throw new WhatsAppException('WhatsApp document message filename cannot be empty.');
        }

        if (trim($this->mimeType) === '' || !str_contains($this->mimeType, '/')) {
            throw new WhatsAppException(
                'WhatsApp document message mime type is missing or malformed (expected "type/subtype", e.g. "application/pdf").',
            );
        }

        if ($this->contents === '') {
            throw new WhatsAppException('WhatsApp document message contents cannot be empty.');
        }

        if ($this->caption !== null && trim($this->caption) === '') {
            throw new WhatsAppException('WhatsApp document message caption, if provided, cannot be blank.');
        }
    }
}
