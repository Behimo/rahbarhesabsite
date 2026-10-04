<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\DefaultUsersSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_the_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_customer_cannot_open_the_admin_panel(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(AccessCatalog::ROLE_USER);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_editor_can_manage_posts_but_not_settings(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole(AccessCatalog::ROLE_EDITOR);

        $this->actingAs($editor)
            ->get(route('admin.posts.index'))
            ->assertOk();

        $this->actingAs($editor)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_open_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_default_users_receive_their_roles(): void
    {
        $this->seed(DefaultUsersSeeder::class);

        $admin = User::query()->where('email', config('cms.admin_email', 'admin@rahbarhesab.ir'))->first();
        $editor = User::query()->where('email', 'editor@rahbarhesab.ir')->first();
        $shop = User::query()->where('email', 'shop@rahbarhesab.ir')->first();
        $instructor = User::query()->where('email', 'instructor@example.com')->first();
        $customer = User::query()->where('email', 'demo@example.com')->first();

        $this->assertTrue($admin?->hasRole(AccessCatalog::ROLE_ADMIN));
        $this->assertTrue($editor?->can(AccessCatalog::MANAGE_POSTS));
        $this->assertFalse($editor?->can(AccessCatalog::MANAGE_SETTINGS));
        $this->assertTrue($shop?->can(AccessCatalog::MANAGE_ORDERS));
        $this->assertTrue($instructor?->can(AccessCatalog::MANAGE_COURSES));
        $this->assertFalse($customer?->can(AccessCatalog::ACCESS_ADMIN));
    }

    public function test_staff_can_log_in_to_the_admin_panel(): void
    {
        $this->seed(DefaultUsersSeeder::class);

        $this->post(route('admin.login'), [
            'email' => 'editor@rahbarhesab.ir',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_customer_login_is_rejected_on_the_admin_panel(): void
    {
        $this->seed(DefaultUsersSeeder::class);

        $this->post(route('admin.login'), [
            'email' => 'demo@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
