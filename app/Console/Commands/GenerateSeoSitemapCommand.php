<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoSlugRegistryService;
use App\Services\Seo\SlugAnalyzerService;
use App\Services\Seo\SitemapXmlBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSeoSitemapCommand extends Command
{
    protected $signature = 'seo:generate-sitemap
        {--path=public/sitemap.xml : Output path relative to the application base path}
        {--base-url= : Canonical site base URL (defaults to APP_URL)}
        {--limit= : Limit the number of URLs to generate (for testing)}
        {--gzip : Deprecated; compressed chunks are now always generated}';

    protected $description = 'Generate split XML sitemaps for all valid programmatic SEO pages';

    public function handle(SeoSlugRegistryService $registry, SitemapXmlBuilder $builder, SlugAnalyzerService $analyzer): int
    {
        $baseUrl = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');
        if ($baseUrl === '') {
            $this->error('Base URL is empty. Set APP_URL or pass --base-url=https://ai.suganta.com');

            return self::FAILURE;
        }

        $this->info('Streaming valid SEO slugs to compressed chunks…');

        $changefreq = (string) config('seo.sitemap.changefreq', 'weekly');
        $defaultPriority = (float) config('seo.sitemap.default_priority', 0.7);
        $prioritiesByType = (array) config('seo.sitemap.priority_by_type', []);
        $lastmod = $builder->lastmodFromTimestamp();

        $relativePath = str_replace('\\', '/', ltrim((string) $this->option('path'), '/\\'));
        $indexPath = base_path($relativePath);
        $outputDir = dirname($indexPath);

        // Figure out the URL segment path relative to the root if written inside public
        $urlSegment = '';
        if (str_starts_with($relativePath, 'public/')) {
            $urlSegment = substr(dirname($relativePath), 7); // Strip 'public/'
            $urlSegment = $urlSegment ? '/' . ltrim($urlSegment, '/') : '';
        }

        File::ensureDirectoryExists($outputDir);

        $chunkSize = 50000;
        $chunkIndex = 1;
        $urlCountInChunk = 0;
        $totalUrls = 0;

        $tempFiles = [];
        
        $startXml = function() {
            return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                   '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        };

        $endXml = function() {
            return '</urlset>' . "\n";
        };

        $currentXml = $startXml();

        $writeChunk = function($index, $xmlContent) use ($outputDir, &$tempFiles) {
            $filename = "sitemap-{$index}.xml.gz";
            $chunkPath = $outputDir . '/' . $filename;

            $gz = gzopen($chunkPath, 'wb9');
            if ($gz === false) {
                throw new \Exception("Failed to write gzip file: {$chunkPath}");
            }
            gzwrite($gz, $xmlContent);
            gzclose($gz);

            $tempFiles[] = $filename;
            $this->info("Wrote chunk {$index} to {$filename} (" . strlen($xmlContent) . " bytes uncompressed)");
        };

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        foreach ($registry->getSlugsGenerator() as $slug) {
            if ($limit !== null && $totalUrls >= $limit) {
                break;
            }
            $analysis = $analyzer->analyze($slug);
            if (!($analysis['isValid'] ?? false)) {
                continue;
            }

            $pageType = $analysis['pageType'] ?? 'general';
            $priority = (float) ($prioritiesByType[$pageType] ?? $defaultPriority);

            $loc = htmlspecialchars($baseUrl . '/' . ltrim($slug, '/'), ENT_XML1, 'UTF-8');
            $currentXml .= "  <url>\n" .
                           "    <loc>{$loc}</loc>\n" .
                           "    <lastmod>{$lastmod}</lastmod>\n" .
                           "    <changefreq>{$changefreq}</changefreq>\n" .
                           "    <priority>{$priority}</priority>\n" .
                           "  </url>\n";

            $urlCountInChunk++;
            $totalUrls++;

            if ($urlCountInChunk >= $chunkSize) {
                $currentXml .= $endXml();
                $writeChunk($chunkIndex, $currentXml);
                $chunkIndex++;
                $urlCountInChunk = 0;
                $currentXml = $startXml();
            }
        }

        if ($urlCountInChunk > 0) {
            $currentXml .= $endXml();
            $writeChunk($chunkIndex, $currentXml);
        }

        if ($totalUrls === 0) {
            $this->warn('No valid SEO URLs were generated.');
            return self::FAILURE;
        }

        // Output index sitemap
        $indexXml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
                    '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($tempFiles as $file) {
            $loc = htmlspecialchars($baseUrl . $urlSegment . '/' . $file, ENT_XML1, 'UTF-8');
            $indexXml .= "  <sitemap>\n" .
                         "    <loc>{$loc}</loc>\n" .
                         "    <lastmod>{$lastmod}</lastmod>\n" .
                         "  </sitemap>\n";
        }
        $indexXml .= '</sitemapindex>' . "\n";

        File::put($indexPath, $indexXml);

        $this->info("Successfully generated sitemap index at {$relativePath} linking to " . count($tempFiles) . " compressed chunks (Total: {$totalUrls} URLs).");

        return self::SUCCESS;
    }
}
