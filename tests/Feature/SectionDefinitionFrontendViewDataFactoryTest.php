<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\SectionTranslation;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\Template as SectionTemplate;
use App\Support\Sections\SectionDefinitionFrontendViewDataFactory;
use App\Support\Sections\SectionDefinitionRuntimeResolver;
use App\Support\Sections\SectionTemplateRegistry;
use Tests\TestCase;

class SectionDefinitionFrontendViewDataFactoryTest extends TestCase
{
    public function test_explicit_registry_override_wins_for_definition_driven_sections(): void
    {
        $factory = new SectionDefinitionFrontendViewDataFactory(
            $this->mockRuntimeResolverFor($this->makeDefinition('brand_new_dynamic_section', 'faq_section')),
        );

        $payload = $factory->build($this->makeSection('brand_new_dynamic_section'), 'en');

        $this->assertIsArray($payload);
        $this->assertSame('front.sections.faq.faq_section', $payload['view']);
        $this->assertSame('registry', $payload['viewData']['sectionTemplateMeta']['resolution_source']);
    }

    public function test_convention_view_is_used_when_no_explicit_registry_entry_exists(): void
    {
        $factory = new SectionDefinitionFrontendViewDataFactory(
            $this->mockRuntimeResolverFor($this->makeDefinition(
                'brand_new_dynamic_section',
                'test_dynamic_card',
                'hero',
            )),
        );

        $payload = $factory->build($this->makeSection('brand_new_dynamic_section'), 'en');

        $this->assertIsArray($payload);
        $this->assertSame('front.sections.hero.test_dynamic_card', $payload['view']);
        $this->assertSame('convention', $payload['viewData']['sectionTemplateMeta']['resolution_source']);
    }

    public function test_legacy_fallback_is_preserved_when_the_section_type_already_has_a_legacy_renderer(): void
    {
        $factory = new SectionDefinitionFrontendViewDataFactory(
            $this->mockRuntimeResolverFor($this->makeDefinition('hero_campaign', 'missing_dynamic_renderer')),
        );

        $payload = $factory->build($this->makeSection('hero_campaign'), 'en');

        $this->assertNull($payload);
    }

    public function test_missing_renderer_state_is_returned_when_no_override_convention_or_legacy_renderer_exists(): void
    {
        $factory = new SectionDefinitionFrontendViewDataFactory(
            $this->mockRuntimeResolverFor($this->makeDefinition('brand_new_dynamic_section', 'missing_dynamic_renderer')),
        );

        $payload = $factory->build($this->makeSection('brand_new_dynamic_section'), 'en');

        $this->assertIsArray($payload);
        $this->assertSame(SectionTemplateRegistry::fallbackView(), $payload['view']);
        $this->assertSame(
            'missing_dynamic_renderer',
            $payload['viewData']['missingTemplate']['template_key'],
        );
        $this->assertSame(
            'hero',
            $payload['viewData']['missingTemplate']['category'],
        );
        $this->assertContains(
            'front.sections.hero.missing_dynamic_renderer',
            $payload['viewData']['missingTemplate']['attempted_views'],
        );
    }

    protected function mockRuntimeResolverFor(
        SectionDefinition $definition,
    ): SectionDefinitionRuntimeResolver {
        $resolver = $this->createMock(SectionDefinitionRuntimeResolver::class);

        $resolver->method('runtimeTablesAvailable')->willReturn(true);
        $resolver->method('resolveRenderableDefinition')->willReturn($definition);

        return $resolver;
    }

    protected function makeDefinition(
        string $sectionKey,
        string $templateKey,
        string $category = 'hero',
    ): SectionDefinition {
        $definition = new SectionDefinition([
            'section_key' => $sectionKey,
            'label' => 'Definition Label',
            'category' => $category,
            'is_active' => true,
        ]);
        $definition->id = 41;
        $definition->setRelation('templates', collect([
            new SectionTemplate([
                'template_key' => $templateKey,
                'label' => 'Template Label',
                'is_active' => true,
            ]),
        ]));
        $definition->setRelation('fields', collect());

        return $definition;
    }

    protected function makeSection(string $type): Section
    {
        $section = new Section([
            'type' => $type,
            'variant' => 'default',
            'is_active' => true,
        ]);
        $section->id = 17;
        $section->setRelation('translations', collect([
            new SectionTranslation([
                'locale' => 'en',
                'title' => 'Section Title',
                'content' => [],
            ]),
        ]));

        return $section;
    }
}
