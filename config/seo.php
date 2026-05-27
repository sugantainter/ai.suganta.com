<?php

return [

  /*
    |--------------------------------------------------------------------------
    | Programmatic SEO sitemap
    |--------------------------------------------------------------------------
    */
    'sitemap' => [
        'changefreq' => env('SEO_SITEMAP_CHANGEFREQ', 'weekly'),
        'default_priority' => (float) env('SEO_SITEMAP_DEFAULT_PRIORITY', 0.7),
        'priority_by_type' => [
            'comparison' => 0.9,
            'listicle' => 0.85,
            'alternatives' => 0.8,
            'guide' => 0.75,
            'education' => 0.75,
            'workflow' => 0.7,
        ],
        'listicle_year_suffix' => (int) env('SEO_SITEMAP_LISTICLE_YEAR', (int) date('Y')),
    ],

];
