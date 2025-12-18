<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageBuilderStructure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\SectionTranslation;

class PageBuilderController extends Controller
{
    /**
     * Render the GrapesJS builder view for a given page.
     */
    public function edit(Page $page)
    {
        $page->loadMissing('translations');

        return view('dashboard.pages.builder', [
            'page' => $page,
        ]);
    }

    /**
     * Return stored GrapesJS project data for this page.
     */
    public function loadData(Page $page): JsonResponse
    {
        $builder = $page->builderStructure;

        // لو فيه structure محفوظ، رجّعه زي ما هو
        if ($builder && is_array($builder->structure) && !empty($builder->structure)) {
            return response()->json([
                'structure' => $builder->structure,
            ]);
        }

        // لو مافيش structure → نبني واحد افتراضي من سكشن الـ hero_default
        $locale = app()->getLocale();

        $heroSection = Section::with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale);
        }])
            ->where('page_id', $page->id)
            ->where('type', 'hero_default')
            ->first();

        $content = $heroSection?->translations->first()?->content ?? [];

        $title    = $content['title']    ?? 'عنوان غير متوفر';
        $subtitle = $content['subtitle'] ?? 'نص وصفي قصير يوضح الفكرة الرئيسية للخدمة أو المنصة.';
        $primaryLabel = $content['primary_button']['label'] ?? 'ابدأ الآن';
        $secondaryLabel = $content['secondary_button']['label'] ?? 'استعرض القوالب';

        // 👇 هذا شبيه بالـ JSON اللي عندك، بس ديناميكي
        $structure = [
            'pages' => [
                [
                    'id' => 'index',
                    'name' => 'Index',
                    'frames' => [
                        [
                            'id' => 'frame-1',
                            'component' => [
                                'type' => 'wrapper',
                                'attributes' => [
                                    'class' => 'w-full bg-slate-50 dark:bg-slate-950',
                                    'style' => 'min-height: calc(100vh - 72px); width: 100%;',
                                ],
                                'components' => [
                                    [
                                        'type' => 'hero-section',
                                        'attributes' => [
                                            'data-section-type' => 'hero',
                                        ],
                                        'components' => [
                                            [
                                                'type' => 'image',
                                                'attributes' => [
                                                    'src' => '/assets/tamplate/images/template.webp',
                                                    'alt' => '',
                                                    'aria-hidden' => 'true',
                                                    'loading' => 'eager',
                                                    'decoding' => 'async',
                                                    'fetchpriority' => 'high',
                                                ],
                                                'classes' => [
                                                    'absolute',
                                                    'inset-0',
                                                    'z-0',
                                                    'opacity-80',
                                                    'w-full',
                                                    'h-full',
                                                    'object-cover',
                                                    'object-center',
                                                    'ltr:scale-x-[-1]',
                                                    'rtl:scale-x-100',
                                                    'transition-transform',
                                                    'duration-500',
                                                    'ease-in-out',
                                                ],
                                            ],
                                            [
                                                'attributes' => [
                                                    'class' =>
                                                    'relative z-10 px-4 sm:px-8 lg:px-24 py-20 sm:py-28 lg:py-32 ' .
                                                        'flex flex-col-reverse md:flex-row items-center justify-between ' .
                                                        'gap-12 min-h-[600px] lg:min-h-[700px]',
                                                ],
                                                'components' => [
                                                    [
                                                        'attributes' => [
                                                            'class' =>
                                                            'max-w-xl rtl:text-right ltr:text-left text-center md:text-start',
                                                        ],
                                                        'components' => [
                                                            [
                                                                'type' => 'text',
                                                                'tagName' => 'h1',
                                                                'attributes' => [
                                                                    'data-field' => 'title',
                                                                    'class' =>
                                                                    'text-3xl/20 sm:text-4xl/20 lg:text-5xl/20 ' .
                                                                        'font-extrabold text-white leading-tight ' .
                                                                        'drop-shadow-lg mb-6',
                                                                ],
                                                                'content' => $title,
                                                            ],
                                                            [
                                                                'type' => 'text',
                                                                'tagName' => 'p',
                                                                'attributes' => [
                                                                    'data-field' => 'subtitle',
                                                                    'class' =>
                                                                    'text-white/90 text-base sm:text-lg ' .
                                                                        'font-light mb-8',
                                                                ],
                                                                'content' => $subtitle,
                                                            ],
                                                            [
                                                                'attributes' => [
                                                                    'class' =>
                                                                    'flex flex-row flex-wrap gap-3 ' .
                                                                        'justify-center md:justify-start',
                                                                ],
                                                                'components' => [
                                                                    [
                                                                        'type' => 'link',
                                                                        'attributes' => [
                                                                            'href' => $content['primary_button']['url'] ?? '#',
                                                                            'aria-label' => $primaryLabel,
                                                                            'data-field' => 'primary-button',
                                                                            'class' =>
                                                                            'bg-secondary hover:bg-primary text-white ' .
                                                                                'font-bold px-6 py-3 rounded-lg shadow ' .
                                                                                'transition text-sm sm:text-base',
                                                                        ],
                                                                        'content' => $primaryLabel,
                                                                    ],
                                                                    [
                                                                        'type' => 'link',
                                                                        'attributes' => [
                                                                            'href' => $content['secondary_button']['url'] ?? '#',
                                                                            'data-field' => 'secondary-button',
                                                                            'class' =>
                                                                            'bg-white/10 text-white font-bold px-6 py-3 ' .
                                                                                'rounded-lg shadow transition hover:bg-white/20 ' .
                                                                                'text-sm sm:text-base border border-white/30',
                                                                        ],
                                                                        'content' => $secondaryLabel,
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'attributes' => [
                                                    'class' => 'absolute -bottom-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl z-0',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'assets' => [],
            'styles' => [],
            'symbols' => [],
            'dataSources' => [],
        ];

        return response()->json([
            'structure' => $structure,
        ]);
    }

    protected function extractHeroContentFromStructure(array $structure): ?array
    {
        $pages = $structure['pages'] ?? [];
        if (empty($pages)) {
            return null;
        }

        // نفترض مؤقتًا أن الهيرو في أول صفحة وأول فريم
        $page   = $pages[0] ?? [];
        $frames = $page['frames'] ?? [];
        if (empty($frames)) {
            return null;
        }

        $rootComponent = $frames[0]['component'] ?? null;
        if (!is_array($rootComponent)) {
            return null;
        }

        // نبحث عن component من نوع hero-section (search recursive)
        $hero = $this->findComponentByType($rootComponent, 'hero-section');

        if (!$hero) {
            return null;
        }

        // نطلع العنوان/الوصف/الأزرار من الـ children
        $children = $hero['components'] ?? [];
        if (empty($children)) {
            return null;
        }

        // child[1] = المحتوى الرئيسي عادةً (الـ div اللي جواته العنوان، الوصف، الأزرار)
        $contentWrapper = $children[1]['components'][0] ?? null;
        if (!$contentWrapper) {
            return null;
        }

        $inner = $contentWrapper['components'] ?? [];

        // h1
        $titleCmp = $inner[0] ?? null;
        $subtitleCmp = $inner[1] ?? null;
        $buttonsWrapper = $inner[2] ?? null;

        $title    = is_array($titleCmp)    ? ($titleCmp['content']    ?? null) : null;
        $subtitle = is_array($subtitleCmp) ? ($subtitleCmp['content'] ?? null) : null;

        $primaryLabel    = null;
        $primaryUrl      = null;
        $secondaryLabel  = null;
        $secondaryUrl    = null;

        if (is_array($buttonsWrapper)) {
            $buttons = $buttonsWrapper['components'] ?? [];
            $primary   = $buttons[0] ?? null;
            $secondary = $buttons[1] ?? null;

            if (is_array($primary)) {
                $primaryLabel = $primary['content'] ?? null;
                $primaryUrl   = $primary['attributes']['href'] ?? null;
            }

            if (is_array($secondary)) {
                $secondaryLabel = $secondary['content'] ?? null;
                $secondaryUrl   = $secondary['attributes']['href'] ?? null;
            }
        }

        return [
            'title'    => $title,
            'subtitle' => $subtitle,
            'primary_button' => [
                'label' => $primaryLabel,
                'url'   => $primaryUrl,
            ],
            'secondary_button' => [
                'label' => $secondaryLabel,
                'url'   => $secondaryUrl,
            ],
        ];
    }

    /**
     * بحث Recursively عن component من نوع معيّن في شجرة GrapesJS
     */
    protected function findComponentByType(array $component, string $type): ?array
    {
        if (($component['type'] ?? null) === $type) {
            return $component;
        }

        $children = $component['components'] ?? [];
        if (!is_array($children)) {
            return null;
        }

        foreach ($children as $child) {
            if (!is_array($child)) {
                continue;
            }
            $found = $this->findComponentByType($child, $type);
            if ($found) {
                return $found;
            }
        }

        return null;
    }


    /**
     * Save GrapesJS project data (components JSON) for this page.
     */
    public function saveData(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'structure' => 'required|array',
        ]);

        $structure = $validated['structure'];

        // 1) نخزن الـ structure في جدول page_builder_structures
        $builder = PageBuilderStructure::updateOrCreate(
            ['page_id' => $page->id],
            ['structure' => $structure]
        );

        // 2) نحاول نحدّث hero_default section من نفس الـ structure
        $heroContent = $this->extractHeroContentFromStructure($structure);

        if ($heroContent) {
            $locale = app()->getLocale();

            // نجيب سكشن hero_default
            $section = Section::where('page_id', $page->id)
                ->where('type', 'hero_default')
                ->first();

            if ($section) {
                $translation = SectionTranslation::firstOrNew([
                    'section_id' => $section->id,
                    'locale'     => $locale,
                ]);

                // ندمج الـ content القديم مع الجديد (عشان ما نضيع حقول ثانية مثل media أو features)
                $oldContent = is_array($translation->content) ? $translation->content : [];

                $translation->content = array_merge($oldContent, $heroContent);
                $translation->save();
            }
        }

        return response()->json([
            'status'    => 'ok',
            'structure' => $builder->structure,
        ]);
    }
}
