<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CmsPost;
use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_slug_is_filled_when_model_events_are_disabled(): void
    {
        $category = Model::withoutEvents(fn () => Category::query()->create([
            'type' => Category::TYPE_POST,
            'name' => 'راهنمای حسابداری',
            'slug' => 'accounting-guides',
            'description' => 'مقالات آموزشی حسابداری و مالیات',
            'sort_order' => 1,
            'is_active' => true,
        ]));

        $this->assertSame('accounting-guides', $category->fresh()->full_slug);
    }

    public function test_child_full_slug_includes_parent_and_updates_when_parent_moves(): void
    {
        $software = Category::query()->create([
            'type' => Category::TYPE_PRODUCT,
            'name' => 'نرم‌افزار',
            'slug' => 'software',
            'sort_order' => 1,
        ]);

        $accounting = Category::query()->create([
            'type' => Category::TYPE_PRODUCT,
            'parent_id' => $software->id,
            'name' => 'حسابداری',
            'slug' => 'accounting',
            'sort_order' => 1,
        ]);

        $this->assertSame('software/accounting', $accounting->fresh()->full_slug);

        $finance = Category::query()->create([
            'type' => Category::TYPE_PRODUCT,
            'name' => 'مالی',
            'slug' => 'finance',
        ]);

        $accounting->update(['parent_id' => $finance->id]);

        $this->assertSame('finance/accounting', $accounting->fresh()->full_slug);
    }

    public function test_deleting_a_parent_promotes_its_children(): void
    {
        $parent = Category::query()->create([
            'type' => Category::TYPE_POST,
            'name' => 'راهنما',
            'slug' => 'guides',
        ]);
        $child = Category::query()->create([
            'type' => Category::TYPE_POST,
            'parent_id' => $parent->id,
            'name' => 'مالیات',
            'slug' => 'tax',
        ]);

        $parent->delete();

        $child->refresh();
        $this->assertNull($child->parent_id);
        $this->assertSame('tax', $child->full_slug);
    }

    public function test_blog_filter_includes_posts_in_child_categories(): void
    {
        $parent = Category::query()->create([
            'type' => Category::TYPE_POST,
            'name' => 'آموزش',
            'slug' => 'learn',
            'is_active' => true,
        ]);
        $child = Category::query()->create([
            'type' => Category::TYPE_POST,
            'parent_id' => $parent->id,
            'name' => 'مالیات',
            'slug' => 'tax',
            'is_active' => true,
        ]);

        $post = CmsPost::query()->create([
            'slug' => 'tax-guide',
            'title' => 'راهنمای مالیات',
            'excerpt' => 'خلاصه',
            'body' => '<p>متن</p>',
            'is_published' => true,
            'status' => CmsPost::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
        $post->syncCategories([$child->id], $child->id);

        $this->get(route('blog.index', ['category' => 'learn']))
            ->assertOk()
            ->assertSee('راهنمای مالیات');
    }

    public function test_course_can_belong_to_several_categories_with_one_primary(): void
    {
        $first = Category::query()->create([
            'type' => Category::TYPE_PRODUCT,
            'name' => 'حسابداری',
            'slug' => 'accounting',
        ]);
        $second = Category::query()->create([
            'type' => Category::TYPE_PRODUCT,
            'name' => 'مالیات',
            'slug' => 'tax',
        ]);

        $product = ShopProduct::query()->create([
            'slug' => 'course-tax',
            'title' => 'دوره مالیات',
            'price' => 1000,
            'type' => ShopProduct::TYPE_COURSE,
            'is_published' => true,
        ]);
        $product->syncCategories([$first->id, $second->id], $second->id);

        $this->assertSame($second->id, $product->fresh()->category?->id);
        $this->assertCount(2, $product->categories);
    }

    public function test_admin_category_screens_follow_the_tree(): void
    {
        $admin = $this->makeAdmin(['email' => 'categories@test.com']);

        $parent = Category::query()->create([
            'type' => Category::TYPE_POST,
            'name' => 'آموزش',
            'slug' => 'learn',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertRedirect(route('admin.categories.index', ['type' => 'post']));

        $this->actingAs($admin)
            ->get(route('admin.categories.index', ['type' => 'post']))
            ->assertOk()
            ->assertSee('دسته‌های بلاگ')
            ->assertSee('آموزش')
            ->assertSee('learn')
            ->assertSee('زیردسته')
            ->assertDontSee('Taxonomy');

        $this->actingAs($admin)
            ->get(route('admin.categories.create', ['type' => 'post', 'parent_id' => $parent->id]))
            ->assertOk()
            ->assertSee('دستهٔ جدید بلاگ')
            ->assertSee('آموزش');

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'type' => 'post',
                'parent_id' => $parent->id,
                'name' => 'مالیات',
                'slug' => 'tax',
                'is_active' => '1',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.categories.index', ['type' => 'post']));

        $child = Category::query()->where('slug', 'tax')->first();
        $this->assertNotNull($child);
        $this->assertSame('learn/tax', $child->full_slug);

        $this->actingAs($admin)
            ->get(route('admin.tags.index'))
            ->assertOk()
            ->assertSee('برچسب‌ها درخت نیستند');

        $this->actingAs($admin)
            ->get(route('admin.posts.create'))
            ->assertOk()
            ->assertSee('دستهٔ اصلی')
            ->assertSee('آموزش');

        $this->actingAs($admin)
            ->get(route('admin.courses.create'))
            ->assertOk()
            ->assertSee('جایگاه در درخت محصول');
    }
}
