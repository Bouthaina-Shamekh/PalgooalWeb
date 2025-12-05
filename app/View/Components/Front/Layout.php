<?php

namespace App\View\Components\Front;

use App\Support\SeoMeta;
use Illuminate\View\Component;
use Illuminate\View\View;

class Layout extends Component
{
    public ?string $title;
    public ?string $description;
    public array   $keywords;
    public ?string $ogImage;
    public $seo;

    public function __construct(
        ?string $title = null,
        ?string $description = null,
        $keywords = [],
        ?string $ogImage = null,
        $seo = null,
    ) {
        $this->title       = $title;
        $this->description = $description;
        $this->keywords    = is_array($keywords)
            ? $keywords
            : array_filter(array_map('trim', explode(',', (string) $keywords)));

        $this->ogImage     = $ogImage;
        $this->seo         = $seo;
    }

    /**
     * Build final SeoMeta payload (base + overrides).
     */
    public function seoPayload(): SeoMeta
    {
        $baseSeo = SeoMeta::make([
            'title'       => $this->title
                ?? (config('seo.default_title') ?? config('app.name', 'Palgoals')),
            'description' => $this->description
                ?? config('seo.default_description'),
            'keywords'    => $this->keywords
                ?: (config('seo.default_keywords', []) ?: []),
            'image'       => $this->ogImage
                ?? asset(config('seo.default_image', 'assets/images/default-og.jpg')),
        ]);

        $seoPayload = isset($this->seo)
            ? $baseSeo->with($this->seo instanceof SeoMeta ? $this->seo->toArray() : (array) $this->seo)
            : $baseSeo;

        // Append global schema (if exists)
        $defaultSchema = trim(view('front.layouts.partials.schema')->render());
        if ($defaultSchema !== '') {
            $existingSchema = $seoPayload->toArray()['schema'] ?? [];
            $existingSchema[] = $defaultSchema;
            $seoPayload = $seoPayload->with(['schema' => $existingSchema]);
        }

        return $seoPayload;
    }

    public function render(): View
    {
        return view('front.layouts.app', [
            'seoPayload' => $this->seoPayload(),
        ]);
    }
}
