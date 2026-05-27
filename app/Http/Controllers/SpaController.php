<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaController extends Controller
{
    public function index(): View
    {
        return view('spa');
    }

    public function settings(): View
    {
        return view('spa');
    }

    public function fallback(): View
    {
        return view('spa');
    }

    /**
     * Public shared chat — SuGanta nav/footer + Vue read-only viewer.
     */
    public function share(Request $request, string $shareToken): View
    {
        $token = trim($shareToken);
        $baseUrl = rtrim((string) config('app.url'), '/');
        $canonical = $baseUrl.'/share/'.rawurlencode($token);

        return view('share.public', [
            'mainFullWidth' => true,
            'viteEntries' => [
                'resources/css/seo.css',
                'resources/css/app.css',
                'resources/js/seo.js',
                'resources/js/app.js',
            ],
            'seo' => [
                'title' => 'Shared Chat | Kaalo AI by SuGanta',
                'description' => 'Read a shared AI conversation on Kaalo AI. View-only public page—sign in to start your own chat with multiple models.',
                'keywords' => 'shared ai chat, public conversation, Kaalo AI, SuGanta',
                'robots' => 'index, follow',
                'canonical' => $canonical,
                'og_type' => 'article',
                'twitter_card' => 'summary',
            ],
            'breadcrumbs' => [
                'Kaalo AI' => url('/'),
                'Shared Chat' => $canonical,
            ],
            'schema' => [],
        ]);
    }
}
