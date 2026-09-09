<?php

namespace Tests\Feature;

use App\Models\CmsAdmin;
use App\Models\CmsPage;
use App\Services\BlockRenderer;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsExtensionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_settings_page_loads(): void
    {
        $admin = CmsAdmin::query()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_super' => true,
        ]);

        $this->actingAs($admin, 'cms')
            ->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_block_renderer_renders_hero(): void
    {
        $html = app(BlockRenderer::class)->render([
            'blocks' => [
                ['type' => 'hero', 'settings' => ['title' => 'Test Hero']],
            ],
        ]);

        $this->assertStringContainsString('Test Hero', $html);
    }

    public function test_page_builder_save_via_form(): void
    {
        $admin = CmsAdmin::query()->create([
            'name' => 'Admin',
            'email' => 'builder@test.com',
            'password' => bcrypt('password'),
            'is_super' => true,
        ]);

        $page = CmsPage::query()->create([
            'slug' => 'form-save-test',
            'title' => 'Form Save Test',
            'template' => 'content',
            'is_published' => true,
            'status' => 'published',
            'is_system' => false,
        ]);

        $payload = json_encode([
            'blocks' => [
                ['type' => 'text', 'settings' => ['content' => '<p>Saved via form</p>']],
            ],
        ]);

        $this->actingAs($admin, 'cms')
            ->post(route('admin.pages.builder.save', $page), [
                'builder_content' => $payload,
            ])
            ->assertRedirect(route('admin.pages.builder', $page));

        $page->refresh();
        $this->assertTrue($page->builder_enabled);
        $this->assertStringContainsString('Saved via form', strip_tags(app(\App\Services\BlockRenderer::class)->render($page->builder_content)));
    }

    public function test_page_builder_content_renders_on_dynamic_page(): void
    {
        CmsPage::query()->create([
            'slug' => 'builder-test',
            'title' => 'Builder Test',
            'template' => 'content',
            'is_system' => false,
            'builder_enabled' => true,
            'builder_content' => [
                'blocks' => [
                    ['type' => 'text', 'settings' => ['content' => '<p>Builder OK</p>']],
                ],
            ],
            'is_published' => true,
            'status' => 'published',
        ]);

        $this->get(route('pages.show', 'builder-test'))
            ->assertOk()
            ->assertSee('Builder OK');
    }

    public function test_search_route_works(): void
    {
        $this->get(route('search', ['q' => 'test']))->assertOk();
    }

    public function test_api_requires_token(): void
    {
        $this->getJson('/api/v1/pages')->assertUnauthorized();
    }

    public function test_api_returns_pages_with_token(): void
    {
        config(['cms.api_token' => 'test-token']);

        CmsPage::query()->create([
            'slug' => 'api-page',
            'title' => 'API Page',
            'template' => 'content',
            'is_published' => true,
            'status' => 'published',
        ]);

        $this->withToken('test-token')
            ->getJson('/api/v1/pages')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'api-page']);
    }

    public function test_menu_service_returns_empty_without_menu(): void
    {
        $links = app(MenuService::class)->linksForLocation('missing');

        $this->assertSame([], $links);
    }
}
