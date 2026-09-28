<?php

namespace Tests\Feature;

use App\Models\CmsAdmin;
use App\Models\CmsProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_product_shows_validation_errors(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'cms')
            ->from(route('admin.products.create'))
            ->followingRedirects()
            ->post(route('admin.products.store'), [
                'title' => 'محصول تست',
                'slug' => 'نامک فارسی',
                'accent' => 'orange',
            ])
            ->assertOk()
            ->assertSee('admin-feedback', false)
            ->assertSee('نامک فقط می‌تواند شامل حروف انگلیسی', false)
            ->assertSee('is-invalid', false);
    }

    public function test_created_product_shows_success_message(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'cms')
            ->followingRedirects()
            ->post(route('admin.products.store'), [
                'title' => 'محصول جدید',
                'slug' => 'new-product',
                'accent' => 'blue',
                'sort_order' => 1,
            ])
            ->assertOk()
            ->assertSee('محصول ایجاد شد.', false)
            ->assertSee('انجام شد', false);

        $this->assertDatabaseHas('cms_products', [
            'slug' => 'new-product',
            'title' => 'محصول جدید',
        ]);
    }

    public function test_persian_slug_is_rejected_on_create_and_update(): void
    {
        $admin = $this->admin();
        $product = CmsProduct::query()->create([
            'slug' => 'rahbar',
            'title' => 'راهبر',
            'accent' => 'orange',
        ]);

        $this->actingAs($admin, 'cms')
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'title' => 'محصول فارسی',
                'slug' => 'محصول',
                'accent' => 'orange',
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin, 'cms')
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), [
                'title' => 'راهبر',
                'slug' => 'محصول',
                'accent' => 'orange',
            ])
            ->assertRedirect(route('admin.products.edit', $product))
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseHas('cms_products', [
            'id' => $product->id,
            'slug' => 'rahbar',
        ]);
    }

    public function test_duplicate_slug_shows_error(): void
    {
        $admin = $this->admin();
        CmsProduct::query()->create([
            'slug' => 'existing',
            'title' => 'موجود',
            'accent' => 'orange',
        ]);

        $this->actingAs($admin, 'cms')
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'title' => 'تکراری',
                'slug' => 'existing',
                'accent' => 'green',
            ])
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('slug');
    }

    private function admin(): CmsAdmin
    {
        return CmsAdmin::query()->create([
            'name' => 'Admin',
            'email' => 'products@test.com',
            'password' => 'password',
            'is_super' => true,
        ]);
    }
}
