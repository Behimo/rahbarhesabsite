<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_coupons_index(): void
    {
        $this->get(route('admin.coupons.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_editor_cannot_view_coupons_index(): void
    {
        $this->actingAs($this->makeEditor())
            ->get(route('admin.coupons.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_coupons_index(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.coupons.index'))
            ->assertOk();
    }

    public function test_admin_can_create_coupon_with_uppercase_code(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.coupons.store'), [
                'code' => 'save10',
                'title' => '۱۰ درصد',
                'type' => 'percentage_cart',
                'value' => 10,
                'is_active' => 1,
                'usage_limit_per_user' => 1,
            ])
            ->assertRedirect(route('admin.coupons.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'code' => 'SAVE10',
            'type' => 'percentage_cart',
            'value' => 10,
            'is_active' => 1,
        ]);
    }

    public function test_duplicate_coupon_code_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        Coupon::query()->create([
            'code' => 'DUP10',
            'type' => 'percentage_cart',
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.coupons.create'))
            ->post(route('admin.coupons.store'), [
                'code' => 'DUP10',
                'type' => 'percentage_cart',
                'value' => 15,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.coupons.create'))
            ->assertSessionHasErrors('code');
    }

    public function test_invalid_coupon_type_is_rejected(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->from(route('admin.coupons.create'))
            ->post(route('admin.coupons.store'), [
                'code' => 'BADTYPE',
                'type' => 'not-a-type',
                'value' => 10,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_admin_can_update_coupon(): void
    {
        $admin = $this->makeAdmin();
        $coupon = Coupon::query()->create([
            'code' => 'OLD20',
            'type' => 'percentage_cart',
            'value' => 20,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'NEW25',
                'title' => '۲۵ درصد',
                'type' => 'percentage_cart',
                'value' => 25,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'NEW25',
            'value' => 25,
        ]);
    }

    public function test_admin_can_delete_coupon(): void
    {
        $admin = $this->makeAdmin();
        $coupon = Coupon::query()->create([
            'code' => 'DELME',
            'type' => 'fixed_cart',
            'value' => 5000,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.coupons.destroy', $coupon))
            ->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_editor_cannot_create_or_delete_coupons(): void
    {
        $editor = $this->makeEditor();
        $coupon = Coupon::query()->create([
            'code' => 'LOCKED',
            'type' => 'percentage_cart',
            'value' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($editor)
            ->get(route('admin.coupons.create'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('admin.coupons.store'), [
                'code' => 'NOPE',
                'type' => 'percentage_cart',
                'value' => 10,
                'is_active' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->delete(route('admin.coupons.destroy', $coupon))
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
