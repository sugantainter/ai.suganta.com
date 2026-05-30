<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google AdSense
    |--------------------------------------------------------------------------
    |
    | GOOGLE_ADSENSE_CLIENT_ID = your Publisher ID from AdSense (ca-pub-…)
    | Create display ad units in AdSense and paste each data-ad-slot below.
    |
    */

    'enabled' => filter_var(
        env('GOOGLE_ADSENSE_ENABLED', env('ADSENSE_ENABLED', false)),
        FILTER_VALIDATE_BOOLEAN
    ),

    'client_id' => env('GOOGLE_ADSENSE_CLIENT_ID', env('ADSENSE_CLIENT_ID', '')),

    'debug' => filter_var(env('GOOGLE_ADSENSE_DEBUG', false), FILTER_VALIDATE_BOOLEAN),

    'slots' => [
        'display' => env('GOOGLE_ADSENSE_DISPLAY_SLOT', ''),
        'after_hero' => env('GOOGLE_ADSENSE_SLOT_AFTER_HERO', ''),
        'in_content' => env('GOOGLE_ADSENSE_SLOT_IN_CONTENT', ''),
        'bottom' => env('GOOGLE_ADSENSE_SLOT_BOTTOM', ''),
    ],

];
