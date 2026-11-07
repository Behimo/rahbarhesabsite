<?php

namespace Tests\Feature;

use App\Models\CmsMenu;
use App\Models\CmsMenuItem;
use App\Models\CmsProduct;
use App\Models\Course;
use App\Models\ShopProduct;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_picker_lists_shop_courses(): void
    {
        $admin = $this->makeAdmin();
        $menu = CmsMenu::query()->create([
            'name' => 'اصلی',
            'slug' => 'primary-menu',
            'location' => 'primary',
        ]);
        CmsProduct::query()->create([
            'slug' => 'cms-only-product',
            'title' => 'محصول معرفی',
            'is_published' => true,
        ]);
        $course = ShopProduct::query()->create([
            'slug' => 'sellable-course',
            'title' => 'دوره قابل فروش',
            'price' => 1000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
        Course::query()->create([
            'shop_product_id' => $course->id,
            'level' => 'beginner',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.menus.edit', $menu))
            ->assertOk()
            ->assertSee('sellable-course')
            ->assertDontSee('cms-only-product');
    }

    public function test_a_failed_tree_save_keeps_the_previous_items(): void
    {
        $menu = CmsMenu::query()->create([
            'name' => 'اصلی',
            'slug' => 'primary-menu',
            'location' => 'primary',
        ]);
        $menu->allItems()->create([
            'label' => 'قدیمی',
            'type' => 'custom',
            'url' => '/old',
            'sort_order' => 0,
        ]);

        try {
            app(MenuService::class)->saveTree($menu, [
                ['label' => 'سالم', 'type' => 'custom', 'url' => '/ok'],
                ['label' => str_repeat('ن', 300), 'type' => 'custom', 'url' => '/long'],
            ]);
            $this->fail('A too-long menu label should abort the save.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('طولانی', $exception->getMessage());
        }

        $this->assertSame(['قدیمی'], $menu->allItems()->pluck('label')->all());
        $this->assertSame(1, CmsMenuItem::query()->count());
    }
}
