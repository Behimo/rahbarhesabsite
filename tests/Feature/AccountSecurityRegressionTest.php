<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for account-security bugs (T4/T12 in docs/BUG-HUNT-REPORT.md).
 *
 * Desired behaviour: blocked accounts lose live sessions, and password changes
 * require the current password. The remaining tests lock behaviour that is
 * already correct (OTP replay, OTP session binding, throttling).
 */
class AccountSecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocked_user_session_is_terminated(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get(route('panel.dashboard'))
            ->assertOk();

        $user->forceFill(['status' => 'banned'])->save();
        $this->assertTrue($user->fresh()->isBlocked());

        $this->actingAs($user)
            ->get(route('panel.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('panel.profile'))
            ->assertForbidden();
    }

    public function test_panel_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'original-password-1']);

        $this->actingAs($user)
            ->put(route('panel.profile.password'), [
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(
            $user->fresh()->passwordMatches('original-password-1'),
            'Password was changed without providing the current password.'
        );

        $this->actingAs($user)
            ->put(route('panel.profile.password'), [
                'current_password' => 'original-password-1',
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
    }

    public function test_unauthenticated_admin_logout_redirects_to_login(): void
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
