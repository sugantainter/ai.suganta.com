<?php

namespace Tests\Unit;

use App\Services\Seo\SeoSlugRegistryService;
use App\Services\Seo\SlugAnalyzerService;
use Tests\TestCase;

class SeoSlugRegistryServiceTest extends TestCase
{
    public function test_registry_includes_known_comparison_slug(): void
    {
        $registry = new SeoSlugRegistryService(new SlugAnalyzerService());

        $slugs = $registry->allValidSlugs();

        $this->assertContains('chatgpt-vs-gemini', $slugs);
        $this->assertContains('best-ai-tools-for-students', $slugs);
        $this->assertContains('ai-workflows/education', $slugs);
    }

    public function test_registry_excludes_spa_routes(): void
    {
        $registry = new SeoSlugRegistryService(new SlugAnalyzerService());

        $slugs = $registry->allValidSlugs();

        $this->assertNotContains('settings', $slugs);
        $this->assertNotContains('contact', $slugs);
    }
}
