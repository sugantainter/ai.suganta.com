<?php

namespace Tests\Feature;

use Tests\TestCase;

class SharePublicLayoutTest extends TestCase
{
    public function test_share_route_renders_public_layout_with_vue_mount(): void
    {
        $response = $this->get('/share/demo-token-abc');

        $response->assertOk();
        $response->assertSee('id="app"', false);
        $response->assertSee('public-nav', false);
        $response->assertSee('Shared conversation', false);
        $response->assertSee('Shared Chat | Kaalo AI', false);
        $response->assertDontSee('id="app"></div>', false);
    }
}
