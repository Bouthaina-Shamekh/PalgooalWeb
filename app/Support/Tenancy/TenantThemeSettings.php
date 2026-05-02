<?php

namespace App\Support\Tenancy;

/**
 * Value object that holds all tenant theme design tokens.
 *
 * Token groups:
 *  - Colors   : primary, secondary, surface, muted, heading, body, border
 *  - Typography: font_primary, font_heading, base_font_size, weight_normal, weight_bold
 *  - Shape    : radius_sm, radius_md, radius_lg, radius_xl
 *  - Buttons  : button_radius, button_style (filled | outline | ghost)
 */
final class TenantThemeSettings
{
    // -----------------------------------------------------------------------
    // Color tokens
    // -----------------------------------------------------------------------
    public readonly string $colorPrimary;
    public readonly string $colorSecondary;
    public readonly string $colorSurface;
    public readonly string $colorMuted;
    public readonly string $colorHeading;
    public readonly string $colorBody;
    public readonly string $colorBorder;

    // -----------------------------------------------------------------------
    // Typography tokens
    // -----------------------------------------------------------------------
    public readonly string $fontPrimary;
    public readonly string $fontHeading;
    public readonly string $baseFontSize;
    public readonly string $weightNormal;
    public readonly string $weightBold;

    // -----------------------------------------------------------------------
    // Shape tokens
    // -----------------------------------------------------------------------
    public readonly string $radiusSm;
    public readonly string $radiusMd;
    public readonly string $radiusLg;
    public readonly string $radiusXl;

    // -----------------------------------------------------------------------
    // Button tokens
    // -----------------------------------------------------------------------
    public readonly string $buttonRadius;
    public readonly string $buttonStyle;   // filled | outline | ghost

    // -----------------------------------------------------------------------
    // Defaults
    // -----------------------------------------------------------------------
    private const DEFAULTS = [
        // Colors
        'color_primary'    => '#7c3aed',   // violet-600
        'color_secondary'  => '#e11d48',   // rose-600
        'color_surface'    => '#ffffff',
        'color_muted'      => '#f8fafc',   // slate-50
        'color_heading'    => '#0f172a',   // slate-900
        'color_body'       => '#475569',   // slate-600
        'color_border'     => '#e2e8f0',   // slate-200

        // Typography
        'font_primary'     => 'Inter, sans-serif',
        'font_heading'     => 'Inter, sans-serif',
        'base_font_size'   => '16px',
        'weight_normal'    => '400',
        'weight_bold'      => '700',

        // Shape
        'radius_sm'        => '0.25rem',   // 4 px
        'radius_md'        => '0.5rem',    // 8 px
        'radius_lg'        => '0.75rem',   // 12 px
        'radius_xl'        => '1rem',      // 16 px

        // Buttons
        'button_radius'    => '0.5rem',
        'button_style'     => 'filled',
    ];

