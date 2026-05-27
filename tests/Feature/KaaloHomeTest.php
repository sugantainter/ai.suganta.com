<?php

namespace Tests\Feature;

use Tests\TestCase;

class KaaloHomeTest extends TestCase
{
    public function test_guest_sees_kaalo_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Kaalo AI — all popular models in one place', false);
        $response->assertSee('Learning-first workspace', false);
        $response->assertSee('Get premium AI access in one simple plan', false);
        $response->assertSee('Frequently asked questions', false);
        $response->assertDontSee('id="app"', false);
    }

    public function test_guest_landing_includes_pricing_plans(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('₹499', false)
            ->assertSee('₹999', false);
    }

    public function test_conversation_route_requires_auth(): void
    {
        $this->get('/c/test-conversation-id')
            ->assertRedirect();
    }
}
