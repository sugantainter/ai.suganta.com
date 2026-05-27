<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ProgrammaticSeoRoutingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            Redis::flushall();
        } catch (\Throwable $e) {
            // Ignore when Redis is unavailable in CI.
        }

        try {
            \Illuminate\Support\Facades\Cache::store('file')->flush();
        } catch (\Throwable $e) {
            // Ignore.
        }
    }

    public function test_valid_comparison_slug_returns_seo_page(): void
    {
        $this->get('/chatgpt-vs-gemini')
            ->assertOk()
            ->assertHeader('X-Cache')
            ->assertSee('ChatGPT', false)
            ->assertDontSee('{$tool2}', false);
    }

    public function test_valid_nested_workflow_slug_returns_seo_page(): void
    {
        $this->get('/ai-workflows/education')
            ->assertOk()
            ->assertSee('Education', false);
    }

    public function test_invalid_slug_returns_public_seo_404_not_redirect(): void
    {
        $response = $this->get('/totally-invalid-random-slug-xyz');

        $response->assertNotFound();
        $response->assertSee('Page not found', false);
        $response->assertHeader('X-Seo-Page', 'not-found');
        $this->assertFalse($response->isRedirect());
    }

    public function test_invalid_legacy_internal_link_pattern_returns_404(): void
    {
        $this->get('/ai-tools-for-learning-python')
            ->assertNotFound();
    }

    public function test_valid_learning_listicle_slug_returns_seo_page(): void
    {
        $this->get('/best-ai-tools-for-learning-python')
            ->assertOk();
    }

    public function test_excluded_app_route_is_not_handled_as_seo_page(): void
    {
        $this->get('/settings')
            ->assertRedirect();
    }
}
