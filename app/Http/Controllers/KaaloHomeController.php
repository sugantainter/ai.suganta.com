<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthCheck;
use App\Support\SugantaAuthResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KaaloHomeController extends Controller
{
    public function __construct(
        protected SugantaAuthResolver $authResolver,
    ) {}

    /**
     * Public marketing home for guests; authenticated users enter the chat app.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $navUser = $this->authResolver->resolveForPublicNav($request);

        if ($navUser['authenticated']) {
            return app(AuthCheck::class)->handle($request, fn () => view('spa'));
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        $config = config('kaalo_home');
        $seoConfig = (array) ($config['seo'] ?? []);

        return view('kaalo.home', [
            'page' => $config,
            'seo' => [
                'title' => $seoConfig['title'] ?? 'Kaalo AI by SuGanta',
                'description' => $seoConfig['description'] ?? '',
                'keywords' => $seoConfig['keywords'] ?? '',
                'robots' => $seoConfig['robots'] ?? 'index, follow',
                'canonical' => $baseUrl.'/',
            ],
            'schema' => [
                'software' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'SoftwareApplication',
                    'name' => 'Kaalo AI by SuGanta',
                    'applicationCategory' => 'EducationalApplication',
                    'operatingSystem' => 'Web',
                    'description' => $seoConfig['description'] ?? '',
                    'url' => $baseUrl.'/',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '499',
                        'priceCurrency' => 'INR',
                    ],
                ],
            ],
            'breadcrumbs' => [],
        ]);
    }
}
