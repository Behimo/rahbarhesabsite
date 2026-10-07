<?php

namespace Tests\Feature;

use App\Models\CmsMenu;
use App\Models\CmsPage;
use App\Models\CmsPopup;
use App\Models\CmsSetting;
use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression tests for admin content bugs (T7–T11, T15, T16 in docs/BUG-HUNT-REPORT.md).
 *
 * These encode the DESIRED behaviour (single escaping, safe menu URLs, atomic
 * and cache-flushing imports, draft stays draft, no executable uploads, raw
 * HTML escaped for non-admins, malformed builder content rejected).
 */
class AdminContentRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_popup_text_is_single_escaped_on_public_pages(): void
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

        $response->assertSee('T & Co');
        $response->assertSee('line1<br />line2', false);
        $response->assertDontSee('&amp;amp;', false, 'title is escaped twice');
        $response->assertDontSee('&lt;br /&gt;', false, 'nl2br output escaped again, shown literally');
    }

    public function test_menu_item_rejects_javascript_scheme_url(): void
    {
        $admin = $this->makeAdmin();
        $menu = CmsMenu::query()->create(['slug' => 'primary', 'name' => 'Primary', 'location' => 'primary']);

        $this->actingAs($admin)
            ->post(route('admin.menus.tree', $menu), [
                'tree' => [
                    [
                        'label' => 'Bad',
                        'type' => 'custom',
                        'url' => 'javascript:alert(document.cookie)',
                        'target' => '_self',
                        'sort_order' => 0,
                    ],
                ],
            ])
            ->assertInvalid();

        $this->assertDatabaseMissing('cms_menu_items', [
            'menu_id' => $menu->id,
            'url' => 'javascript:alert(document.cookie)',
        ]);
    }

    public function test_import_flushes_the_setting_cache(): void
    {
        CmsSetting::set('contact_email', 'old@example.test');
        $this->assertSame('old@example.test', CmsSetting::get('contact_email'));

        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.import'), [
                'export_file' => UploadedFile::fake()->createWithContent(
                    'import.json',
                    json_encode(['settings' => ['contact_email' => 'new@example.test']])
                ),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_settings', ['key' => 'contact_email', 'value' => 'new@example.test']);
        $this->assertSame(
            'new@example.test',
            CmsSetting::get('contact_email'),
            'Setting cache was not invalidated by import.'
        );
    }

    public function test_malformed_import_is_rejected_without_partial_changes(): void
    {
        CmsSetting::set('contact_email', 'old@example.test');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.import'), [
                'export_file' => UploadedFile::fake()->createWithContent(
                    'import.json',
                    json_encode([
                        'settings' => ['contact_email' => 'partial@example.test'],
                        'pages' => [['title' => 'missing slug']],
                    ])
                ),
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('cms_settings', [
            'key' => 'contact_email',
            'value' => 'partial@example.test',
        ]);
        $this->assertSame('old@example.test', CmsSetting::get('contact_email'));
        $this->assertDatabaseMissing('cms_pages', ['title' => 'missing slug']);
    }

    public function test_builder_save_does_not_publish_a_draft_page(): void
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
        $this->assertFalse((bool) $page->is_published, 'Builder save force-published a draft.');
        $this->assertSame('draft', $page->status);
    }

    public function test_product_upload_never_stores_an_executable_looking_extension(): void
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
        ]);

        $executable = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phtm', 'phar', 'pht', 'cgi'];
        $stored = array_values(array_filter(
            Storage::disk('public')->allFiles(),
            fn (string $file) => in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $executable, true)
        ));

        $this->assertSame(
            [],
            $stored,
            'Upload kept a client-supplied executable-lookable extension: '.implode(', ', $stored)
        );
    }

    public function test_raw_script_from_editor_role_is_escaped_on_the_public_blog(): void
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
            ->assertDontSee('<img src=x onerror=', false, 'Raw script from a non-admin role reached the page unescaped.');
    }

    public function test_malformed_builder_blocks_are_rejected_and_editor_stays_usable(): void
    {
        $admin = $this->makeAdmin();

        $page = CmsPage::query()->create([
            'slug' => 'broken-builder',
            'title' => 'Broken',
            'is_published' => true,
            'status' => 'published',
            'is_system' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.pages.builder.save', $page), [
                'builder_content' => ['blocks' => ['not-a-block']],
            ])
            ->assertInvalid();

        $this->actingAs($admin)
            ->get(route('admin.pages.builder', $page))
            ->assertOk();
    }
}
