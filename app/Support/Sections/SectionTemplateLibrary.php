<?php

namespace App\Support\Sections;

/**
 * Section Template Library — complete section blueprints, composed from Components.
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * ARCHITECTURE LAYERS
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 *  ComponentLibrary   →  canonical reusable field groups (intro / cta / image …)
 *  SectionTemplateLibrary → full blueprints = components[] + extra_fields[] + stub
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * WHAT CHANGED (v2 — Component Architecture)
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * Before: each template defined its own `fields[]` array, duplicating eyebrow /
 *         title / subtitle / button_* / image across every template.
 *
 * After:  templates declare `components[]` (keys from ComponentLibrary) plus an
 *         optional `extra_fields[]` for template-specific additions.
 *         ComponentLibrary::resolveFields() merges them and deduplicates.
 *
 * Backward compatibility: `storeFromTemplate()` checks for `components` first,
 * falls back to `fields` if present (older templates still work unchanged).
 *
 * ═══════════════════════════════════════════════════════════════════════════════
 * ADDING A NEW TEMPLATE
 * ═══════════════════════════════════════════════════════════════════════════════
 *
 * 1. Add an entry to ALL_TEMPLATES. Key = unique slug, lowercase-dash.
 * 2. Set `components` to an ordered list of ComponentLibrary keys.
 * 3. Add `extra_fields` for anything not covered by existing components.
 * 4. Write a `blade_stub` — saved to blade_source for a quick start.
 * 5. Add t('dashboard.SectionTpl_<Key>') to DashboardTranslationsSeeder.
 * No other file needs to change.
 */
class SectionTemplateLibrary
{
    // ── Scope shortcuts ───────────────────────────────────────────
    private const T = 'translatable';
    private const S = 'shared';

    // ── Type shortcuts ────────────────────────────────────────────
    private const TEXT     = 'text';
    private const TEXTAREA = 'textarea';
    private const URL      = 'url';
    private const MEDIA    = 'media';
    private const SELECT   = 'select';
    private const REPEATER = 'repeater';

    // ─────────────────────────────────────────────────────────────

    public static function all(): array
    {
        return self::ALL_TEMPLATES;
    }

    public static function get(string $key): ?array
    {
        return self::ALL_TEMPLATES[$key] ?? null;
    }

    public static function keys(): array
    {
        return array_keys(self::ALL_TEMPLATES);
    }

    /**
     * Return the resolved flat field list for a template.
     *
     * Prefers `components + extra_fields` (v2).
     * Falls back to inline `fields` (v1 backward-compat).
     *
     * @return array<int, array>
     */
    public static function resolveTemplateFields(string $templateKey): array
    {
        $template = self::get($templateKey);

        if (! is_array($template)) {
            return [];
        }

        // v2: component-based
        if (! empty($template['components'])) {
            return ComponentLibrary::resolveFields(
                $template['components'],
                $template['extra_fields'] ?? [],
            );
        }

        // v1: inline fields (backward compat)
        return $template['fields'] ?? [];
    }

    // ─────────────────────────────────────────────────────────────
    // Template definitions (v2 — component architecture)
    // ─────────────────────────────────────────────────────────────

