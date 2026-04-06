<?php

namespace App\Support\Sections;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SectionEditorSchemaHelper
{
    protected array $schema;
    protected Collection $fields;
    protected Collection $groups;

    public function __construct(array $schema = [])
    {
        $this->schema = $schema;
        $this->fields = collect($schema['fields'] ?? []);
        $this->groups = collect($schema['groups'] ?? []);
    }

    public static function make(array $schema = []): self
    {
        return new self($schema);
    }

    public function field(string $name): array
    {
        return $this->fields->firstWhere('name', $name) ?? [];
    }

    public function fieldLabel(string $name, string $fallback): string
    {
        return (string) ($this->field($name)['label'] ?? $fallback);
    }

    public function fieldUi(string $name): array
    {
        return (array) ($this->field($name)['ui'] ?? []);
    }

    public function fieldDefault(string $name, mixed $fallback = null): mixed
    {
        return $this->field($name)['default'] ?? $fallback;
    }

    public function group(string $name): array
    {
        return $this->groups->firstWhere('name', $name) ?? [];
    }

    public function groupLabel(string $name, string $fallback): string
    {
        return (string) ($this->group($name)['label'] ?? $fallback);
    }

    public function hasField(string $name): bool
    {
        return $this->fields->contains(fn ($f) => Arr::get($f, 'name') === $name);
    }
}