    private function __construct(array $data)
    {
        $d = array_merge(self::DEFAULTS, $data);

        $this->colorPrimary   = $this->sanitizeHex($d['color_primary'],   self::DEFAULTS['color_primary']);
        $this->colorSecondary = $this->sanitizeHex($d['color_secondary'],  self::DEFAULTS['color_secondary']);
        $this->colorSurface   = $this->sanitizeHex($d['color_surface'],    self::DEFAULTS['color_surface']);
        $this->colorMuted     = $this->sanitizeHex($d['color_muted'],      self::DEFAULTS['color_muted']);
        $this->colorHeading   = $this->sanitizeHex($d['color_heading'],    self::DEFAULTS['color_heading']);
        $this->colorBody      = $this->sanitizeHex($d['color_body'],       self::DEFAULTS['color_body']);
        $this->colorBorder    = $this->sanitizeHex($d['color_border'],     self::DEFAULTS['color_border']);

        $this->fontPrimary  = $this->sanitizeFont($d['font_primary'],  self::DEFAULTS['font_primary']);
        $this->fontHeading  = $this->sanitizeFont($d['font_heading'],  self::DEFAULTS['font_heading']);
        $this->baseFontSize = $this->sanitizeCssSize($d['base_font_size'], self::DEFAULTS['base_font_size']);
        $this->weightNormal = $this->sanitizeFontWeight($d['weight_normal'], self::DEFAULTS['weight_normal']);
        $this->weightBold   = $this->sanitizeFontWeight($d['weight_bold'],   self::DEFAULTS['weight_bold']);

        $this->radiusSm = $this->sanitizeCssSize($d['radius_sm'], self::DEFAULTS['radius_sm']);
        $this->radiusMd = $this->sanitizeCssSize($d['radius_md'], self::DEFAULTS['radius_md']);
        $this->radiusLg = $this->sanitizeCssSize($d['radius_lg'], self::DEFAULTS['radius_lg']);
        $this->radiusXl = $this->sanitizeCssSize($d['radius_xl'], self::DEFAULTS['radius_xl']);

        $this->buttonRadius = $this->sanitizeCssSize($d['button_radius'], self::DEFAULTS['button_radius']);
        $this->buttonStyle  = in_array($d['button_style'], ['filled', 'outline', 'ghost'], true)
            ? $d['button_style']
            : self::DEFAULTS['button_style'];
    }

    // -----------------------------------------------------------------------
    // Factory
    // -----------------------------------------------------------------------

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function defaults(): self
    {
        return new self([]);
    }

    // -----------------------------------------------------------------------
    // Serialisation
    // -----------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'color_primary'   => $this->colorPrimary,
            'color_secondary' => $this->colorSecondary,
            'color_surface'   => $this->colorSurface,
            'color_muted'     => $this->colorMuted,
            'color_heading'   => $this->colorHeading,
            'color_body'      => $this->colorBody,
            'color_border'    => $this->colorBorder,

            'font_primary'    => $this->fontPrimary,
            'font_heading'    => $this->fontHeading,
            'base_font_size'  => $this->baseFontSize,
            'weight_normal'   => $this->weightNormal,
            'weight_bold'     => $this->weightBold,

            'radius_sm'       => $this->radiusSm,
            'radius_md'       => $this->radiusMd,
            'radius_lg'       => $this->radiusLg,
            'radius_xl'       => $this->radiusXl,

            'button_radius'   => $this->buttonRadius,
            'button_style'    => $this->buttonStyle,
        ];
    }

    public static function defaultKeys(): array
    {
        return array_keys(self::DEFAULTS);
    }

    // -----------------------------------------------------------------------
    // Sanitisers (keep CSS injection out)
    // -----------------------------------------------------------------------

    private function sanitizeHex(mixed $value, string $fallback): string
    {
        $v = trim((string) $value);
        // Allow 3, 4, 6, or 8 digit hex with leading #
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $v)) {
            return strtolower($v);
        }
        return $fallback;
    }

    private function sanitizeFont(mixed $value, string $fallback): string
    {
        $v = trim((string) $value);
        // Allow letters, digits, spaces, commas, hyphens, apostrophes, and quotes
        if ($v !== '' && preg_match('/^[a-zA-Z0-9 ,\-\'\"]+$/', $v)) {
            return $v;
        }
        return $fallback;
    }

    private function sanitizeCssSize(mixed $value, string $fallback): string
    {
        $v = trim((string) $value);
        // Allow numbers with px, rem, em, % units, or 0
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%)$/', $v) || $v === '0') {
            return $v;
        }
        return $fallback;
    }

    private function sanitizeFontWeight(mixed $value, string $fallback): string
    {
        $v = trim((string) $value);
        $named = ['thin', 'extralight', 'light', 'normal', 'medium', 'semibold', 'bold', 'extrabold', 'black'];
        if (in_array(strtolower($v), $named, true)) {
            return strtolower($v);
        }
        if (preg_match('/^[1-9]00$/', $v)) {
            return $v;
        }
        return $fallback;
    }
}
