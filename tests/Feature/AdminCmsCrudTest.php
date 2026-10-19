<?php

namespace Tests\Feature;

use App\Models\CmsMedia;
use App\Models\CmsMenu;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsRedirect;
use App\Models\CmsSetting;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\ShopProduct;
use App\Models\Tag;
use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCmsCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_duplicate_soft_delete_restore_and_force_destroy_post(): void
    {
        $admin = $this->makeAdmin();
        $post = CmsPost::factory()->published()->create(['title' => 'مقاله اصلی']);

        $this->actingAs($admin)
            ->post(route('admin.posts.duplicate', $post))
            ->assertRedirect();

        $clone = CmsPost::query()->where('title', 'مقاله اصلی (کپی)')->firstOrFail();
        $this->assertFalse($clone->is_published);
        $this->assertSame(CmsPost::STATUS_DRAFT, $clone->status);

        $this->actingAs($admin)
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertSoftDeleted('cms_posts', ['id' => $post->id]);

        $this->actingAs($admin)
            ->post(route('admin.posts.restore', $post->id))
            ->assertRedirect(route('admin.posts.edit', $post));

        $this->assertNull($post->fresh()->deleted_at);

        $this->actingAs($admin)->delete(route('admin.posts.destroy', $post));
        $this->actingAs($admin)
            ->delete(route('admin.posts.force-destroy', $post->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('cms_posts', ['id' => $post->id]);
    }

    public function test_editor_can_destroy_page_but_not_system_page(): void
    {
        $editor = $this->makeEditor();
        $page = CmsPage::query()->create([
            'slug' => 'custom-page',
            'title' => 'صفحه سفارشی',
            'is_published' => true,
            'is_system' => false,
            'status' => 'published',
        ]);
        $system = CmsPage::query()->create([
            'slug' => 'about-us',
            'title' => 'درباره ما',
            'is_published' => true,
            'is_system' => true,
            'status' => 'published',
        ]);

        $this->actingAs($editor)
            ->from(route('admin.pages.edit', $system))
            ->delete(route('admin.pages.destroy', $system))
            ->assertRedirect(route('admin.pages.edit', $system))
            ->assertSessionHasErrors('page');

        $this->actingAs($editor)
            ->delete(route('admin.pages.destroy', $page))
            ->assertRedirect(route('admin.pages.index'));

        $this->assertSoftDeleted('cms_pages', ['id' => $page->id]);
    }

    public function test_admin_can_manage_course_sections_and_lessons(): void
    {
        $admin = $this->makeAdmin();
        $product = ShopProduct::factory()->course()->create();
        Course::factory()->create([
            'shop_product_id' => $product->id,
            'instructor_id' => null,
        ]);
        $product = $product->fresh('course');

        $this->actingAs($admin)
            ->from(route('admin.courses.edit', $product))
            ->post(route('admin.courses.sections.store', $product), [
                'title' => 'فصل اول',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.courses.edit', $product))
            ->assertSessionHas('success');

        $section = CourseSection::query()->where('course_id', $product->course->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.courses.lessons.store', [$product, $section]), [
                'title' => 'درس اول',
                'slug' => 'lesson-one',
                'sort_order' => 1,
                'is_free_preview' => 1,
            ])
            ->assertRedirect(route('admin.courses.edit', $product));

        $lesson = CourseLesson::query()->where('section_id', $section->id)->firstOrFail();
        $this->assertTrue($lesson->is_free_preview);

        $this->actingAs($admin)
            ->put(route('admin.courses.lessons.update', [$product, $lesson]), [
                'title' => 'درس اول ویرایش‌شده',
                'slug' => 'lesson-one',
                'sort_order' => 1,
                'is_free_preview' => 0,
            ])
            ->assertRedirect(route('admin.courses.edit', $product));

        $this->actingAs($admin)
            ->delete(route('admin.courses.lessons.destroy', [$product, $lesson]))
            ->assertRedirect(route('admin.courses.edit', $product));

        $this->assertDatabaseMissing('course_lessons', ['id' => $lesson->id]);

        $this->actingAs($admin)
            ->delete(route('admin.courses.sections.destroy', [$product, $section]))
            ->assertRedirect(route('admin.courses.edit', $product));

        $this->assertDatabaseMissing('course_sections', ['id' => $section->id]);
    }

    public function test_admin_can_crud_menu_tag_message_media_settings_and_redirect(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.menus.store'), [
                'name' => 'منوی اصلی',
                'slug' => 'main-menu',
                'location' => 'header',
            ])
            ->assertRedirect();

        $menu = CmsMenu::query()->where('slug', 'main-menu')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.menus.update', $menu), [
                'name' => 'منوی به‌روز',
                'slug' => 'main-menu',
                'location' => 'header',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.tags.index'))
            ->post(route('admin.tags.store'), [
                'name' => 'مالیات',
                'slug' => 'tax',
            ])
            ->assertRedirect(route('admin.tags.index'));

        $tag = Tag::query()->where('slug', 'tax')->firstOrFail();
        $this->actingAs($admin)
            ->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'));

        $message = ContactMessage::query()->create([
            'name' => 'کاربر',
            'email' => 'user@example.com',
            'phone' => '09121111111',
            'subject' => 'موضوع',
            'message' => 'متن پیام تماس آزمایشی.',
            'is_read' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.messages.show', $message))
            ->assertOk();

        $this->assertTrue($message->fresh()->is_read);

        $this->actingAs($admin)
            ->delete(route('admin.messages.destroy', $message))
            ->assertRedirect(route('admin.messages.index'));

        Storage::fake('public');
        $media = CmsMedia::query()->create([
            'disk' => 'public',
            'path' => 'cms/test.jpg',
            'filename' => 'test.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
            'alt' => 'test',
        ]);
        Storage::disk('public')->put('cms/test.jpg', 'fake');

        $this->actingAs($admin)
            ->from(route('admin.media.index'))
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseMissing('cms_media', ['id' => $media->id]);

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'contact_email' => 'info@example.com',
                'contact_phone' => '02112345678',
                'social_linkedin' => 'https://linkedin.com/company/example',
                'social_telegram' => 'https://t.me/example',
                'social_instagram' => 'https://instagram.com/example',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('info@example.com', CmsSetting::get('contact_email'));

        $this->actingAs($admin)
            ->post(route('admin.redirects.store'), [
                'from_path' => '/old-path',
                'to_path' => '/new-path',
                'status_code' => 301,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $redirect = CmsRedirect::query()->where('from_path', '/old-path')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.redirects.update', $redirect), [
                'from_path' => '/old-path',
                'to_path' => '/newer-path',
                'status_code' => 302,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->delete(route('admin.redirects.destroy', $redirect))
            ->assertRedirect();

        $this->assertDatabaseMissing('cms_redirects', ['id' => $redirect->id]);

        $this->actingAs($admin)
            ->delete(route('admin.menus.destroy', $menu))
            ->assertRedirect();

        $this->assertDatabaseMissing('cms_menus', ['id' => $menu->id]);
    }

    public function test_shop_manager_cannot_manage_posts_or_settings(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $manager = User::factory()->create();
        $manager->assignRole(AccessCatalog::ROLE_SHOP_MANAGER);
        $post = CmsPost::factory()->published()->create();

        $this->actingAs($manager)
            ->get(route('admin.posts.index'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('admin.posts.destroy', $post))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    private function makeEditor(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole(AccessCatalog::ROLE_EDITOR);

        return $editor;
    }
}
