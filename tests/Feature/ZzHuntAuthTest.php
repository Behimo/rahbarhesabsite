<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\OtpService;
use App\Support\AccessCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZzHuntAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_with_user_edit_permission_can_promote_themselves_to_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::query()->create([
            'name' => 'hr',
            'label' => 'HR',
            'guard_name' => AccessCatalog::guard(),
            'is_system' => false,
        ]);
        $role->syncPermissions([
            AccessCatalog::ACCESS_ADMIN,
            AccessCatalog::permission('users', AccessCatalog::VIEW),
            AccessCatalog::permission('users', AccessCatalog::UPDATE),
        ]);

        $staff = User::factory()->create();
        $staff->syncRoles(['hr']);

        $this->assertFalse($staff->hasRole(AccessCatalog::ROLE_ADMIN));

        $this->actingAs($staff)
            ->put(route('admin.users.update', $staff), [
                'first_name' => 'HR',
                'last_name' => 'Person',
                'phone' => $staff->phone,
                'email' => $staff->email,
                'status' => 'active',
                'role' => AccessCatalog::ROLE_ADMIN,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(
            $staff->fresh()->hasRole(AccessCatalog::ROLE_ADMIN),
            'A non-admin staff user escalated themselves to the admin role.'
        );
    }

    public function test_staff_with_user_edit_permission_can_promote_another_user_to_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $role = Role::query()->create([
            'name' => 'hr2',
            'label' => 'HR2',
            'guard_name' => AccessCatalog::guard(),
            'is_system' => false,
        ]);
        $role->syncPermissions([
            AccessCatalog::ACCESS_ADMIN,
            AccessCatalog::permission('users', AccessCatalog::VIEW),
            AccessCatalog::permission('users', AccessCatalog::UPDATE),
        ]);

        $staff = User::factory()->create();
        $staff->syncRoles(['hr2']);
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
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($target->fresh()->hasRole(AccessCatalog::ROLE_ADMIN));
    }

    public function test_banned_user_keeps_an_already_authenticated_session(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('panel.dashboard'))
            ->assertOk();

        $user->forceFill(['status' => 'banned'])->save();
        $this->assertTrue($user->fresh()->isBlocked());

        $this->actingAs($user)
            ->get(route('panel.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('panel.profile'))
            ->assertOk();
    }

    public function test_panel_password_change_requires_no_current_password(): void
    {
        $user = User::factory()->create(['password' => 'original-password-1']);

        $this->actingAs($user)
            ->put(route('panel.profile.password'), [
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertSessionHas('success');

        $this->assertTrue($user->fresh()->passwordMatches('brand-new-password'));
    }

    public function test_guest_cannot_change_password(): void
    {
        $this->put(route('panel.profile.password'), [
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect(route('login'));
    }

    public function test_otp_code_cannot_be_replayed_after_success(): void
    {
        $otp = app(OtpService::class);

        $otp->send('09123456789');
        $code = $otp->peekLatestCode('09123456789');

        $this->assertNotNull($code);
        $this->assertTrue($otp->verify('09123456789', $code));
        $this->assertFalse($otp->verify('09123456789', $code), 'OTP code was reusable.');
    }

    public function test_otp_code_is_bound_to_the_requesting_session_phone(): void
    {
        $otp = app(OtpService::class);

        $this->post('/login/otp', ['phone' => '09123456789'])
            ->assertRedirect(route('login.verify'));
        $codeA = $otp->peekLatestCode('09123456789');

        $this->flushSession();

        $this->post('/login/otp', ['phone' => '09120000000'])
            ->assertRedirect(route('login.verify'));
        $codeB = $otp->peekLatestCode('09120000000');

        $this->assertNotSame($codeA, $codeB);

        $this->post('/login/verify', ['code' => $codeA])
            ->assertSessionHasErrors('code');

        $this->guest();
    }

    public function test_admin_logout_route_has_no_auth_middleware(): void
    {
        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_otp_send_route_is_rate_limited(): void
    {
        foreach (range(1, 6) as $i) {
            $this->post('/login/otp', ['phone' => '09125550000']);
        }

        $this->post('/login/otp', ['phone' => '09125550000'])
            ->assertStatus(429);
    }
}