    private const ALL_TEMPLATES = [

        // ══════════════════════════════════════════════════════════
        // 1. Hero Section
        //    Components: intro + cta + image
        // ══════════════════════════════════════════════════════════
        'hero' => [
            'label'       => 'Hero Section',
            'icon'        => 'ti-layout-navbar',
            'color'       => 'indigo',
            'category'    => 'hero',
            'description' => 'Full-width hero with headline, subtitle, CTA button and image.',

            'components'   => ['intro', 'cta', 'image'],
            'extra_fields' => [],

            'definition' => [
                'label'       => 'Hero Section',
                'section_key' => 'hero',
                'category'    => 'hero',
                'description' => 'Full-width hero section with headline, subtitle, CTA, and image.',
                'is_active'   => true,
                'is_visible'  => true,
                'sort_order'  => 0,
            ],

            'blade_stub' => <<<'BLADE'
@php
    $eyebrow     = $data['eyebrow']       ?? null;
    $title       = $data['title']         ?? '';
    $subtitle    = $data['subtitle']      ?? null;
    $buttonLabel = $data['button_label']  ?? null;
    $buttonUrl   = $data['button_url']    ?? null;
    $buttonTarget= $data['button_target'] ?? '_self';
    $image       = $data['image']         ?? null;
    $imageAlt    = $data['image_alt']     ?? '';
@endphp

<section class="section-hero">
    <div class="container">
        <div class="hero-content">
            @if($eyebrow)
                <p class="hero-eyebrow">{{ $eyebrow }}</p>
            @endif

            <h1 class="hero-title">{{ $title }}</h1>

            @if($subtitle)
                <p class="hero-subtitle">{{ $subtitle }}</p>
            @endif

            @if($buttonLabel && $buttonUrl)
                <a href="{{ $buttonUrl }}" target="{{ $buttonTarget }}" class="btn btn-primary">
                    {{ $buttonLabel }}
                </a>
            @endif
        </div>

        @if($image)
            <div class="hero-image">
                <img src="{{ asset('storage/' . $image) }}" alt="{{ $imageAlt }}">
            </div>
        @endif
    </div>
</section>
BLADE,
        ],

        // ══════════════════════════════════════════════════════════
        // 2. Features Grid
        //    Components: intro + features
        // ══════════════════════════════════════════════════════════
        'features-grid' => [
            'label'       => 'Features Grid',
            'icon'        => 'ti-layout-grid',
            'color'       => 'violet',
            'category'    => 'features',
            'description' => 'Grid of icon-based feature cards with title and description.',

            'components'   => ['intro', 'features'],
            'extra_fields' => [],

            'definition' => [
                'label'       => 'Features Grid',
                'section_key' => 'features_grid',
                'category'    => 'features',
                'description' => 'Icon-based feature cards in a responsive grid layout.',
                'is_active'   => true,
                'is_visible'  => true,
                'sort_order'  => 0,
            ],

            'blade_stub' => <<<'BLADE'
@php
    $title    = $data['title']    ?? '';
    $subtitle = $data['subtitle'] ?? null;
    $features = is_array($data['features'] ?? null) ? $data['features'] : [];
@endphp

<section class="section-features-grid">
    <div class="container">
        <div class="section-header text-center">
            <h2>{{ $title }}</h2>
            @if($subtitle) <p>{{ $subtitle }}</p> @endif
        </div>

        <div class="features-grid">
            @foreach($features as $feature)
                <div class="feature-card">
                    @if(!empty($feature['icon_source']) && $feature['icon_source'] === 'media' && !empty($feature['icon_media']))
                        <img src="{{ asset('storage/' . $feature['icon_media']) }}" alt="{{ $feature['title'] ?? '' }}" class="feature-icon">
                    @elseif(!empty($feature['icon']))
                        <i class="{{ $feature['icon'] }} feature-icon"></i>
                    @endif

                    <h3>{{ $feature['title'] ?? '' }}</h3>
                    @if(!empty($feature['description']))
                        <p>{{ $feature['description'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
BLADE,
        ],

        // ══════════════════════════════════════════════════════════
        // 3. Content Showcase
        //    Components: intro + features + highlight + cta + image
        // ══════════════════════════════════════════════════════════
        'content-showcase' => [
            'label'       => 'Content Showcase',
            'icon'        => 'ti-layout-sidebar',
            'color'       => 'emerald',
            'category'    => 'content',
            'description' => 'Two-column layout: text content on one side, image on the other.',

            'components'   => ['intro', 'features', 'highlight', 'cta', 'image'],
            'extra_fields' => [],

            'definition' => [
                'label'       => 'Content Showcase',
                'section_key' => 'content_showcase',
                'category'    => 'content',
                'description' => 'Two-column section with rich text content and a supporting image.',
                'is_active'   => true,
                'is_visible'  => true,
                'sort_order'  => 0,
            ],

            'blade_stub' => <<<'BLADE'
@php
    $eyebrow       = $data['eyebrow']        ?? null;
    $title         = $data['title']          ?? '';
    $subtitle      = $data['subtitle']       ?? null;
    $highlightText = $data['highlight_text'] ?? null;
    $buttonLabel   = $data['button_label']   ?? null;
    $buttonUrl     = $data['button_url']     ?? null;
    $image         = $data['image']          ?? null;
    $imageAlt      = $data['image_alt']      ?? '';
    $imagePosition = $data['image_position'] ?? 'right';
    $features      = is_array($data['features'] ?? null) ? $data['features'] : [];
@endphp

<section class="section-content-showcase image-{{ $imagePosition }}">
    <div class="container">
        <div class="content-col">
            @if($eyebrow) <p class="eyebrow">{{ $eyebrow }}</p> @endif
            @if($highlightText) <span class="highlight-badge">{{ $highlightText }}</span> @endif
            <h2>{{ $title }}</h2>
            @if($subtitle) <p class="subtitle">{{ $subtitle }}</p> @endif

            @if($features)
                <ul class="feature-list">
                    @foreach($features as $feature)
                        <li>
                            @if(!empty($feature['icon'])) <i class="{{ $feature['icon'] }}"></i> @endif
                            {{ $feature['title'] ?? '' }}
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($buttonLabel && $buttonUrl)
                <a href="{{ $buttonUrl }}" class="btn btn-primary">{{ $buttonLabel }}</a>
            @endif
        </div>

        @if($image)
            <div class="image-col">
                <img src="{{ asset('storage/' . $image) }}" alt="{{ $imageAlt }}">
            </div>
        @endif
    </div>
</section>
BLADE,
        ],

        // ══════════════════════════════════════════════════════════
        // 4. CTA Banner
        //    Components: intro + cta
        //    Extra fields: background_image (template-specific)
        // ══════════════════════════════════════════════════════════
        'cta-banner' => [
            'label'       => 'CTA Banner',
            'icon'        => 'ti-speakerphone',
            'color'       => 'rose',
            'category'    => 'cta',
            'description' => 'Full-width call-to-action banner with title, subtitle, and button.',

            'components' => ['intro', 'cta'],
            'extra_fields' => [
                // background_image — shared: visual asset, same for all locales
                ['field_key' => 'background_image', 'label' => 'Background Image', 'field_type' => self::MEDIA, 'field_scope' => self::S, 'is_required' => false],
            ],

            'definition' => [
                'label'       => 'CTA Banner',
                'section_key' => 'cta_banner',
                'category'    => 'cta',
                'description' => 'Full-width CTA section with headline, subtitle, and action button.',
                'is_active'   => true,
                'is_visible'  => true,
                'sort_order'  => 0,
            ],

            'blade_stub' => <<<'BLADE'
@php
    $title           = $data['title']            ?? '';
    $subtitle        = $data['subtitle']         ?? null;
    $buttonLabel     = $data['button_label']     ?? null;
    $buttonUrl       = $data['button_url']       ?? null;
    $buttonTarget    = $data['button_target']    ?? '_self';
    $backgroundImage = $data['background_image'] ?? null;
@endphp

<section class="section-cta-banner"
    @if($backgroundImage)
        style="background-image: url('{{ asset('storage/' . $backgroundImage) }}');"
    @endif
>
    <div class="container text-center">
        <h2>{{ $title }}</h2>
        @if($subtitle) <p>{{ $subtitle }}</p> @endif

        @if($buttonLabel && $buttonUrl)
            <a href="{{ $buttonUrl }}" target="{{ $buttonTarget }}" class="btn btn-primary btn-lg">
                {{ $buttonLabel }}
            </a>
        @endif
    </div>
</section>
BLADE,
        ],

        // ══════════════════════════════════════════════════════════
        // 5. FAQ Accordion
        //    Components: intro + faq
        // ══════════════════════════════════════════════════════════
        'faq' => [
            'label'       => 'FAQ Accordion',
            'icon'        => 'ti-help',
            'color'       => 'amber',
            'category'    => 'faq',
            'description' => 'Accordion-style FAQ section with collapsible question/answer pairs.',

            'components'   => ['intro', 'faq'],
            'extra_fields' => [],

            'definition' => [
                'label'       => 'FAQ Accordion',
                'section_key' => 'faq',
                'category'    => 'faq',
                'description' => 'Frequently asked questions in an accessible accordion layout.',
                'is_active'   => true,
                'is_visible'  => true,
                'sort_order'  => 0,
            ],

            'blade_stub' => <<<'BLADE'
@php
    $title    = $data['title']    ?? '';
    $subtitle = $data['subtitle'] ?? null;
    $faqs     = is_array($data['faqs'] ?? null) ? $data['faqs'] : [];
@endphp

<section class="section-faq">
    <div class="container">
        <div class="section-header text-center">
            <h2>{{ $title }}</h2>
            @if($subtitle) <p>{{ $subtitle }}</p> @endif
        </div>

        <div class="faq-accordion" id="faq-accordion">
            @foreach($faqs as $index => $faq)
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-{{ $index }}">
                        {{ $faq['question'] ?? '' }}
                        <span class="faq-icon"></span>
                    </button>
                    <div class="faq-answer" id="faq-answer-{{ $index }}" hidden>
                        <p>{{ $faq['answer'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
BLADE,
        ],

        // ══════════════════════════════════════════════════════════
        // 6. Testimonials
        //    Components: intro + testimonials
        // ══════════════════════════════════════════════════════════
        'testimonials' => [
            'label'       => 'Testimonials',
            'icon'        => 'ti-quote',
            'color'       => 'cyan',
            'category'    => 'social-proof',
            'description' => 'Customer testimonials with avatar, name, position, and quote.',

            'components'   => ['intro', 'testimonials'],
            'extra_fields' => [],

            'definition' => [
                'label'       => 'Testimonials',
                'section_key' => 'testimonials',
                'category'    => 'social-proof',
                'description' => 'Customer reviews and testimonials with avatars and quotes.',
                'is_active'   => true,
                'is_visible'  => true,
                'sort_order'  => 0,
            ],

            'blade_stub' => <<<'BLADE'
@php
    $title        = $data['title']        ?? '';
    $subtitle     = $data['subtitle']     ?? null;
    $testimonials = is_array($data['testimonials'] ?? null) ? $data['testimonials'] : [];
@endphp

<section class="section-testimonials">
    <div class="container">
        <div class="section-header text-center">
            <h2>{{ $title }}</h2>
            @if($subtitle) <p>{{ $subtitle }}</p> @endif
        </div>

        <div class="testimonials-grid">
            @foreach($testimonials as $testimonial)
                <div class="testimonial-card">
                    <blockquote>{{ $testimonial['quote'] ?? '' }}</blockquote>
                    <div class="testimonial-author">
                        @if(!empty($testimonial['avatar']))
                            <img src="{{ asset('storage/' . $testimonial['avatar']) }}"
                                 alt="{{ $testimonial['name'] ?? '' }}"
                                 class="testimonial-avatar">
                        @endif
                        <div>
                            <strong>{{ $testimonial['name'] ?? '' }}</strong>
                            @if(!empty($testimonial['position']) || !empty($testimonial['company']))
                                <span>{{ implode(', ', array_filter([$testimonial['position'] ?? '', $testimonial['company'] ?? ''])) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
BLADE,
        ],

    ];
}
