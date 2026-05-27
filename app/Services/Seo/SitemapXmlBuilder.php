<?php

namespace App\Services\Seo;

use Illuminate\Support\Carbon;

class SitemapXmlBuilder
{
    /**
     * @param  list<array{loc: string, lastmod?: string, changefreq?: string, priority?: float}>  $urls
     */
    public function build(array $urls): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';

            if (! empty($url['lastmod'])) {
                $lines[] = '    <lastmod>'.htmlspecialchars($url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</lastmod>';
            }

            if (! empty($url['changefreq'])) {
                $lines[] = '    <changefreq>'.htmlspecialchars($url['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</changefreq>';
            }

            if (isset($url['priority'])) {
                $priority = number_format((float) $url['priority'], 1, '.', '');
                $lines[] = '    <priority>'.$priority.'</priority>';
            }

            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    public function lastmodFromTimestamp(?int $timestamp = null): string
    {
        return Carbon::createFromTimestamp($timestamp ?? time())
            ->utc()
            ->format('Y-m-d');
    }
}
