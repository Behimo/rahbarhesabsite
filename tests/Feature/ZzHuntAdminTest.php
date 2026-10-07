<?php

namespace Tests\Feature;

use App\Models\CmsMenu;
use App\Models\CmsPage;
use App\Models\CmsPopup;
use App\Models\CmsSetting;
use App\Models\User;
use App\Services\ImportExportService;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ZzHuntAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_popup_text_is_double_escaped_on_public_pages(): void
    {
        CmsPopup::query()->create([
            'name' => 'p',
            'title' => 'T & Co',
            'body' => "line1\nline2",
            'is_active' => true,
            'target_mode' => 'pages',
            'pages' => ['home'],
            'frequency' => 'always',
            'audience' => 'all',
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();

        $this->assertStringContainsString('&amp;amp;', $response->getContent(), 'title is escaped twice');
        $this->assertStringContainsString('&lt;br /&gt;', $response->getContent(), 'nl2br output escaped again, shown literally');
    }

    public function test_menu_item_accepts_javascript_scheme_url(): void
    {
        $menu = CmsMenu::query()->create(['slug' => 'primary', 'name' => 'Primary', 'location' => 'primary']);
        $menu->allItems()->create([
            'label' => 'Bad',
            'type' => 'custom',
            'url' => 'javascript:alert(document.cookie)',
            'target' => '_self',
            'sort_order' => 0,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="javascript:alert(document.cookie)"', false);
    }

    public function test_import_updates_settings_row_but_not_the_setting_cache(): void
    {
        CmsSetting::set('contact_email', 'old@example.test');
        $cached = CmsSetting::get('contact_email');
        $this->assertSame('old@example.test', $cached);

        app(ImportExportService::class)->import([
            'settings' => ['contact_email' => 'new@example.test'],
        ]);

        $this->assertSame('old@example.test', CmsSetting::get('contact_email'), 'cache not invalidated by import');
        $this->assertDatabaseHas('cms_settings', ['key' => 'contact_email', 'value' => 'new@example.test']);
    }

    public function test_import_is_not_transactional_and_applies_settings_before_failing(): void
    {
        $threw = false;

        try {
            app(ImportExportService::class)->import([
                'settings' => ['contact_email' => 'partial@example.test'],
                'pages' => [['title' => 'missing slug']],
            ]);
        } catch (\Throwable $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'malformed page row should blow up');
        $this->assertDatabaseHas('cms_settings', ['key' => 'contact_email', 'value' => 'partial@example.test']);
        $this->assertDatabaseMissing('cms_pages', ['title' => 'missing slug']);
    }

    public function test_builder_save_force_publishes_a_draft_page(): void
    {
        $admin = $this->makeAdmin();

        $page = CmsPage::query()->create([
            'slug' => 'draft-page',
            'title' => 'Draft',
            'is_published' => false,
            'status' => 'draft',
            'is_system' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pages.builder.save', $page), [
                'builder_content' => ['blocks' => []],
            ])
            ->assertRedirect(route('admin.pages.builder', $page));

        $page->refresh();
        $this->assertTrue((bool) $page->is_published, 'draft was published by builder save');
        $this->assertSame('published', $page->status);
    }

    public function test_product_dashboard_upload_keeps_the_client_supplied_extension(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();

        $im = imagecreatetruecolor(4, 4);
        ob_start();
        imagepng($im);
        $polyglot = ob_get_clean()."\n<?php echo 'owned';";

        $blocked = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($blocked, $polyglot);
        $this->actingAs($admin)->post(route('admin.products.store'), [
            'slug' => 'shell-course',
            'title' => 'Shell',
            'accent' => 'orange',
            'dashboard_image_file' => new UploadedFile($blocked, 'shell.php', 'image/png', null, true),
        ])->assertSessionHasErrors('dashboard_image_file');

        $accepted = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($accepted, $polyglot);
        $this->actingAs($admin)->post(route('admin.products.store'), [
            'slug' => 'shell-course',
            'title' => 'Shell',
            'accent' => 'orange',
            'dashboard_image_file' => new UploadedFile($accepted, 'shell.pht', 'image/png', null, true),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists('cms/products/shell-course-dashboard.pht');
    }

    public function test_editor_role_can_store_script_that_is_rendered_raw_on_public_blog(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole(AccessCatalog::ROLE_EDITOR);

        $this->actingAs($editor)
            ->post(route('admin.posts.store'), [
                'title' => 'XSS Post',
                'slug' => 'xss-post',
                'body' => '<img src=x onerror="alert(document.domain)">',
                'is_published' => '1',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->get(route('blog.show', 'xss-post'))
            ->assertOk()
            ->assertSee('<img src=x onerror="alert(document.domain)">', false);
    }
}
