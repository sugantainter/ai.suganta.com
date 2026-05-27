<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateSeoSitemapCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $path = base_path('public/sitemap-test.xml');
        if (File::exists($path)) {
            File::delete($path);
        }

        $chunk = base_path('public/sitemap-1.xml.gz');
        if (File::exists($chunk)) {
            File::delete($chunk);
        }

        parent::tearDown();
    }

    public function test_command_generates_valid_sitemap_xml(): void
    {
        $this->artisan('seo:generate-sitemap', [
            '--path' => 'public/sitemap-test.xml',
            '--base-url' => 'https://ai.suganta.com',
            '--limit' => 10,
        ])
            ->assertSuccessful();

        $path = base_path('public/sitemap-test.xml');
        $this->assertFileExists($path);

        $xml = File::get($path);
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $xml);
        $this->assertStringContainsString('https://ai.suganta.com/sitemap-1.xml.gz</loc>', $xml);

        $chunkPath = base_path('public/sitemap-1.xml.gz');
        $this->assertFileExists($chunkPath);
    }
}
