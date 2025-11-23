<?php

namespace App\Services\Feeds;

use Illuminate\Support\Facades\Http;
use SimplePie;

class BlogFeedService
{
    public function getLatest($limit = 5)
    {
        $feed = new SimplePie();
        $feed->set_feed_url('https://www.palgoals.com/blog/feed/');
        $feed->enable_cache(false);
        $feed->init();

        return collect($feed->get_items())->take($limit)->map(function ($item) {
            $fallbackImage = asset('assets/tamplate/images/wordpress.webp');
            $imageFromDescription = $this->extractImage($item->get_description());
            $imageFromPage = $imageFromDescription ? null : $this->fetchOgImage($item->get_permalink());

            return [
                'title' => $item->get_title(),
                'url' => $item->get_permalink(),
                'description' => strip_tags($item->get_description()),
                'date' => $item->get_date('Y-m-d'),
                'author' => optional($item->get_author())->get_name() ?? 'Palgoals',
                'image' => $imageFromDescription ?: $imageFromPage ?: $fallbackImage,
                'categories' => $item->get_categories() ? array_map(fn($cat) => $cat->get_label(), $item->get_categories()) : [],
            ];
        });
    }

    private function extractImage($html)
    {
        if (! $html) return null;

        libxml_use_internal_errors(true); // لتجنب تحذيرات HTML
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $images = $doc->getElementsByTagName('img');
        return $images->length > 0 ? $images->item(0)->getAttribute('src') : null;
    }

    private function fetchOgImage(string $url): ?string
    {
        try {
            $response = Http::timeout(8)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $html = $response->body();
            if (! $html) {
                return null;
            }

            libxml_use_internal_errors(true);
            $doc = new \DOMDocument();
            $doc->loadHTML($html);
            $xpath = new \DOMXPath($doc);
            $metaTags = [
                "//meta[@property='og:image']",
                "//meta[@property='og:image:secure_url']",
                "//meta[@name='twitter:image']",
            ];

            foreach ($metaTags as $query) {
                $nodes = $xpath->query($query);
                if ($nodes->length > 0) {
                    $content = $nodes->item(0)->getAttribute('content');
                    return $this->makeAbsoluteUrl($content, $url);
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function makeAbsoluteUrl(string $imageUrl, string $pageUrl): ?string
    {
        if (! $imageUrl) {
            return null;
        }

        if (preg_match('#^https?://#i', $imageUrl)) {
            return $imageUrl;
        }

        $base = parse_url($pageUrl);
        if (! $base || ! isset($base['scheme'], $base['host'])) {
            return $imageUrl;
        }

        $scheme = $base['scheme'];
        $host = $base['host'];
        $path = $base['path'] ?? '/';

        if (str_starts_with($imageUrl, '/')) {
            return "{$scheme}://{$host}{$imageUrl}";
        }

        $dir = rtrim(substr($path, 0, strrpos($path, '/') + 1), '/');
        return "{$scheme}://{$host}{$dir}/{$imageUrl}";
    }
}
