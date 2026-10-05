<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\AccessCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_define_a_role_and_assign_it_when_editing_a_user(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'label' => 'پشتیبان',
                'name' => 'support',
                'permissions' => [AccessCatalog::ACCESS_ADMIN, AccessCatalog::permission('messages', AccessCatalog::VIEW)],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('support');
        $this->assertSame('پشتیبان', $role->label);
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->hasPermissionTo(AccessCatalog::permission('messages', AccessCatalog::VIEW)));

        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('پشتیبان')
            ->assertDontSee('دسترسی‌های مستقیم');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'first_name' => 'علی',
                'last_name' => 'رضایی',
                'phone' => $user->phone,
                'email' => $user->email,
                'status' => 'active',
                'role' => 'support',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('علی رضایی', $user->name);
        $this->assertTrue($user->hasRole('support'));
        $this->assertTrue($user->can(AccessCatalog::permission('messages', AccessCatalog::VIEW)));
        $this->assertFalse($user->can(AccessCatalog::permission('messages', AccessCatalog::DELETE)));
        $this->assertFalse($user->can(AccessCatalog::permission('settings', AccessCatalog::VIEW)));
    }

    public function test_system_role_identifier_stays_fixed_and_cannot_be_deleted(): void
    {
        $admin = $this->makeAdmin();
        $editor = Role::findByName(AccessCatalog::ROLE_EDITOR);

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $editor), [
                'label' => 'نویسنده',
                'name' => 'writer',
                'permissions' => [AccessCatalog::ACCESS_ADMIN, AccessCatalog::permission('posts', AccessCatalog::UPDATE)],
            ])
            ->assertRedirect(route('admin.roles.index'));

        $editor->refresh();
        $this->assertSame(AccessCatalog::ROLE_EDITOR, $editor->name);
        $this->assertSame('نویسنده', $editor->label);
        $this->assertTrue($editor->hasPermissionTo(AccessCatalog::permission('posts', AccessCatalog::UPDATE)));
        $this->assertTrue($editor->hasPermissionTo(AccessCatalog::permission('posts', AccessCatalog::VIEW)));
        $this->assertFalse($editor->hasPermissionTo(AccessCatalog::permission('posts', AccessCatalog::DELETE)));
        $this->assertFalse($editor->hasPermissionTo(AccessCatalog::permission('settings', AccessCatalog::VIEW)));

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $editor))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['name' => AccessCatalog::ROLE_EDITOR]);
    }

    public function test_assigned_custom_role_cannot_be_deleted(): void
    {
        $admin = $this->makeAdmin();
        $role = Role::query()->create([
            'name' => 'support',
            'label' => 'پشتیبان',
            'guard_name' => AccessCatalog::guard(),
            'is_system' => false,
        ]);
        $user = User::factory()->create();
        $user->syncRoles([$role->name]);

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_admin_can_edit_user_identity_status_and_password(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create([
            'first_name' => 'قدیم',
            'last_name' => 'قدیم‌زاده',
            'name' => 'قدیم قدیم‌زاده',
            'status' => 'active',
        ]);
        $user->assignRole(AccessCatalog::ROLE_USER);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('نام خانوادگی')
            ->assertSee('وضعیت')
            ->assertSee('رمز عبور')
            ->assertSee('قدیم')
            ->assertDontSee('دسترسی‌های مستقیم');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'first_name' => 'سارا',
                'last_name' => 'کریمی',
                'phone' => $user->phone,
                'email' => 'sara@example.com',
                'status' => 'suspended',
                'role' => AccessCatalog::ROLE_USER,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('سارا', $user->first_name);
        $this->assertSame('سارا کریمی', $user->name);
        $this->assertSame('sara@example.com', $user->email);
        $this->assertSame('suspended', $user->status);
        $this->assertTrue($user->isBlocked());
        $this->assertTrue($user->passwordMatches('new-password'));
        $this->assertFalse($user->is_wp_password);
    }

    public function test_admin_cannot_block_their_own_account(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'first_name' => 'مدیر',
                'phone' => $admin->phone,
                'email' => $admin->email,
                'status' => 'banned',
                'role' => AccessCatalog::ROLE_ADMIN,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_editor_cannot_manage_roles(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole(AccessCatalog::ROLE_EDITOR);

        $this->actingAs($editor)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }
}
