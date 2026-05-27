<?php

namespace App\Console\Commands;

use App\Services\Seo\SeoSlugRegistryService;
use App\Services\Seo\SitemapXmlBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSeoSitemapCommand extends Command
{
    protected $signature = 'seo:generate-sitemap
        {--path=public/sitemap.xml : Output path relative to the application base path}
        {--base-url= : Canonical site base URL (defaults to APP_URL)}
        {--gzip : Also write a gzip-compressed copy alongside the XML file}';

    protected $description = 'Generate an XML sitemap for all valid programmatic SEO pages';

    public function handle(SeoSlugRegistryService $registry, SitemapXmlBuilder $builder): int
    {
        $baseUrl = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');
        if ($baseUrl === '') {
            $this->error('Base URL is empty. Set APP_URL or pass --base-url=https://ai.suganta.com');

            return self::FAILURE;
        }

        $this->info('Collecting valid SEO slugs…');

        $entries = $registry->allValidEntries();
        $count = count($entries);

        if ($count === 0) {
            $this->warn('No valid SEO URLs found.');

            return self::FAILURE;
        }

        $changefreq = (string) config('seo.sitemap.changefreq', 'weekly');
        $defaultPriority = (float) config('seo.sitemap.default_priority', 0.7);
        $prioritiesByType = (array) config('seo.sitemap.priority_by_type', []);
        $lastmod = $builder->lastmodFromTimestamp();

        $urls = [];
        foreach ($entries as $entry) {
            $pageType = $entry['pageType'];
            $urls[] = [
                'loc' => $baseUrl.'/'.ltrim($entry['slug'], '/'),
                'lastmod' => $lastmod,
                'changefreq' => $changefreq,
                'priority' => (float) ($prioritiesByType[$pageType] ?? $defaultPriority),
            ];
        }

        $xml = $builder->build($urls);

        $relativePath = ltrim((string) $this->option('path'), '/\\');
        $outputPath = base_path($relativePath);

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $xml);

        $this->info("Wrote {$count} URLs to {$relativePath}");

        if ($this->option('gzip')) {
            $gzipPath = $outputPath.'.gz';
            $gz = gzopen($gzipPath, 'wb9');
            if ($gz === false) {
                $this->error("Failed to write gzip file: {$gzipPath}");

                return self::FAILURE;
            }
            gzwrite($gz, $xml);
            gzclose($gz);
            $this->info('Wrote '.basename($gzipPath));
        }

        $this->line("Sitemap URL: {$baseUrl}/".basename($outputPath));

        return self::SUCCESS;
    }
}
