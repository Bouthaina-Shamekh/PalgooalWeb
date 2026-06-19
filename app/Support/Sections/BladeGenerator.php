<?php

namespace App\Support\Sections;

use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use Illuminate\Support\Collection;

/**
 * BladeGenerator — Server-side Blade scaffold generator.
 *
 * Transforms a SectionDefinition (with its field definitions) into a
 * ready-to-edit Blade stub. The generated scaffold is intentionally
 * readable and opinionated rather than minimal, using the same runtime
 * contract as the production section renderer:
 *
 *   • $data  — flat merged array of all field values (shared + translatable)
 *   • Media fields use SectionFrontendMediaResolver::resolve()
 *   • Repeater fields iterate $data['key'] directly (no collect())
 *
 * Component Awareness
 * ───────────────────
 * Fields are grouped by component membership (intro / cta / image / …)
 * using ComponentLibrary as the single source of truth. The canonical
 * component order and field-key assignments are derived at runtime from
 * ComponentLibrary::all(), so adding a new component there is the only
 * change required — no update to this file is needed.
 *
 * Repeater fields always go to the "ungrouped" bucket regardless of which
 * component they belong to, because they require their own @foreach block.
 *
 * Adding a new field type
 * ───────────────────────
 * Add a case to renderFieldHtml(). No other file needs to change.
 */
class BladeGenerator
{

    // ── Tag per known field_key ─────────────────────────────────────────────
    // Determines which HTML element wraps the value.
    // These are presentation hints specific to BladeGenerator — they describe
    // *how* to render a field_key in an HTML context, which is outside the
    // scope of ComponentLibrary (which only defines field structure/scope).
    private const TAG_BY_KEY = [
        'eyebrow'        => 'span',
        'title'          => 'h2',
        'subtitle'       => 'p',
        'description'    => 'div',
        'highlight_text' => 'mark',
        'meta_title'     => null,   // meta tags — handled separately
        'meta_description' => null,
    ];

    // ── CSS class per known field_key ───────────────────────────────────────
    private const CLASS_BY_KEY = [
        'eyebrow'        => 'section-eyebrow',
        'title'          => 'section-title',
        'subtitle'       => 'section-subtitle',
        'description'    => 'section-desc',
        'highlight_text' => 'section-highlight',
    ];

    // ───────────────────────────────────────────────────────────────────────

    /**
     * Generate a full Blade scaffold string for the given section definition.
     *
     * @param  SectionDefinition  $definition
     * @return string
     */
    public function generate(SectionDefinition $definition): string
    {
        /** @var Collection<int, SectionDefinitionField> $fields */
        $fields = $definition->fields()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($fields->isEmpty()) {
            return $this->emptyStub($definition);
        }

        $phpBlock  = $this->buildPhpBlock($definition, $fields);
        $htmlBlock = $this->buildHtmlBlock($definition, $fields);

        return $phpBlock . "\n\n" . $htmlBlock;
    }

    /**
     * Return generation statistics for the given definition.
     *
     * @return array{fields: int, repeaters: int, components: int, component_names: string[]}
     */
    public function stats(SectionDefinition $definition): array
    {
        $fields = $definition->fields()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $repeaterCount  = $fields->where('field_type', SectionDefinitionField::FIELD_TYPE_REPEATER)->count();
        $detectedGroups = $this->detectComponentGroups($fields);

        return [
            'fields'          => $fields->count(),
            'repeaters'       => $repeaterCount,
            'components'      => count($detectedGroups),
            'component_names' => array_keys($detectedGroups),
        ];
    }

    // ── PHP block ───────────────────────────────────────────────────────────

    private function buildPhpBlock(SectionDefinition $definition, Collection $fields): string
    {
        $sectionKey = $definition->section_key ?? 'section';
        $date       = now()->toDateString();

        $lines   = [];
        $lines[] = '@php';
        $lines[] = "    // Auto-generated scaffold: {$sectionKey} — {$date}";
        $lines[] = '    // $data contains all field values (shared + translatable merged).';
        $lines[] = '';

        foreach ($fields as $field) {
            $key   = $field->field_key;
            $type  = $field->field_type;
            $scope = $field->field_scope === SectionDefinitionField::FIELD_SCOPE_SHARED ? 'shared' : 'trans';

            $lines[] = match ($type) {
                SectionDefinitionField::FIELD_TYPE_MEDIA =>
                    "    \${$key} = \\App\\Support\\Sections\\SectionFrontendMediaResolver::resolve(\$data['{$key}'] ?? null); // media / {$scope}",

                SectionDefinitionField::FIELD_TYPE_BOOLEAN =>
                    "    \${$key} = !empty(\$data['{$key}']); // boolean / {$scope}",

                SectionDefinitionField::FIELD_TYPE_REPEATER =>
                    "    \${$key} = is_array(\$data['{$key}'] ?? null) ? \$data['{$key}'] : []; // repeater",

                SectionDefinitionField::FIELD_TYPE_RICHTEXT,
                SectionDefinitionField::FIELD_TYPE_TEXTAREA =>
                    "    \${$key} = (string) (\$data['{$key}'] ?? ''); // {$type} / {$scope}",

                default =>
                    "    \${$key} = trim((string) (\$data['{$key}'] ?? '')); // {$type} / {$scope}",
            };
        }

        $lines[] = '@endphp';

        return implode("\n", $lines);
    }

