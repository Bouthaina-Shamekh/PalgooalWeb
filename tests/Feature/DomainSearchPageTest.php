<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Section;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\Template as SectionTemplate;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * P0 #4 — GET /domains ("domains.page") was returning a 500 because its
 * controller (DomainSearchController::page()) rendered a view
 * ("domains.search") that did not exist anywhere in the codebase.
 *
 * A first fix wrapped an orphaned, stale legacy component
 * (<x-template.sections.search-domain />) in a new standalone view. That fix
 * was rejected after a re-audit: the authoritative domain-search UI is the
 * Page Builder section template
 * resources/views/front/sections/templates/domain_search.blade.php, already
 * used (and working) by a real multilingual builder Page reachable in
 * Arabic at "/حجز-الدومين". "/domains" is that same builder page's stable,
 * locale-independent entry point (also referenced by the footer links and
 * the "domains showcase" section's default CTA via route('domains.page')).
 *
 * The real fix makes DomainSearchController::page() resolve the active
 * marketing Page that owns the "domain_search" builder section and render
 * it in-process through the same pipeline as the "/{slug}" catch-all
 * (Front\PageController::show()/showBySectionTemplate()), instead of owning
 * a standalone view of its own.
 *
 * This test builds the minimal Page + PageTranslation + Section +
 * SectionDefinition + SectionTemplate graph needed to reproduce that
 * builder page and proves:
 *  - GET /domains returns 200 (no longer a missing-view 500)
 *  - it renders through the builder pipeline ("front.pages.page"), never
 *    the removed "domains.search" view
 *  - the actual authoritative domain_search section markup is present
 *    (the data-domain-search-* attributes that only that template renders)
 *
 * The domains.check API contract (availability, pricing, unknown-vs-
 * unavailable semantics) is already covered by DomainSearchAvailabilityTest
 * and is not duplicated here.
 */
class DomainSearchPageTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    public function test_domains_page_returns_200_and_renders_the_builder_domain_search_section(): void
    {
        $this->makeDomainSearchBuilderPage();

        // Same locale state as the reported bug: an English-locale visitor
        // (e.g. after using the language switcher) requesting /domains.
        $response = $this->withSession(['locale' => 'en'])->get(route('domains.page'));

        $response->assertOk();
        $response->assertViewIs('front.pages.page');

        // Markup unique to the authoritative builder section template --
        // the rejected legacy <x-template.sections.search-domain /> component
        // does not render these data attributes.
        $response->assertSee('data-domain-search-form', false);
        $response->assertSee('data-domain-search-input', false);
        $response->assertSee('data-domain-search-button', false);
    }

    public function test_domains_page_does_not_use_the_removed_standalone_view(): void
    {
        $this->makeDomainSearchBuilderPage();

        $response = $this->withSession(['locale' => 'en'])->get(route('domains.page'));

        $response->assertOk();
        // Laravel's TestResponse has no assertViewIsNot(); assert the
        // rendered view's own name directly instead (Illuminate\View\View
        // exposes it via getName()) to prove the removed "domains.search"
        // view is not what rendered this response.
        $this->assertNotSame('domains.search', $response->original->getName());
    }

    private function makeDomainSearchBuilderPage(): Page
    {
        Language::create([
            'name' => 'Arabic', 'native' => 'العربية', 'code' => 'ar',
            'is_rtl' => true, 'is_active' => true,
        ]);
        Language::create([
            'name' => 'English', 'native' => 'English', 'code' => 'en',
            'is_rtl' => false, 'is_active' => true,
        ]);

        $page = Page::create([
            'context'   => 'marketing',
            'is_active' => true,
            'is_home'   => false,
        ]);

        PageTranslation::create([
            'page_id' => $page->id,
            'locale'  => 'ar',
            'slug'    => 'حجز-الدومين',
            'title'   => 'ابحث عن اسم دومين',
        ]);

        PageTranslation::create([
            'page_id' => $page->id,
            'locale'  => 'en',
            'slug'    => 'domains',
            'title'   => 'Domain Search',
        ]);

        $template = SectionTemplate::create([
            'template_key' => 'domain_search',
            'label'        => 'Domain Search',
            'category'     => 'templates',
            'is_active'    => true,
            'is_visible'   => true,
        ]);

        $definition = SectionDefinition::create([
            'section_key' => 'domain_search',
            'label'       => 'Domain Search',
            'category'    => 'templates',
            'editor_mode' => 'dynamic',
            'is_active'   => true,
            'is_visible'  => true,
        ]);

        $definition->templates()->attach($template->id, ['sort_order' => 0]);

        Section::create([
            'page_id'               => $page->id,
            'section_definition_id' => $definition->id,
            'type'                  => 'domain_search',
            'order'                 => 0,
            'is_active'             => true,
        ]);

        return $page;
    }
}
