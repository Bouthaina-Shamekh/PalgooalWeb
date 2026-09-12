<?php

namespace App\WhatsApp\Support;

use App\WhatsApp\Exceptions\InvalidWhatsAppPhoneException;

/**
 * Normalizes a phone number into the digits-only international format
 * WhatsApp-sending APIs expect (e.g. "970599123456" -- no "+", no spaces,
 * no hyphens, no parentheses, no leading "00").
 *
 * Fail-closed by design (approved Phase 4 phone policy):
 *  - No country is ever assumed. A locally-formatted number with no
 *    explicit international prefix ("+" or leading "00") is REJECTED, never
 *    completed with a guessed country code.
 *  - Any input this class cannot confidently normalize throws
 *    InvalidWhatsAppPhoneException rather than returning a partial or
 *    best-effort value.
 *
 * Idempotent by design: normalize(normalize($x)) === normalize($x) for every
 * accepted input. The canonical output (e.g. "970599123456") carries no "+"
 * or "00" marker of its own, so a bare digits-only string with no explicit
 * prefix is also accepted PROVIDED it does not start with "0" -- no real
 * E.164 country code starts with "0", so a leading "0" remains the signal
 * that distinguishes a local number (reject, per policy) from an
 * already-canonical international one (accept). This is the smallest rule
 * that keeps every locally-formatted number in the approved fail-closed list
 * (0599123456, 0501234567, 012345678, ...) rejected while letting the
 * normalizer's own output -- and any Client::phone value already stored in
 * that same canonical shape -- round-trip cleanly. See "Known limitations"
 * below for what this rule deliberately does NOT (and, without a full
 * country-calling-code dataset, cannot) distinguish.
 *
 * This is intentionally a minimal first-party boundary (no libphonenumber
 * or other package dependency): it validates *shape*, not real-world
 * per-country numbering-plan correctness.
 *
 * Known limitations (accepted trade-off, not solved here):
 *  - "No leading 0" is a heuristic, not a real country-calling-code check.
 *    A local number in a numbering plan that does NOT use a "0" trunk prefix
 *    (e.g. a bare US-style 10-digit local number like "2025550123", with no
 *    "1" country code) is indistinguishable, by shape alone, from a short
 *    canonical international number and will be ACCEPTED. Reliably closing
 *    this gap requires validating against real country calling codes (a
 *    maintained dataset of every assigned ITU-T E.164 prefix, e.g. via
 *    libphonenumber) -- explicitly out of scope for this minimal first-party
 *    boundary per current instructions. Numbers that already carry an
 *    explicit "+"/"00" prefix are unaffected by this limitation.
 *  - This still validates shape only, not that the number is real, in
 *    service, or WhatsApp-registered.
 *
 * Deliberately independent of Client/Invoice/controllers/queues/the Meta
 * API -- a pure, stateless string transform with no framework or model
 * dependency, callable from any future WhatsApp sending code path.
 */
class WhatsAppPhoneNormalizer
{
    /**
     * Digits-only length bounds for the FINAL normalized number (country
     * code + subscriber number combined, "+" and prefix already removed).
     * 15 is the ITU-T E.164 hard maximum. 8 is a conservative floor chosen
     * to reject obviously-truncated input (e.g. "00123" -> "123") without
     * claiming per-country minimum-length correctness.
     */
    private const MIN_DIGITS = 8;
    private const MAX_DIGITS = 15;

    /**
     * Characters allowed in raw input purely as visual formatting, and
     * stripped before validation: spaces, hyphens, parentheses, dots.
     * Nothing else is tolerated -- any other non-digit, non-"+" character
     * (letters included) is treated as malformed.
     */
    private const FORMATTING_CHARS = " \t\n\r\0\x0B-().";

    /**
     * Normalize a raw phone value to a WhatsApp-ready digits-only
     * international number.
     *
     * @throws InvalidWhatsAppPhoneException when the input is null, empty,
     *         malformed, or a locally-formatted number with no explicit
     *         international prefix.
     */
    public static function normalize(?string $raw): string
    {
        if ($raw === null) {
            throw new InvalidWhatsAppPhoneException('Phone number is null.');
        }

        $trimmed = trim($raw);

        if ($trimmed === '') {
            throw new InvalidWhatsAppPhoneException('Phone number is empty.');
        }

        // Reject anything containing letters or any character outside the
        // allowed set (digits, a single leading "+", and plain formatting
        // punctuation) BEFORE stripping formatting -- this must run on the
        // original string so "+abc123" is rejected for its letters rather
        // than surviving as a plausible-looking "123" after cleanup.
        if (preg_match('/[^0-9+' . preg_quote(self::FORMATTING_CHARS, '/') . ']/', $trimmed)) {
            throw new InvalidWhatsAppPhoneException('Phone number contains invalid characters.');
        }

        $hasLeadingPlus = str_starts_with($trimmed, '+');

        // Collapse formatting characters, keeping only "+" (if present, and
        // only meaningful at position 0) and digits.
        $compact = str_replace(str_split(self::FORMATTING_CHARS), '', $trimmed);

        if ($hasLeadingPlus) {
            if (substr_count($compact, '+') !== 1 || !str_starts_with($compact, '+')) {
                throw new InvalidWhatsAppPhoneException('Phone number has a misplaced "+".');
            }

            $digits = substr($compact, 1);
        } elseif (str_starts_with($compact, '00')) {
            $digits = substr($compact, 2);
        } elseif ($compact !== '' && ctype_digit($compact) && $compact[0] !== '0') {
            // Idempotency case: no "+"/"00" prefix, but this is exactly the
            // canonical digits-only shape normalize() itself returns on
            // success (and Client::phone may already be stored this way).
            // Still fail-closed for the thing that actually matters: a
            // leading "0" (real local formats, e.g. "0599123456") always
            // falls through to the rejection below instead of landing here.
            $digits = $compact;
        } else {
            // Approved policy: never guess a country code. A number with no
            // explicit "+"/"00" prefix AND a leading "0" is local by
            // definition and must fail closed here, even if the rest is
            // made up entirely of plausible-looking digits (e.g.
            // "0599123456"). Anything else that reaches this branch (empty,
            // non-digit, or otherwise malformed after cleanup) fails for the
            // same reason: it cannot be confidently read as either a
            // recognized prefix form or the canonical bare form above.
            throw new InvalidWhatsAppPhoneException(
                'Phone number has no explicit international country code (missing "+" or "00" prefix, or starts with "0"); refusing to guess one.',
            );
        }

        if ($digits === '' || !ctype_digit($digits)) {
            throw new InvalidWhatsAppPhoneException('Phone number has no digits after the country-code prefix.');
        }

        // A real E.164 country code never starts with "0". A leading zero
        // here means either a malformed prefix (e.g. stray "+0...") or a
        // local number wrongly dressed up with "00" -- reject either way
        // rather than stripping the zero and guessing what remains.
        if ($digits[0] === '0') {
            throw new InvalidWhatsAppPhoneException('Phone number is not valid after the country code (leading zero).');
        }

        $length = strlen($digits);

        if ($length < self::MIN_DIGITS) {
            throw new InvalidWhatsAppPhoneException('Phone number is too short to be a valid international number.');
        }

        if ($length > self::MAX_DIGITS) {
            throw new InvalidWhatsAppPhoneException('Phone number is too long to be a valid international number.');
        }

        return $digits;
    }
}