    // ── HTML block ──────────────────────────────────────────────────────────

    private function buildHtmlBlock(SectionDefinition $definition, Collection $fields): string
    {
        $sectionKey = $definition->section_key ?? 'section';
        $lines      = [];

        $lines[] = "<section class=\"section-{$sectionKey}\">";
        $lines[] = '    <div class="container">';

        // Separate into component groups + ungrouped (repeaters + unknowns)
        $groupedMap = $this->detectComponentGroups($fields);
        $usedKeys   = [];

        foreach ($groupedMap as $component => $componentFields) {
            $label   = ComponentLibrary::get($component)['name'] ?? ucfirst($component);
            $lines[] = '';
            $lines[] = "        {{-- {$label} --}}";

            foreach ($componentFields as $field) {
                $rendered = $this->renderFieldHtml($field, 2);
                if ($rendered !== null) {
                    foreach (explode("\n", $rendered) as $l) {
                        $lines[] = $l;
                    }
                }
                $usedKeys[] = $field->field_key;
            }
        }

        // Ungrouped: repeaters and any field_key not in a component
        $ungrouped = $fields->filter(fn ($f) => ! in_array($f->field_key, $usedKeys, true));

        foreach ($ungrouped as $field) {
            $rendered = $this->renderFieldHtml($field, 2);
            if ($rendered !== null) {
                $lines[] = '';
                $lines[] = "        {{-- {$field->field_key} / {$field->field_type} --}}";
                foreach (explode("\n", $rendered) as $l) {
                    $lines[] = $l;
                }
            }
        }

        $lines[] = '';
        $lines[] = '    </div>';
        $lines[] = '</section>';

        return implode("\n", $lines);
    }

    /**
     * Group active fields by detected component.
     * Returns only components that have at least one matching field.
     *
     * Component membership and canonical order are derived from
     * ComponentLibrary::all() — the single source of truth.
     * Repeater fields are always excluded here (they go to ungrouped).
     *
     * @return array<string, Collection<int, SectionDefinitionField>>
     */
    private function detectComponentGroups(Collection $fields): array
    {
        // Build reverse map: field_key → component name, derived from ComponentLibrary.
        // Repeater-typed component fields are excluded — they always go to ungrouped.
        $keyToComponent = $this->buildKeyToComponentMap();

        // Group fields (non-repeaters only) by component
        $groups = [];
        foreach ($fields as $field) {
            if ($field->field_type === SectionDefinitionField::FIELD_TYPE_REPEATER) {
                continue; // repeaters always go to ungrouped
            }
            $component = $keyToComponent[$field->field_key] ?? null;
            if ($component !== null) {
                $groups[$component][] = $field;
            }
        }

        // Preserve canonical component order from ComponentLibrary
        $ordered = [];
        foreach (ComponentLibrary::keys() as $component) {
            if (isset($groups[$component])) {
                $ordered[$component] = collect($groups[$component]);
            }
        }

        return $ordered;
    }

    /**
     * Build a reverse map of field_key → component name from ComponentLibrary.
     *
     * Only non-repeater fields are included. Repeater fields belong to the
     * "ungrouped" bucket and are handled separately in buildHtmlBlock().
     *
     * This is the single integration point between BladeGenerator and
     * ComponentLibrary. Adding a new component to ComponentLibrary
     * automatically makes it detectable here — no other change needed.
     *
     * @return array<string, string>  field_key → component key
     */
    private function buildKeyToComponentMap(): array
    {
        $map = [];

        foreach (ComponentLibrary::all() as $componentKey => $component) {
            foreach ($component['fields'] ?? [] as $fieldDef) {
                $fieldKey  = (string) ($fieldDef['field_key'] ?? '');
                $fieldType = (string) ($fieldDef['field_type'] ?? '');

                // Skip empty keys and repeater fields — they go to ungrouped
                if ($fieldKey === '' || $fieldType === SectionDefinitionField::FIELD_TYPE_REPEATER) {
                    continue;
                }

                // First occurrence wins (same dedup rule as ComponentLibrary::resolveFields)
                if (! isset($map[$fieldKey])) {
                    $map[$fieldKey] = $componentKey;
                }
            }
        }

        return $map;
    }

