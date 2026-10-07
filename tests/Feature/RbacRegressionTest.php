<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the RBAC self-escalation bug (T1 in docs/BUG-HUNT-REPORT.md).
 *
 * These encode the DESIRED behaviour: a staff member holding only the `users`
 * permission group must never be able to mint the superuser `admin` role.
 * They fail while the bug exists and pass once UserController::update is fixed.
 */
class RbacRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_promote_themselves_to_admin(): void
    {
        $staff = $this->makeStaffWithUserPermissions('hr');

        $this->actingAs($staff)
            ->put(route('admin.users.update', $staff), [
                'first_name' => 'HR',
                'last_name' => 'Person',
                'phone' => $staff->phone,
                'email' => $staff->email,
                'status' => 'active',
                'role' => AccessCatalog::ROLE_ADMIN,
            ])
            ->assertSessionHasErrors('role');

        $this->assertFalse(
            $staff->fresh()->hasRole(AccessCatalog::ROLE_ADMIN),
            'A non-admin staff user escalated themselves to the admin role.'
        );
    }

    public function test_staff_cannot_assign_the_admin_role_to_another_user(): void
    {
        $staff = $this->makeStaffWithUserPermissions('hr2');
        $target = User::factory()->create();
        $target->syncRoles([AccessCatalog::ROLE_USER]);

        $this->actingAs($staff)
            ->put(route('admin.users.update', $target), [
                'first_name' => 'Target',
                'last_name' => 'User',
                'phone' => $target->phone,
                'email' => $target->email,
                'status' => 'active',
                'role' => AccessCatalog::ROLE_ADMIN,
            ])
            ->assertSessionHasErrors('role');

        $this->assertFalse(
            $target->fresh()->hasRole(AccessCatalog::ROLE_ADMIN),
            'A non-admin staff user granted the admin role to someone else.'
        );
    }

    private function makeStaffWithUserPermissions(string $roleName): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::query()->create([
            'name' => $roleName,
            'label' => strtoupper($roleName),
            'guard_name' => AccessCatalog::guard(),
            'is_system' => false,
        ]);
        $role->syncPermissions([
            AccessCatalog::ACCESS_ADMIN,
            AccessCatalog::permission('users', AccessCatalog::VIEW),
            AccessCatalog::permission('users', AccessCatalog::UPDATE),
        ]);

        $staff = User::factory()->create();
        $staff->syncRoles([$roleName]);

        $this->assertFalse($staff->hasRole(AccessCatalog::ROLE_ADMIN));

        return $staff;
    }
}
