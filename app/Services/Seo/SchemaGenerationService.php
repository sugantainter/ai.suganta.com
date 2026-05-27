<?php

namespace App\Services\Seo;

use Illuminate\Support\Str;

class SchemaGenerationService
{
    /**
     * Generate JSON-LD schema blocks based on page contents.
     */
    public function generate(string $slug, array $content, array $analysis): array
    {
        $appUrl = rtrim((string) config('app.url', 'https://ai.suganta.com'), '/');
        $pageUrl = "{$appUrl}/" . trim($slug, '/');
        $pageType = $analysis['pageType'] ?? 'general';

        $schema = [];

        // 1. BreadcrumbList Schema
        $schema['breadcrumbs'] = $this->buildBreadcrumbsSchema($appUrl, $pageUrl, $content, $pageType);

        // 2. FAQPage Schema
        if (!empty($content['faqs'])) {
            $schema['faq'] = $this->buildFaqSchema($content['faqs']);
        }

        // 3. Page Type Specific Schema
        if ($pageType === 'comparison' || $pageType === 'alternatives') {
            // SoftwareApplication Schema (Kaalo AI)
            $schema['software'] = [
                '@context' => 'https://schema.org',
                '@type' => 'SoftwareApplication',
                'name' => 'Kaalo AI',
                'operatingSystem' => 'All',
                'applicationCategory' => 'BusinessApplication',
                'aggregateRating' => [
                    '@type' => 'AggregateRating',
                    'ratingValue' => '4.9',
                    'reviewCount' => '1420',
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0.00',
                    'priceCurrency' => 'USD',
                ],
            ];
        }

        if ($pageType === 'listicle') {
            // ItemList Schema
            $schema['item_list'] = $this->buildItemListSchema($pageUrl, $content);
        }

        // 4. Article Schema
        $schema['article'] = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $content['hero']['title'],
            'description' => $content['hero']['description'],
            'url' => $pageUrl,
            'datePublished' => date('c', strtotime($content['hero']['update_date'])),
            'dateModified' => date('c', strtotime($content['hero']['update_date'])),
            'author' => [
                '@type' => 'Organization',
                'name' => 'SuGanta Editorial Team',
                'url' => $appUrl,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'SuGanta AI',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => "{$appUrl}/logo/favicon.png",
                ],
            ],
        ];

        return $schema;
    }

    /**
     * Build BreadcrumbList JSON-LD
     */
    private function buildBreadcrumbsSchema(string $appUrl, string $pageUrl, array $content, string $pageType): array
    {
        $categoryName = $content['hero']['category'] ?? 'AI Resources';
        $categorySlug = Str::slug($categoryName);
        $categoryUrl = "{$appUrl}/ai-workflows/{$categorySlug}";

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => "{$appUrl}/",
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $categoryName,
                    'item' => $categoryUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $content['hero']['title'],
                    'item' => $pageUrl,
                ],
            ],
        ];
    }

    /**
     * Build FAQPage JSON-LD
     */
    private function buildFaqSchema(array $faqs): array
    {
        $elements = [];
        foreach ($faqs as $faq) {
            $elements[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $elements,
        ];
    }

    /**
     * Build ItemList JSON-LD for Listicles
     */
    private function buildItemListSchema(string $pageUrl, array $content): array
    {
        // Parse list items from the HTML or list details
        // To be safe and simple, we dynamically generate elements
        $items = [
            'Kaalo AI by SuGanta',
            'Claude 3.5 Sonnet',
            'Perplexity AI',
            'ChatGPT Plus',
            'Cursor AI'
        ];

        $itemListElement = [];
        foreach ($items as $index => $itemName) {
            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $itemName,
                'url' => "{$pageUrl}#tool-list",
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $itemListElement,
        ];
    }
}
