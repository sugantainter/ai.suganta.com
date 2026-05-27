<?php

/**
 * Public navigation configuration.
 * All URLs point to www.suganta.com unless they are local routes.
 */

$base = 'https://www.suganta.com';

return [

    'header' => [
        'logo' => [
            'src'  => '/logo/Su250.png',
            'alt'  => 'SuGanta',
            'href' => $base,
        ],

        'nav' => [
            [
                'label'    => 'Home',
                'href'     => $base . '/',
                'external' => true,
            ],
            [
                'label'    => 'Find Teachers',
                'href'     => $base . '/teachers',
                'external' => true,
            ],
            [
                'label'    => 'Find Institutes',
                'href'     => $base . '/institutes',
                'external' => true,
            ],
            [
                'label'    => 'Kaalo Ai',
                'href'     => 'https://ai.suganta.com',
                'external' => true,
            ],
          
            [
                'label'    => 'Blog',
                'href'     => $base . '/blog',
                'external' => true,
            ],
            [
                'label'    => 'Store',
                'href'     => $base . '/store',
                'external' => true,
            ],
        ],

        'cta' => [
            'login' => [
                'label' => 'Log in',
                'route' => 'login',
                'href'  => $base . '/login',
            ],
            'register' => [
                'label' => 'Get started',
                'route' => 'register',
                'href'  => $base . '/register',
            ],
        ],
    ],

    'authenticated' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'href'  => $base . '/dashboard',
        ],
        'ai_dashboard' => [
            'label' => 'Dashboard',
            'href'  => 'https://ai.suganta.com/',
        ],
    ],

    'footer' => [
        'tagline' => 'SuGanta is an all-in-one edtech platform that connects learners, teachers, and institutes seamlessly. It simplifies access to quality education, personalized learning, and expert guidance. ',

        'columns' => [
            [
                'heading' => 'Platform',
                'links'   => [
                    ['label' => 'Find Teachers',  'href' => $base . '/teachers'],
                    ['label' => 'Find Institutes', 'href' => $base . '/institutes'],
                    ['label' => 'Kaalo Ai', 'href' => 'https://ai.suganta.com'],
                    ['label' => 'Find Tutors India ', 'href' => $base . '/find-tutors-india'],
                ],
            ],
            [
                'heading' => 'Company',
                'links'   => [
                    ['label' => 'About Us',   'href' => $base . '/about'],
                    ['label' => 'Blog',       'href' => $base . '/blog'],
                    ['label' => 'Careers',    'href' => $base . '/careers'],
                    ['label' => 'Contact Us', 'href' => '/contact'],
                ],
            ],
            [
                'heading' => 'Support',
                'links'   => [
                    ['label' => 'Help Center',      'href' => $base . '/help-center'],
                    ['label' => 'Community Forum',  'href' => $base . '/community-guidelines'],
                ],
            ],
            [
                'heading' => 'Legal',
                'links'   => [
                    ['label' => 'Privacy Policy',    'href' => $base . '/privacy-and-policies'],
                    ['label' => 'Terms of Service',  'href' => $base . '/terms-and-conditions'],
                    ['label' => 'Cookie Policy',     'href' => $base . '/cookies'],
                    ['label' => 'Safety Policy',     'href' => $base . '/safety'],
                ],
            ],
        ],

        'app_links' => [
            [
                'label' => 'Download on the App Store',
                'href'  => $base . '/app',
                'store' => 'apple',
            ],
            [
                'label' => 'Get it on Google Play',
                'href'  => $base . '/app',
                'store' => 'google',
            ],
        ],
    ],

];
