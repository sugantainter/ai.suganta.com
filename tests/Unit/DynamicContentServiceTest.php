<?php

namespace Tests\Unit;

use App\Services\Seo\DynamicContentService;
use App\Services\Seo\IntentDetectionService;
use App\Services\Seo\KeywordClusterService;
use App\Services\Seo\SlugAnalyzerService;
use Tests\TestCase;

class DynamicContentServiceTest extends TestCase
{
    public function test_comparison_overview_interpolates_tool_names(): void
    {
        $analyzer = new SlugAnalyzerService();
        $analysis = $analyzer->analyze('chatgpt-vs-gemini');

        $service = new DynamicContentService(
            new IntentDetectionService(),
            new KeywordClusterService(),
        );

        $content = $service->generate('chatgpt-vs-gemini', $analysis, [], []);

        $overviewBody = $content['sections']['overview']['body'] ?? '';

        $this->assertStringContainsString('<strong>ChatGPT</strong>', $overviewBody);
        $this->assertStringContainsString('<strong>Gemini</strong>', $overviewBody);
        $this->assertStringNotContainsString('{$tool1}', $overviewBody);
        $this->assertStringNotContainsString('{$tool2}', $overviewBody);
    }
}