    // ── Field renderers ─────────────────────────────────────────────────────

    /**
     * Render a single field definition as Blade HTML.
     * Returns null for fields that produce no output (e.g. hidden SEO meta).
     *
     * @param  int  $indentLevel  Number of 4-space indent levels
     */
    private function renderFieldHtml(SectionDefinitionField $field, int $indentLevel): ?string
    {
        $indent = str_repeat('    ', $indentLevel);
        $key    = $field->field_key;
        $type   = $field->field_type;

        return match ($type) {
            SectionDefinitionField::FIELD_TYPE_TEXT    => $this->renderText($key, $indent),
            SectionDefinitionField::FIELD_TYPE_TEXTAREA,
            SectionDefinitionField::FIELD_TYPE_RICHTEXT => $this->renderTextBlock($key, $type, $indent),
            SectionDefinitionField::FIELD_TYPE_URL     => $this->renderUrl($key, $indent),
            SectionDefinitionField::FIELD_TYPE_MEDIA   => $this->renderMedia($field, $indent),
            SectionDefinitionField::FIELD_TYPE_BOOLEAN => $this->renderBoolean($key, $indent),
            SectionDefinitionField::FIELD_TYPE_NUMBER  => $this->renderNumber($key, $indent),
            SectionDefinitionField::FIELD_TYPE_SELECT  => $this->renderSelect($key, $indent),
            SectionDefinitionField::FIELD_TYPE_REPEATER => $this->renderRepeater($field, $indent),
            default => "{$indent}{{-- {$key}: {$type} --}}",
        };
    }

    private function renderText(string $key, string $indent): string
    {
        // Special keys get semantic HTML
        $tag   = self::TAG_BY_KEY[$key]   ?? 'p';
        $class = self::CLASS_BY_KEY[$key] ?? $key;

        // SEO fields should not render visible HTML
        if ($tag === null) {
            return "{$indent}{{-- {$key}: SEO — render in <head> section --}}";
        }

        return implode("\n", [
            "{$indent}@if (\${$key})",
            "{$indent}    <{$tag} class=\"{$class}\">{{ \${$key} }}</{$tag}>",
            "{$indent}@endif",
        ]);
    }

    private function renderTextBlock(string $key, string $type, string $indent): string
    {
        $echo  = ($type === SectionDefinitionField::FIELD_TYPE_RICHTEXT)
            ? "{!! \${$key} !!}"
            : "{{ \${$key} }}";
        $class = self::CLASS_BY_KEY[$key] ?? $key;

        return implode("\n", [
            "{$indent}@if (\${$key})",
            "{$indent}    <div class=\"{$class}\">{$echo}</div>",
            "{$indent}@endif",
        ]);
    }

    private function renderUrl(string $key, string $indent): string
    {
        // button_url uses button_label as display text
        if ($key === 'button_url') {
            return implode("\n", [
                "{$indent}@if (\$button_url)",
                "{$indent}    <a href=\"{{ \$button_url }}\"",
                "{$indent}       target=\"{{ \$button_target ?: '_self' }}\"",
                "{$indent}       class=\"btn btn-primary\">",
                "{$indent}        {{ \$button_label }}",
                "{$indent}    </a>",
                "{$indent}@endif",
            ]);
        }

        return implode("\n", [
            "{$indent}@if (\${$key})",
            "{$indent}    <a href=\"{{ \${$key} }}\" class=\"{$key}\">{{ \${$key} }}</a>",
            "{$indent}@endif",
        ]);
    }

    private function renderMedia(SectionDefinitionField $field, string $indent): string
    {
        $key    = $field->field_key;
        // Look for a companion alt field (image → image_alt, hero_image → hero_image_alt)
        $altKey = $key . '_alt';

        return implode("\n", [
            "{$indent}@if (\${$key})",
            "{$indent}    <img src=\"{{ \${$key} }}\"",
            "{$indent}         alt=\"{{ \$data['{$altKey}'] ?? '' }}\"",
            "{$indent}         class=\"{$key}\">",
            "{$indent}@endif",
        ]);
    }

    private function renderBoolean(string $key, string $indent): string
    {
        return implode("\n", [
            "{$indent}@if (\${$key})",
            "{$indent}    {{-- {$key} is enabled --}}",
            "{$indent}@endif",
        ]);
    }

