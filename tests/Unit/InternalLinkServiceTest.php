<?php

namespace Tests\Unit;

use App\Services\Seo\InternalLinkService;
use App\Services\Seo\SlugAnalyzerService;
use Tests\TestCase;

class InternalLinkServiceTest extends TestCase
{
    public function test_generated_links_only_use_valid_slugs(): void
    {
        $analyzer = new SlugAnalyzerService();
        $service = new InternalLinkService($analyzer);

        $analysis = $analyzer->analyze('chatgpt-vs-gemini');
        $links = $service->generate('chatgpt-vs-gemini', $analysis);

        $this->assertNotEmpty($links);

        foreach ($links as $link) {
            $slug = trim($link['url'], '/');
            $this->assertTrue(
                $analyzer->analyze($slug)['isValid'] ?? false,
                "Invalid internal link generated: {$link['url']}"
            );
        }

        $this->assertFalse($analyzer->analyze('ai-tools-for-learning-python')['isValid'] ?? true);
    }
}
