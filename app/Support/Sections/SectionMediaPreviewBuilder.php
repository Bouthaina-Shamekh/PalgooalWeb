<?php

namespace App\Support\Sections;

use App\Models\Media;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SectionMediaPreviewBuilder
{
    public function build(mixed $value): array
    {
        if (is_numeric($value)) {
            $mediaItem = Media::find((int) $value);

            return $mediaItem?->url ? [$mediaItem->url] : [];
        }

        if (is_string($value) && $value !== '') {
            return [
                Str::startsWith($value, ['http://', 'https://', '//', '/', 'data:'])
                    ? $value
                    : asset($value),
            ];
        }

        return [];
    }

    public function buildMany(iterable $values): array
    {
        return Collection::make($values)
            ->flatMap(fn($value) => $this->build($value))
            ->filter()
            ->values()
            ->all();
    }
}
