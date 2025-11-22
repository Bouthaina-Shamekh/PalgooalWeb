<?php

namespace App\Services\Feeds;

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
            return [
                'title' => $item->get_title(),
                'url' => $item->get_permalink(),
                'description' => strip_tags($item->get_description()),
                'date' => $item->get_date('Y-m-d'),
                'author' => optional($item->get_author())->get_name() ?? 'Palgoals',
                'image' => $this->extractImage($item->get_description()),
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
}
