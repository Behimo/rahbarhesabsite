<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\CmsPopup;
use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_target_a_popup_to_selected_pages(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.popups.store'), [
                'name' => 'خوش‌آمد',
                'title' => 'ثبت‌نام دوره',
                'body' => 'همین حالا ثبت‌نام کنید',
                'button_label' => 'مشاهده دوره‌ها',
                'button_url' => '/courses',
                'target_mode' => 'pages',
                'pages' => ['home'],
                'audience' => 'all',
                'frequency' => 'session',
                'delay_seconds' => 2,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.popups.index'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('ثبت‌نام دوره')
            ->assertSee('data-frequency="session"', false)
            ->assertSee('href="/courses"', false);

        $this->get(route('contact'))
            ->assertOk()
            ->assertDontSee('ثبت‌نام دوره');
    }

    public function test_popup_can_target_one_custom_page(): void
    {
        CmsPage::query()->create([
            'slug' => 'offer',
            'title' => 'پیشنهاد',
            'is_published' => true,
            'is_system' => false,
            'status' => 'published',
        ]);

        CmsPopup::query()->create([
            'name' => 'پیشنهاد',
            'title' => 'تخفیف ویژه',
            'body' => 'فقط این صفحه',
            'is_active' => true,
            'target_mode' => 'pages',
            'pages' => ['page:offer'],
            'frequency' => 'always',
            'audience' => 'all',
        ]);

        $this->get(route('pages.show', 'offer'))
            ->assertOk()
            ->assertSee('تخفیف ویژه');

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('تخفیف ویژه');
    }

    public function test_display_rules_match_a_path_prefix(): void
    {
        CmsPopup::query()->create([
            'name' => 'بلاگ',
            'title' => 'مقاله جدید',
            'body' => 'از بلاگ بخوانید',
            'is_active' => true,
            'target_mode' => 'rules',
            'rules' => ['match' => 'starts', 'path' => '/blog'],
            'frequency' => 'always',
            'audience' => 'all',
        ]);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('مقاله جدید');

        $this->get(route('home'))
            ->assertDontSee('مقاله جدید');
    }

    public function test_inactive_expired_and_audience_rules_hide_the_popup(): void
    {
        CmsPopup::query()->create([
            'name' => 'غیرفعال',
            'title' => 'پاپ‌آپ خاموش',
            'body' => 'نباید دیده شود',
            'is_active' => false,
            'target_mode' => 'pages',
            'pages' => ['home'],
            'frequency' => 'always',
            'audience' => 'all',
        ]);

        CmsPopup::query()->create([
            'name' => 'منقضی',
            'title' => 'پاپ‌آپ منقضی',
            'body' => 'تمام شده',
            'is_active' => true,
            'ends_at' => now()->subMinute(),
            'target_mode' => 'pages',
            'pages' => ['home'],
            'frequency' => 'always',
            'audience' => 'all',
        ]);

        CmsPopup::query()->create([
            'name' => 'اعضا',
            'title' => 'پاپ‌آپ اعضا',
            'body' => 'فقط واردشده‌ها',
            'is_active' => true,
            'target_mode' => 'pages',
            'pages' => ['home'],
            'frequency' => 'always',
            'audience' => 'auth',
        ]);

        $this->get(route('home'))
            ->assertDontSee('پاپ‌آپ خاموش')
            ->assertDontSee('پاپ‌آپ منقضی')
            ->assertDontSee('پاپ‌آپ اعضا');

        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertSee('پاپ‌آپ اعضا')
            ->assertDontSee('پاپ‌آپ خاموش');
    }

    public function test_lower_priority_popup_wins_when_several_match(): void
    {
        CmsPopup::query()->create([
            'name' => 'دوم',
            'title' => 'پاپ‌آپ دوم',
            'body' => 'بعدی',
            'is_active' => true,
            'target_mode' => 'rules',
            'rules' => ['match' => 'all'],
            'frequency' => 'always',
            'audience' => 'all',
            'sort_order' => 5,
        ]);

        CmsPopup::query()->create([
            'name' => 'اول',
            'title' => 'پاپ‌آپ اول',
            'body' => 'اولویت بالاتر',
            'is_active' => true,
            'target_mode' => 'rules',
            'rules' => ['match' => 'all'],
            'frequency' => 'always',
            'audience' => 'all',
            'sort_order' => 1,
        ]);

        $this->get(route('home'))
            ->assertSee('پاپ‌آپ اول')
            ->assertDontSee('پاپ‌آپ دوم');
    }

    public function test_unsafe_button_url_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from(route('admin.popups.create'))
            ->post(route('admin.popups.store'), [
                'name' => 'ناامن',
                'title' => 'عنوان',
                'body' => 'متن',
                'button_label' => 'کلیک',
                'button_url' => 'javascript:alert(1)',
                'target_mode' => 'pages',
                'pages' => ['home'],
                'audience' => 'all',
                'frequency' => 'once',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.popups.create'))
            ->assertSessionHasErrors('button_url');

        $this->assertDatabaseCount('cms_popups', 0);
    }

    public function test_editor_can_open_popups_and_shop_manager_cannot(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $editor = User::factory()->create();
        $editor->assignRole(AccessCatalog::ROLE_EDITOR);

        $shop = User::factory()->create();
        $shop->assignRole(AccessCatalog::ROLE_SHOP_MANAGER);

        $this->actingAs($editor)
            ->get(route('admin.popups.index'))
            ->assertOk()
            ->assertSee('پاپ‌آپ جدید');

        $this->actingAs($shop)
            ->get(route('admin.popups.index'))
            ->assertForbidden();
    }
}
