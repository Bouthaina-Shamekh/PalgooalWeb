<?php

namespace App\WhatsApp\Exceptions;

/**
 * Thrown by WhatsAppPhoneNormalizer whenever a phone value cannot be
 * confidently normalized to a WhatsApp-ready international number.
 *
 * This is a fail-closed boundary: it is thrown for null/empty input,
 * malformed characters, and -- critically -- for locally-formatted numbers
 * that carry no explicit international prefix ("+" or "00"). The normalizer
 * never guesses a country code, so a locally-formatted number always ends
 * up here rather than being silently (and possibly wrongly) completed.
 */
class InvalidWhatsAppPhoneException extends \RuntimeException {}
