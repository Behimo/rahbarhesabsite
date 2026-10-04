<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSlugValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_slug_rules_match_on_create_and_update(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.categories.index'))
            ->post(route('admin.categories.store'), [
                'name' => 'مالیات',
                'slug' => 'مالیات',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasErrors('slug');

        $category = Category::query()->create([
            'type' => Category::TYPE_POST,
            'slug' => 'tax',
            'name' => 'مالیات',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.categories.index'))
            ->put(route('admin.categories.update', $category), [
                'name' => 'مالیات',
                'slug' => 'مالیات',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'مالیات',
                'slug' => 'tax-1405',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('tax-1405', $category->fresh()->slug);
    }

    public function test_page_rejects_persian_and_reserved_slugs(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.pages.create'))
            ->post(route('admin.pages.store'), [
                'title' => 'درباره',
                'slug' => 'درباره',
                'robots' => 'index, follow',
            ])
            ->assertRedirect(route('admin.pages.create'))
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)
            ->from(route('admin.pages.create'))
            ->post(route('admin.pages.store'), [
                'title' => 'ادمین',
                'slug' => 'admin',
                'robots' => 'index, follow',
            ])
            ->assertRedirect(route('admin.pages.create'))
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)
            ->post(route('admin.pages.store'), [
                'title' => 'راهنما',
                'slug' => 'guide',
                'robots' => 'index, follow',
            ])
            ->assertRedirect();

        $page = CmsPage::query()->where('slug', 'guide')->first();
        $this->assertNotNull($page);

        $this->actingAs($admin)
            ->from(route('admin.pages.edit', $page))
            ->put(route('admin.pages.update', $page), [
                'title' => 'راهنما',
                'slug' => 'راهنما',
                'robots' => 'index, follow',
            ])
            ->assertRedirect(route('admin.pages.edit', $page))
            ->assertSessionHasErrors('slug');
    }

    public function test_course_slug_rules_match_on_create_and_update(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.courses.create'))
            ->post(route('admin.courses.store'), [
                'title' => 'دوره مالیات',
                'slug' => 'مالیات',
                'price' => 1000,
                'level' => 'beginner',
            ])
            ->assertRedirect(route('admin.courses.create'))
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)
            ->post(route('admin.courses.store'), [
                'title' => 'دوره مالیات',
                'slug' => 'tax-course',
                'price' => 1000,
                'level' => 'beginner',
            ])
            ->assertRedirect();

        $product = ShopProduct::query()->where('slug', 'tax-course')->first();
        $this->assertNotNull($product);

        $this->actingAs($admin)
            ->from(route('admin.courses.edit', $product))
            ->put(route('admin.courses.update', $product), [
                'title' => 'دوره مالیات',
                'slug' => 'مالیات',
                'price' => 1000,
                'level' => 'beginner',
            ])
            ->assertRedirect(route('admin.courses.edit', $product))
            ->assertSessionHasErrors('slug');
    }

    private function admin(): User
    {
        return $this->makeAdmin(['email' => 'slug-admin@test.com']);
    }
}
