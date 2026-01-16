<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageBuilderStructure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\SectionTranslation;
use Illuminate\Support\Facades\Storage;


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
     *
     * 👉 الآن نعتمد على حقل "project" الجديد،
     *   مع دعم fallback لحقل "structure" القديم (عن طريق الموديل).
     */
    public function loadData(Page $page): JsonResponse
    {
        $locale = $this->requestLocale(request());

        $builder = PageBuilderStructure::query()
            ->where('page_id', $page->id)
            ->where('locale', $locale)
            ->first();


        if ($builder) {
            // getCurrentProject() ميثود في الموديل يرجّع project أو structure القديم
            $project = $builder->getCurrentProject();

            if (!empty($project) && is_array($project)) {
                return response()->json([
                    'structure' => $project,   // هذا ما يتوقعه GrapesJS
                    'html'      => $builder->html,
                    'css'       => $builder->css,
                ]);
            }
        }


        $heroSection = Section::with(['translations' => function ($q) use ($locale) {
            $q->where('locale', $locale);
        }])
            ->where('page_id', $page->id)
            ->where('type', 'hero_default')
            ->first();

        $content = $heroSection?->translations->first()?->content ?? [];

        $title          = $content['title']    ?? 'عنوان غير متوفر';
        $subtitle       = $content['subtitle'] ?? 'نص وصفي قصير يوضح الفكرة الرئيسية للخدمة أو المنصة.';
        $primaryLabel   = $content['primary_button']['label']   ?? 'ابدأ الآن';
        $secondaryLabel = $content['secondary_button']['label'] ?? 'استعرض القوالب';

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
                                                    'class' =>
                                                    'absolute -bottom-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl z-0',
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

    /**
     * نحاول استخراج بيانات الهيرو من الـ project (أو structure) لتحديث hero_default section.
     */
    protected function extractHeroContentFromStructure(array $structure): ?array
    {
        $pages = $structure['pages'] ?? [];
        if (empty($pages)) return null;

        $frames = $pages[0]['frames'] ?? [];
        if (empty($frames)) return null;

        $root = $frames[0]['component'] ?? null;
        if (!is_array($root)) return null;

        $hero = $this->findComponentByType($root, 'hero-section');
        if (!$hero) return null;

        // ابحث داخل الهيرو عن العناصر حسب data-field
        $titleCmp = $this->findComponentByDataField($hero, 'title');
        $subCmp   = $this->findComponentByDataField($hero, 'subtitle');
        $priBtn   = $this->findComponentByDataField($hero, 'primary-button');
        $secBtn   = $this->findComponentByDataField($hero, 'secondary-button');

        $title = is_array($titleCmp) ? ($titleCmp['content'] ?? null) : null;
        $subtitle = is_array($subCmp) ? ($subCmp['content'] ?? null) : null;

        $primaryLabel = is_array($priBtn) ? ($priBtn['content'] ?? null) : null;
        $primaryUrl   = is_array($priBtn) ? ($priBtn['attributes']['href'] ?? null) : null;

        $secondaryLabel = is_array($secBtn) ? ($secBtn['content'] ?? null) : null;
        $secondaryUrl   = is_array($secBtn) ? ($secBtn['attributes']['href'] ?? null) : null;

        if (!$title && !$subtitle && !$primaryLabel && !$secondaryLabel) {
            return null;
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
     * يبحث Recursively عن component يحمل attributes[data-field] = $field
     */
    protected function findComponentByDataField(array $component, string $field): ?array
    {
        $attrs = $component['attributes'] ?? [];
        if (is_array($attrs) && (($attrs['data-field'] ?? null) === $field)) {
            return $component;
        }

        $children = $component['components'] ?? [];
        if (!is_array($children)) return null;

        foreach ($children as $child) {
            if (!is_array($child)) continue;
            $found = $this->findComponentByDataField($child, $field);
            if ($found) return $found;
        }

        return null;
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
     * Save GrapesJS project data for this page.
     *
     * ✅ الآن نخزّن:
     *  - project : JSON كامل من getProjectData()
     *  - html    : ناتج getHtml()
     *  - css     : ناتج getCss()
     * ونحدث hero_default section كما كان سابقًا.
     */
    public function saveData(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'structure' => 'required|array',   // projectData من GrapesJS
            'html'      => 'required|string', // HTML النهائي للفرونت
            'css'       => 'nullable|string', // CSS النهائي للفرونت
        ]);

        $project = $validated['structure'];
        $html    = $validated['html'];
        $css     = $validated['css'] ?? null;

        // 1) نخزن الـ project + html + css في جدول page_builder_structures
        $locale = $this->requestLocale($request);

        $builder = PageBuilderStructure::updateOrCreate(
            ['page_id' => $page->id, 'locale' => $locale],

            [
                'project'   => $project,
                'html'      => $html,
                'css'       => $css,

                // اختياري: نخزّن كمان في structure عشان التوافق الرجعي
                'structure' => $project,
            ]
        );

        // 2) نحاول نحدّث hero_default section من نفس الـ project (نفس منطقك القديم)
        $heroContent = $this->extractHeroContentFromStructure($project);

        if ($heroContent) {
            
            $section = Section::where('page_id', $page->id)
                ->where('type', 'hero_default')
                ->first();

            if ($section) {
                $translation = SectionTranslation::firstOrNew([
                    'section_id' => $section->id,
                    'locale'     => $locale,
                ]);

                $oldContent = is_array($translation->content) ? $translation->content : [];

                $translation->content = array_merge($oldContent, $heroContent);
                $translation->save();
            }
        }

        return response()->json([
            'status'    => 'ok',
            'structure' => $builder->project,
        ]);
    }

    /**
     * Publish current builder snapshot for a page.
     *
     * This will:
     * - Take the latest saved HTML + CSS from PageBuilderStructure
     * - Store CSS in a public file (if not empty)
     * - Save published_html + published_css_path + published_at
     * - Used later in front/pages/page.blade.php (publishedHtml / publishedCss)
     */
    public function publish(Page $page): JsonResponse
    {
        $locale = $this->requestLocale(request());

        $builder = PageBuilderStructure::query()
            ->where('page_id', $page->id)
            ->where('locale', $locale)
            ->first();

        if (! $builder || ! $builder->html) {
            return response()->json([
                'status'  => 'error',
                'message' => 'لا يوجد محتوى جاهز للنشر لهذه اللغة. تأكد من حفظ الصفحة أولاً في البيلدر.',
            ], 422);
        }

        $css = (string) ($builder->css ?? '');
        $cssPath = null;

        // لو في CSS فعلي → نحفظه في ملف مستقل في disk public
        if (trim($css) !== '') {
            $directory = 'builder-css';

                // Example: builder-css/page-5-1735400000.css
                $fileName = 'page-' . $page->id . '-' . $locale . '-' . time() . '.css';
                $relativePath = $directory . '/' . $fileName;

            // ننشئ الدليل لو مش موجود و نحفظ الملف
            Storage::disk('public')->put($relativePath, $css);

            // هذا المسار هو اللي نستخدمه في الفرونت مع asset()
            $cssPath = 'storage/' . $relativePath;
        }

        // لو عندنا CSS منشور قديم، ممكن تختار:
        // - إلغاءه (تركه)
        // - أو تحاول تحذفه من Storage
        // هنا نتركه حالياً لتبسيط الموضوع

        $builder->published_html     = $builder->html;
        $builder->published_css_path = $cssPath;
        $builder->published_at       = now();
        $builder->save();

        return response()->json([
            'status'        => 'ok',
            'message'       => 'تم نشر الصفحة بنجاح.',
            'published_at'  => $builder->published_at?->toDateTimeString(),
            'css_url'       => $cssPath ? asset($cssPath) : null,
        ]);
    }

    protected function requestLocale(Request $request): string
    {
        $locale = $request->query('locale')
            ?: $request->input('locale')
            ?: app()->getLocale();

        $locale = strtolower(trim($locale));

        $isActive = Language::query()
            ->where('code', $locale)
            ->where('is_active', true)
            ->exists();

        if (! $isActive) {
            $locale = config('app.fallback_locale', 'ar');
        }

        return $locale;
    }
}
