<?php

namespace App\Support\AdminBrand;

/**
 * Value object holding Admin Brand color tokens.
 *
 * Token groups:
 *  - Core brand  : primary, secondary, muted, body
 *  - Custom slots: custom_1 … custom_5  (empty string = not set)
 *
 * These tokens are consumed by AdminBrandCssGenerator to produce
 *   --admin-color-primary / secondary / muted / body / custom-1…5
 * on :root, which the alias layer in app.css forwards to
 *   --color-purple-brand, --color-red-brand, --color-gray-light, --color-gray-dark.
 */
final class AdminBrandThemeSettings
{
    // ── Brand defaults ────────────────────────────────────────────────────────
    // These mirror the fallback values in resources/css/app.css @theme block
    // so that the CSS output is identical to Phase 0 (no visual change).

    public const DEFAULTS = [
        'primary'   => '#240a37',
        'secondary' => '#ba112c',
        'muted'     => '#f2f2f2',
        'body'      => '#626262',
        'custom_1'  => '',
        'custom_2'  => '',
        'custom_3'  => '',
        'custom_4'  => '',
        'custom_5'  => '',
    ];

    // ── Properties ───────────────────────────────────────────────────────────

    public readonly string $primary;
    public readonly string $secondary;
    public readonly string $muted;
    public readonly string $body;

    public readonly string $custom1;
    public readonly string $custom2;
    public readonly string $custom3;
    public readonly string $custom4;
    public readonly string $custom5;

    // ── Constructor ──────────────────────────────────────────────────────────

    private function __construct(
        string $primary,
        string $secondary,
        string $muted,
        string $body,
        string $custom1,
        string $custom2,
        string $custom3,
        string $custom4,
        string $custom5,
    ) {
        $this->primary   = $primary;
        $this->secondary = $secondary;
        $this->muted     = $muted;
        $this->body      = $body;
        $this->custom1   = $custom1;
        $this->custom2   = $custom2;
        $this->custom3   = $custom3;
        $this->custom4   = $custom4;
        $this->custom5   = $custom5;
    }

    // ── Factory ──────────────────────────────────────────────────────────────

    public static function fromArray(array $data): self
    {
        $d = self::DEFAULTS;

        return new self(
            primary:   self::sanitizeHex($data['primary']   ?? null, $d['primary']),
            secondary: self::sanitizeHex($data['secondary'] ?? null, $d['secondary']),
            muted:     self::sanitizeHex($data['muted']     ?? null, $d['muted']),
            body:      self::sanitizeHex($data['body']      ?? null, $d['body']),
            custom1:   self::sanitizeHexOrEmpty($data['custom_1'] ?? null),
            custom2:   self::sanitizeHexOrEmpty($data['custom_2'] ?? null),
            custom3:   self::sanitizeHexOrEmpty($data['custom_3'] ?? null),
            custom4:   self::sanitizeHexOrEmpty($data['custom_4'] ?? null),
            custom5:   self::sanitizeHexOrEmpty($data['custom_5'] ?? null),
        );
    }

    public static function defaults(): self
    {
        return self::fromArray([]);
    }

    // ── Serialization ────────────────────────────────────────────────────────

    public function toArray(): array
    {
        return [
            'primary'   => $this->primary,
            'secondary' => $this->secondary,
            'muted'     => $this->muted,
            'body'      => $this->body,
            'custom_1'  => $this->custom1,
            'custom_2'  => $this->custom2,
            'custom_3'  => $this->custom3,
            'custom_4'  => $this->custom4,
            'custom_5'  => $this->custom5,
        ];
    }

    // ── Getters (explicit for IDE auto-complete) ─────────────────────────────

    public function primary(): string   { return $this->primary; }
    public function secondary(): string { return $this->secondary; }
    public function muted(): string     { return $this->muted; }
    public function body(): string      { return $this->body; }
    public function custom1(): string   { return $this->custom1; }
    public function custom2(): string   { return $this->custom2; }
    public function custom3(): string   { return $this->custom3; }
    public function custom4(): string   { return $this->custom4; }
    public function custom5(): string   { return $this->custom5; }

    // ── Sanitizers ───────────────────────────────────────────────────────────

    /**
     * Validate a hex colour; return the default if invalid.
     */
    private static function sanitizeHex(?string $value, string $default): string
    {
        if ($value === null || trim($value) === '') {
            return $default;
        }

        $v = strtolower(trim($value));
        if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $v)) {
            return $v;
        }

        return $default;
    }

    /**
     * Validate a hex colour; return empty string if not set (custom slots).
     */
    private static function sanitizeHexOrEmpty(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $v = strtolower(trim($value));
        if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $v)) {
            return $v;
        }

        return '';
    }
}
