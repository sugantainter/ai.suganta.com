<?php

namespace App\Services\Seo;

class MetaGenerationService
{
    /**
     * Generate meta tags array dynamically for the page.
     */
    public function generate(string $slug, array $content, array $analysis, array $clusters): array
    {
        $appUrl = rtrim((string) config('app.url', 'https://ai.suganta.com'), '/');
        $canonical = "{$appUrl}/" . trim($slug, '/');

        // Compile keywords: focus keywords + generated clusters
        $keywordsList = array_unique(array_merge(
            [$analysis['pageType'] ?? 'ai tools'],
            $clusters,
            ['suganta ai', 'unified chat', 'cognitive engines']
        ));
        $keywords = implode(', ', array_slice($keywordsList, 0, 10));

        return [
            'title' => $content['hero']['title'] . ' - SuGanta AI',
            'description' => $content['hero']['description'],
            'keywords' => $keywords,
            'canonical' => $canonical,
            'robots' => 'index, follow',
        ];
    }
}
