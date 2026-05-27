<?php

namespace Tests\Unit;

use App\Support\SugantaAuthResolver;
use Illuminate\Http\Request;
use Tests\TestCase;

class SugantaAuthResolverTest extends TestCase
{
    public function test_guest_when_no_credentials(): void
    {
        $resolver = new SugantaAuthResolver();
        $request = Request::create('https://ai.suganta.com/chatgpt-vs-gemini', 'GET');

        $nav = $resolver->resolveForPublicNav($request);

        $this->assertFalse($nav['authenticated']);
        $this->assertSame('', $nav['name']);
    }

    public function test_authenticated_from_request_attributes(): void
    {
        $resolver = new SugantaAuthResolver();
        $request = Request::create('https://ai.suganta.com/test', 'GET');
        $request->attributes->set('auth_user', [
            'name' => 'Jane Student',
            'email' => 'jane@example.com',
        ]);

        $nav = $resolver->resolveForPublicNav($request);

        $this->assertTrue($nav['authenticated']);
        $this->assertSame('Jane Student', $nav['name']);
        $this->assertSame('jane@example.com', $nav['email']);
        $this->assertSame('JS', $nav['initials']);
        $this->assertStringContainsString('ai.suganta.com', $nav['dashboard_url']);
    }

    public function test_dashboard_points_to_www_on_main_site(): void
    {
        $resolver = new SugantaAuthResolver();
        $request = Request::create('https://www.suganta.com/blog', 'GET');
        $request->attributes->set('auth_user', [
            'name' => 'Jane Student',
            'email' => 'jane@example.com',
        ]);

        $nav = $resolver->resolveForPublicNav($request);

        $this->assertStringContainsString('www.suganta.com/dashboard', $nav['dashboard_url']);
    }
}
