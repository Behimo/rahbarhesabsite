<?php

namespace Tests\Feature;

use App\Models\CmsMenu;
use App\Models\CmsPage;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsExtensionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_settings_page_loads(): void
    {
        $admin = $this->makeAdmin(['email' => 'admin@test.com']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_home_page_renders_site_layout(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rahbar-about', false);
    }

    public function test_admin_can_save_home_content(): void
    {
        $admin = $this->makeAdmin(['email' => 'home@test.com']);

        $this->actingAs($admin)
            ->put(route('admin.home.update'), [
                'heading' => 'عنوان تست صفحه اصلی',
                'faqs' => [
                    ['q' => 'سوال تست', 'a' => 'پاسخ تست', 'href' => ''],
                ],
            ])
            ->assertRedirect();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('عنوان تست صفحه اصلی');
    }

    public function test_page_builder_renders_saved_blocks_on_custom_page(): void
    {
        CmsPage::query()->create([
            'slug' => 'builder-page',
            'title' => 'صفحه بلوکی',
            'template' => 'content',
            'is_system' => false,
            'builder_enabled' => true,
            'builder_content' => [
                'blocks' => [
                    ['type' => 'text', 'settings' => ['content' => '<p>متن بلوک صفحه</p>']],
                ],
            ],
            'is_published' => true,
            'status' => 'published',
        ]);

        $this->get(route('pages.show', 'builder-page'))
            ->assertOk()
            ->assertSee('متن بلوک صفحه');
    }

    public function test_home_uses_saved_page_builder_sections(): void
    {
        CmsPage::query()->create([
            'slug' => 'home',
            'title' => 'صفحه اصلی',
            'template' => 'system',
            'is_system' => true,
            'builder_enabled' => true,
            'builder_content' => [
                'blocks' => [
                    ['type' => 'hero', 'settings' => ['title' => 'عنوان سکشن تست']],
                ],
            ],
            'is_published' => true,
            'status' => 'published',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('عنوان سکشن تست')
            ->assertSee('rahbar-about', false);
    }

    public function test_custom_page_renders_body_html(): void
    {
        CmsPage::query()->create([
            'slug' => 'custom-page',
            'title' => 'صفحه سفارشی',
            'template' => 'content',
            'is_system' => false,
            'content' => ['body_html' => '<p>متن صفحه سفارشی</p>'],
            'is_published' => true,
            'status' => 'published',
        ]);

        $this->get(route('pages.show', 'custom-page'))
            ->assertOk()
            ->assertSee('متن صفحه سفارشی');
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

    public function test_nested_menu_is_stored_and_rendered_on_the_site(): void
    {
        $admin = $this->makeAdmin(['email' => 'menu@test.com']);

        $menu = CmsMenu::query()->create([
            'name' => 'اصلی',
            'slug' => 'primary-nav',
            'location' => 'primary',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.menus.tree', $menu), [
                'tree' => [
                    [
                        'label' => 'آموزش',
                        'type' => 'custom',
                        'url' => '/courses',
                        'target' => '_self',
                        'children' => [
                            [
                                'label' => 'حسابداری',
                                'type' => 'custom',
                                'url' => '/courses/accounting',
                                'children' => [
                                    [
                                        'label' => 'سطح سوم منوی آزمایشی',
                                        'type' => 'custom',
                                        'url' => '/courses/accounting/basic',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('tree.0.children.0.children.0.label', 'سطح سوم منوی آزمایشی');

        $this->actingAs($admin)
            ->get(route('admin.menus.edit', $menu))
            ->assertOk()
            ->assertSee('id="menu-builder"', false)
            ->assertSee('initialTree', false);

        $links = app(MenuService::class)->linksForLocation('primary');

        $this->assertSame('سطح سوم منوی آزمایشی', $links[0]['children'][0]['children'][0]['label']);
        $this->assertSame('/courses/accounting/basic', $links[0]['children'][0]['children'][0]['href']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('سطح سوم منوی آزمایشی', false)
            ->assertSee('nav-dropdown-sub', false);
    }
}
