<?php

namespace App\Support;

class SeoMeta
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public array $keywords = [],
        public ?string $canonical = null,
        public ?string $robots = null,
        public ?string $image = null,
        public ?string $type = null,
        public ?string $locale = null,
        public ?string $siteName = null,
        public ?string $twitterCard = null,
        public ?string $twitterHandle = null,
        public array $alternates = [],
        public array $extraMeta = [],
        public array $extraLinks = [],
        public array $schema = [],
    ) {
        $this->keywords = self::normalizeKeywords($keywords);
        $this->alternates = self::normalizeAlternates($alternates);
        $this->extraMeta = self::normalizeMetaEntries($extraMeta);
        $this->extraLinks = self::normalizeLinkEntries($extraLinks);
    }

    public static function defaults(): self
    {
        $config = (array) config('seo');
        $locale = app()->getLocale();

        return new self(
            title: $config['default_title'] ?? config('app.name', 'Website'),
            description: $config['default_description'] ?? null,
            keywords: $config['default_keywords'] ?? [],
            canonical: null,
            robots: $config['default_robots'] ?? 'index, follow',
            image: $config['default_image'] ?? null,
            type: $config['default_type'] ?? 'website',
            locale: $locale ?: ($config['default_locale'] ?? null),
            siteName: $config['site_name'] ?? config('app.name'),
            twitterCard: data_get($config, 'twitter.card', 'summary_large_image'),
            twitterHandle: data_get($config, 'twitter.handle'),
        );
    }

    public static function make(array $attributes = []): self
    {
        $defaults = self::defaults()->toArray();
        $data = array_replace_recursive($defaults, $attributes);

        return self::fromArray($data);
    }

    public static function fromArray(array $data): self
    {
        $alternates = $data['alternates'] ?? ($data['hreflang'] ?? []);
        if (!is_array($alternates)) {
            $alternates = array_filter([$alternates]);
        }

        $extraMeta = $data['extra_meta'] ?? ($data['meta'] ?? []);
        $extraLinks = $data['extra_links'] ?? ($data['links'] ?? []);

        return new self(
            title: (string) ($data['title'] ?? config('app.name', 'Website')),
            description: $data['description'] ?? null,
            keywords: $data['keywords'] ?? [],
            canonical: $data['canonical'] ?? null,
            robots: $data['robots'] ?? null,
            image: $data['image'] ?? null,
            type: $data['type'] ?? null,
            locale: $data['locale'] ?? app()->getLocale(),
            siteName: $data['site_name'] ?? ($data['siteName'] ?? config('app.name')),
            twitterCard: $data['twitter_card'] ?? ($data['twitterCard'] ?? null),
            twitterHandle: $data['twitter_handle'] ?? ($data['twitterHandle'] ?? null),
            alternates: $alternates,
            extraMeta: $extraMeta,
            extraLinks: $extraLinks,
            schema: $data['schema'] ?? [],
        );
    }

    public function with(array $attributes): self
    {
        $current = $this->toArray();
        $data = array_replace_recursive($current, $attributes);

        return self::fromArray($data);
    }

    public function keywordsString(): ?string
    {
        return empty($this->keywords) ? null : implode(', ', $this->keywords);
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'canonical' => $this->canonical,
            'robots' => $this->robots,
            'image' => $this->image,
            'type' => $this->type,
            'locale' => $this->locale,
            'site_name' => $this->siteName,
            'twitter_card' => $this->twitterCard,
            'twitter_handle' => $this->twitterHandle,
            'alternates' => $this->alternates,
            'extra_meta' => $this->extraMeta,
            'extra_links' => $this->extraLinks,
            'schema' => $this->schema,
        ];
    }

    protected static function normalizeKeywords(array|string $keywords): array
    {
        $items = is_string($keywords) ? explode(',', $keywords) : $keywords;

        return array_values(array_filter(array_map(function ($value) {
            if (is_string($value)) {
                return trim($value);
            }

            return null;
        }, $items), static fn ($value) => !empty($value)));
    }

    protected static function normalizeAlternates(array $alternates): array
    {
        $normalized = [];

        foreach ($alternates as $alternate) {
            if (is_array($alternate)) {
                $locale = $alternate['locale'] ?? ($alternate['hreflang'] ?? null);
                $url = $alternate['url'] ?? null;
            } elseif (is_string($alternate)) {
                $locale = null;
                $url = $alternate;
            } else {
                continue;
            }

            if ($locale && $url) {
                $normalized[] = [
                    'locale' => $locale,
                    'url' => $url,
                ];
            }
        }

        return $normalized;
    }

    protected static function normalizeMetaEntries(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $name = $item['name'] ?? null;
            $property = $item['property'] ?? null;
            $content = $item['content'] ?? ($item['value'] ?? null);

            if (($name || $property) && $content !== null) {
                return array_filter([
                    'name' => $name,
                    'property' => $property,
                    'content' => $content,
                ], static fn ($value) => $value !== null && $value !== '');
            }

            return null;
        }, $items)));
    }

    protected static function normalizeLinkEntries(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (!is_array($item)) {
                return null;
            }

            $rel = $item['rel'] ?? null;
            $href = $item['href'] ?? null;
            $type = $item['type'] ?? null;

            if ($rel && $href) {
                $entry = [
                    'rel' => $rel,
                    'href' => $href,
                ];

                if ($type) {
                    $entry['type'] = $type;
                }

                return $entry;
            }

            return null;
        }, $items)));
    }
}
