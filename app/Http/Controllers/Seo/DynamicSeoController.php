<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Services\Seo\SlugAnalyzerService;
use App\Services\Seo\IntentDetectionService;
use App\Services\Seo\KeywordClusterService;
use App\Services\Seo\DynamicContentService;
use App\Services\Seo\MetaGenerationService;
use App\Services\Seo\SchemaGenerationService;
use App\Services\Seo\InternalLinkService;
use App\Services\Seo\FAQGenerationService;
use App\Services\Seo\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DynamicSeoController extends Controller
{
    protected $analyzer;
    protected $intent;
    protected $keywords;
    protected $content;
    protected $meta;
    protected $schema;
    protected $links;
    protected $faqs;
    protected $cache;

    public function __construct(
        SlugAnalyzerService $analyzer,
        IntentDetectionService $intent,
        KeywordClusterService $keywords,
        DynamicContentService $content,
        MetaGenerationService $meta,
        SchemaGenerationService $schema,
        InternalLinkService $links,
        FAQGenerationService $faqs,
        CacheService $cache
    ) {
        $this->analyzer = $analyzer;
        $this->intent = $intent;
        $this->keywords = $keywords;
        $this->content = $content;
        $this->meta = $meta;
        $this->schema = $schema;
        $this->links = $links;
        $this->faqs = $faqs;
        $this->cache = $cache;
    }

    /**
     * Render the requested slug dynamically.
     */
    public function render(Request $request, string $slug = '')
    {
        $slug = trim($slug, '/');

        // 1. Analyze the slug
        $analysis = $this->analyzer->analyze($slug);

        if (! $analysis['isValid']) {
            return $this->notFoundResponse($slug);
        }

        // 2. Try Redis Cache
        $cacheHit = $this->cache->get($slug);

        if ($cacheHit !== null) {
            $html = $cacheHit['html'];

            // Stale-While-Revalidate trigger
            if ($cacheHit['is_stale']) {
                $this->triggerBackgroundRegeneration($slug, $analysis);
                return response($html)
                    ->header('Content-Type', 'text/html; charset=UTF-8')
                    ->header('X-Cache', 'HIT-STALE');
            }

            return response($html)
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('X-Cache', 'HIT');
        }

        // 3. Cache Miss - Generate dynamically
        $html = $this->generatePageHtml($slug, $analysis);

        // Save to cache
        $this->cache->set($slug, $html);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Cache', 'MISS');
    }

    /**
     * Generate HTML for the page.
     */
    protected function generatePageHtml(string $slug, array $analysis): string
    {
        // Detect Search Intent
        $intent = $this->intent->detect($analysis);

        // Generate Semantic Keywords
        $clusters = $this->keywords->generate($analysis);

        // Generate Base Content Sections
        $pageData = $this->content->generate($slug, $analysis, $intent, $clusters);

        // Generate Fallback/Structured FAQs if missing
        if (empty($pageData['faqs'])) {
            $pageData['faqs'] = $this->faqs->generate($analysis);
        }

        // Compile related internal linking grid
        $relatedLinks = $this->links->generate($slug, $analysis);
        $pageData['related_links'] = $relatedLinks;

        // Generate Meta tags
        $seo = $this->meta->generate($slug, $pageData, $analysis, $clusters);

        // Generate JSON-LD schemas
        $schema = $this->schema->generate($slug, $pageData, $analysis);

        // Generate Breadcrumb Labels for view
        $breadcrumbs = [];
        $categoryName = $pageData['hero']['category'] ?? 'AI Resources';
        $breadcrumbs[$categoryName] = $this->categoryBreadcrumbUrl($analysis);
        $breadcrumbs[$pageData['hero']['title']] = url($slug);

        // Render Blade template to HTML string
        return view('seo.page', [
            'content' => $pageData,
            'seo' => $seo,
            'schema' => $schema,
            'breadcrumbs' => $breadcrumbs,
        ])->render();
    }

    /**
     * Map page analysis to a valid workflow hub URL for breadcrumbs.
     */
    protected function categoryBreadcrumbUrl(array $analysis): string
    {
        $industry = $analysis['industry'] ?? null;
        if (is_string($industry)) {
            $key = \Illuminate\Support\Str::slug($industry);
            if (($this->analyzer->analyze("ai-workflows/{$key}")['isValid'] ?? false)) {
                return url("ai-workflows/{$key}");
            }
        }

        return url('ai-workflows/education');
    }

    /**
     * Public SEO 404 — no auth redirect, no SPA fallback.
     */
    protected function notFoundResponse(string $slug): Response
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $path = ltrim($slug, '/');

        return response()->view('seo.not-found', [
            'seo' => [
                'title' => 'Page Not Found | '.config('public_nav.header.logo.alt', 'SuGanta'),
                'description' => 'The requested AI guide or comparison page could not be found.',
                'keywords' => 'page not found, 404',
                'robots' => 'noindex, follow',
                'canonical' => $path !== '' ? "{$baseUrl}/{$path}" : $baseUrl,
            ],
            'schema' => [],
            'breadcrumbs' => [],
        ], Response::HTTP_NOT_FOUND)->header('X-Seo-Page', 'not-found');
    }

    /**
     * Register post-response callback to regenerate cache (Stale-While-Revalidate).
     */
    protected function triggerBackgroundRegeneration(string $slug, array $analysis): void
    {
        app()->terminating(function () use ($slug, $analysis): void {
            try {
                $freshHtml = $this->generatePageHtml($slug, $analysis);
                $this->cache->set($slug, $freshHtml);
            } catch (\Throwable $e) {
                // Silently absorb regeneration errors in post-response
            }
        });
    }
}
