<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CmsPost;
use App\Models\CmsSidebar;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopProduct;
use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SidebarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_shows_category_items_only_on_selected_pages(): void
    {
        $parent = Category::factory()->product()->create(['name' => 'مالیات سایدبار', 'slug' => 'tax-side']);
        $child = Category::factory()->childOf($parent)->create(['name' => 'ارزش افزوده سایدبار', 'slug' => 'vat-side']);

        $included = ShopProduct::factory()->course()->create(['title' => 'دوره داخل دسته سایدبار']);
        $included->syncCategories([$child->id], $child->id);

        $excluded = ShopProduct::factory()->course()->create(['title' => 'دوره بیرون دسته سایدبار']);

        CmsSidebar::query()->create([
            'name' => 'دسته مالیات',
            'title' => 'دوره‌های مالیات',
            'is_active' => true,
            'target_mode' => CmsSidebar::MODE_PAGES,
            'pages' => ['contact'],
            'source' => CmsSidebar::SOURCE_COURSE,
            'selection' => CmsSidebar::SELECTION_CATEGORY,
            'category_id' => $parent->id,
            'limit' => 5,
        ]);

        CmsSidebar::query()->create([
            'name' => 'خاموش',
            'title' => 'سایدبار خاموش',
            'is_active' => false,
            'target_mode' => CmsSidebar::MODE_PAGES,
            'pages' => ['contact'],
            'source' => CmsSidebar::SOURCE_COURSE,
            'selection' => CmsSidebar::SELECTION_LATEST,
            'limit' => 5,
        ]);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('دوره‌های مالیات')
            ->assertSee('دوره داخل دسته سایدبار')
            ->assertDontSee('دوره بیرون دسته سایدبار')
            ->assertDontSee('سایدبار خاموش');

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('دوره‌های مالیات');
    }

    public function test_bestseller_courses_and_popular_posts_are_ranked(): void
    {
        $user = User::factory()->create();
        $hot = ShopProduct::factory()->course()->create(['title' => 'دوره پرفروش سایدبار']);
        $cold = ShopProduct::factory()->course()->create(['title' => 'دوره کم‌فروش سایدبار']);

        $paid = Order::query()->create([
            'user_id' => $user->id,
            'order_number' => 'SB-PAID',
            'status' => Order::STATUS_PAID,
            'subtotal' => 2000,
            'total' => 2000,
            'paid_at' => now(),
        ]);
        OrderItem::query()->create([
            'order_id' => $paid->id,
            'shop_product_id' => $hot->id,
            'title' => $hot->title,
            'price' => 1000,
            'quantity' => 5,
        ]);
        OrderItem::query()->create([
            'order_id' => $paid->id,
            'shop_product_id' => $cold->id,
            'title' => $cold->title,
            'price' => 1000,
            'quantity' => 1,
        ]);

        CmsSidebar::query()->create([
            'name' => 'پرفروش',
            'title' => 'پرفروش‌های سایدبار',
            'is_active' => true,
            'target_mode' => CmsSidebar::MODE_PAGES,
            'pages' => ['contact'],
            'source' => CmsSidebar::SOURCE_COURSE,
            'selection' => CmsSidebar::SELECTION_BESTSELLER,
            'limit' => 1,
        ]);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('دوره پرفروش سایدبار')
            ->assertDontSee('دوره کم‌فروش سایدبار');

        CmsPost::factory()->published()->create(['title' => 'مقاله پربازدید سایدبار', 'views' => 900]);
        CmsPost::factory()->published()->create(['title' => 'مقاله کم‌بازدید سایدبار', 'views' => 2]);

        CmsSidebar::query()->where('name', 'پرفروش')->update(['is_active' => false]);
        CmsSidebar::query()->create([
            'name' => 'پربازدید',
            'title' => 'مقالات پربازدید',
            'is_active' => true,
            'target_mode' => CmsSidebar::MODE_PAGES,
            'pages' => ['about'],
            'source' => CmsSidebar::SOURCE_POST,
            'selection' => CmsSidebar::SELECTION_BESTSELLER,
            'limit' => 1,
        ]);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('مقاله پربازدید سایدبار')
            ->assertDontSee('مقاله کم‌بازدید سایدبار');
    }

    public function test_product_source_skips_courses(): void
    {
        ShopProduct::factory()->digital()->create(['title' => 'فایل فروشگاه سایدبار']);
        ShopProduct::factory()->course()->create(['title' => 'دوره که نباید در محصولات باشد']);

        CmsSidebar::query()->create([
            'name' => 'محصولات',
            'title' => 'فایل‌های تازه',
            'is_active' => true,
            'target_mode' => CmsSidebar::MODE_RULES,
            'rules' => ['match' => 'equals', 'path' => '/contact'],
            'source' => CmsSidebar::SOURCE_PRODUCT,
            'selection' => CmsSidebar::SELECTION_LATEST,
            'limit' => 5,
        ]);

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('فایل فروشگاه سایدبار')
            ->assertDontSee('دوره که نباید در محصولات باشد');

        $this->get(route('about'))
            ->assertOk()
            ->assertDontSee('فایل‌های تازه');
    }

    public function test_editor_can_open_sidebars_and_shop_manager_cannot(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $editor = User::factory()->create();
        $editor->assignRole(AccessCatalog::ROLE_EDITOR);

        $shop = User::factory()->create();
        $shop->assignRole(AccessCatalog::ROLE_SHOP_MANAGER);

        $this->actingAs($editor)
            ->get(route('admin.sidebars.index'))
            ->assertOk()
            ->assertSee('سایدبار جدید');

        $this->actingAs($shop)
            ->get(route('admin.sidebars.index'))
            ->assertForbidden();
    }

    public function test_default_article_sidebar_lists_latest_courses(): void
    {
        ShopProduct::factory()->course()->create([
            'title' => 'دوره پیش‌فرض سایدبار مقاله',
            'created_at' => now(),
        ]);
        ShopProduct::factory()->digital()->create(['title' => 'فایل که در سایدبار مقاله نیست']);

        $post = CmsPost::factory()->published()->create(['title' => 'مقاله با سایدبار پیش‌فرض', 'slug' => 'sidebar-default-post']);

        $this->seed(SidebarSeeder::class);
        $this->seed(SidebarSeeder::class);

        $this->assertSame(1, CmsSidebar::query()->where('name', 'سایدبار پیش‌فرض مقالات')->count());

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('آخرین دوره‌ها')
            ->assertSee('دوره پیش‌فرض سایدبار مقاله')
            ->assertDontSee('فایل که در سایدبار مقاله نیست');

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertDontSee('آخرین دوره‌ها');
    }

    public function test_edit_form_renders_as_a_composer(): void
    {
        $admin = $this->makeAdmin();
        $sidebar = CmsSidebar::query()->create([
            'name' => 'فرم سایدبار',
            'title' => 'آخرین دوره‌ها',
            'is_active' => true,
            'target_mode' => CmsSidebar::MODE_PAGES,
            'pages' => ['blog.index'],
            'source' => CmsSidebar::SOURCE_COURSE,
            'selection' => CmsSidebar::SELECTION_LATEST,
            'limit' => 5,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sidebars.edit', $sidebar))
            ->assertOk()
            ->assertSee('محتوای فهرست')
            ->assertSee('جستجوی صفحه')
            ->assertSee('نمایش در سایت')
            ->assertSee('فهرست مقالات');
    }

    public function test_admin_cannot_target_the_user_panel(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from(route('admin.sidebars.create'))
            ->post(route('admin.sidebars.store'), [
                'name' => 'پنل',
                'title' => 'سایدبار پنل',
                'target_mode' => 'pages',
                'pages' => ['panel.dashboard'],
                'source' => 'course',
                'selection' => 'latest',
                'limit' => 4,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.sidebars.create'))
            ->assertSessionHasErrors('pages');

        $this->assertDatabaseCount('cms_sidebars', 0);
    }
}