    private function renderNumber(string $key, string $indent): string
    {
        return implode("\n", [
            "{$indent}@if (\${$key})",
            "{$indent}    <span class=\"{$key}\">{{ \${$key} }}</span>",
            "{$indent}@endif",
        ]);
    }

    private function renderSelect(string $key, string $indent): string
    {
        return implode("\n", [
            "{$indent}@if (\${$key})",
            "{$indent}    <span class=\"{$key}\">{{ \${$key} }}</span>",
            "{$indent}@endif",
        ]);
    }

    private function renderRepeater(SectionDefinitionField $field, string $indent): string
    {
        $key       = $field->field_key;
        $subFields = $field->repeaterItemSchema();

        // Derive a clean singular item variable name
        // features → $feature, services → $service, items → $item
        if (strlen($key) > 2 && str_ends_with($key, 's')) {
            $itemVar = '$' . substr($key, 0, -1);
        } else {
            $itemVar = '$' . $key . 'Item';
        }

        $lines   = [];
        $lines[] = "{$indent}@if (!empty(\${$key}))";
        $lines[] = "{$indent}    <div class=\"{$key}-list\">";
        $lines[] = "{$indent}        @foreach (\${$key} as {$itemVar})";
        $lines[] = "{$indent}            <div class=\"{$key}-item\">";

        if (! empty($subFields)) {
            foreach ($subFields as $sub) {
                $sk    = $sub['key']  ?? '';
                $stype = $sub['type'] ?? 'text';
                if ($sk === '') {
                    continue;
                }
                $inner = $this->renderRepeaterSubField($sk, $stype, $itemVar, $indent . '                ');
                foreach (explode("\n", $inner) as $l) {
                    $lines[] = $l;
                }
            }
        } else {
            $lines[] = "{$indent}                {{-- sub-fields: define item_schema on the field --}}";
        }

        $lines[] = "{$indent}            </div>";
        $lines[] = "{$indent}        @endforeach";
        $lines[] = "{$indent}    </div>";
        $lines[] = "{$indent}@endif";

        return implode("\n", $lines);
    }

    /**
     * Render a single repeater sub-field snippet.
     */
    private function renderRepeaterSubField(string $key, string $type, string $itemVar, string $indent): string
    {
        $isIcon = in_array($key, ['icon', 'icon_class'], true)
            || str_ends_with($key, '_icon')
            || str_ends_with($key, '_icon_class');

        switch ($type) {
            case SectionDefinitionField::FIELD_TYPE_MEDIA:
                return implode("\n", [
                    "{$indent}@if (!empty({$itemVar}['{$key}']))",
                    "{$indent}    <img src=\"{{ {$itemVar}['{$key}'] ?? '' }}\" alt=\"\">",
                    "{$indent}@endif",
                ]);

            case SectionDefinitionField::FIELD_TYPE_BOOLEAN:
                return implode("\n", [
                    "{$indent}@if (!empty({$itemVar}['{$key}']))",
                    "{$indent}    {{-- {$key} enabled --}}",
                    "{$indent}@endif",
                ]);

            case SectionDefinitionField::FIELD_TYPE_URL:
                return implode("\n", [
                    "{$indent}@if (!empty({$itemVar}['{$key}']))",
                    "{$indent}    <a href=\"{{ {$itemVar}['{$key}'] ?? '' }}\">{{ {$itemVar}['{$key}'] ?? '' }}</a>",
                    "{$indent}@endif",
                ]);

            default:
                if ($isIcon) {
                    return implode("\n", [
                        "{$indent}@if (!empty({$itemVar}['{$key}']))",
                        "{$indent}    <i class=\"{{ {$itemVar}['{$key}'] ?? '' }}\"></i>",
                        "{$indent}@endif",
                    ]);
                }

                // text / textarea / select → simple output
                return "{$indent}<span>{{ {$itemVar}['{$key}'] ?? '' }}</span>";
        }
    }

    // ── Empty stub ──────────────────────────────────────────────────────────

    private function emptyStub(SectionDefinition $definition): string
    {
        $sectionKey = $definition->section_key ?? 'section';
        $date       = now()->toDateString();

        return implode("\n", [
            '@php',
            "    // {$sectionKey} — {$date}",
            '    // No active fields defined yet. Add fields in the field definitions panel.',
            '@endphp',
            '',
            "<section class=\"section-{$sectionKey}\">",
            '    <div class="container">',
            '        {{-- content --}}',
            '    </div>',
            '</section>',
        ]);
    }
}
