<?php

namespace Tests\Feature;

use App\Jobs\SendOtpSmsJob;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OtpAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_otp_dispatches_sms_job(): void
    {
        Queue::fake();

        $this->post(route('login.otp'), [
            'phone' => '09121234567',
            'name' => 'OTP User',
        ])->assertRedirect(route('login.verify'));

        Queue::assertPushed(SendOtpSmsJob::class, function (SendOtpSmsJob $job) {
            return str_ends_with($job->phone, '9121234567')
                && strlen($job->code) === (int) config('otp.length', 6);
        });
    }

    public function test_pending_activation_otp_flow_activates_account(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'password' => 'SecretPass1',
            'status' => User::STATUS_PENDING_ACTIVATION,
            'mobile' => null,
            'phone' => null,
            'claimed_mobile' => '09123334455',
            'is_wp_password' => false,
        ]);

        $this->post(route('login.password'), [
            'login' => 'pending@example.com',
            'password' => 'SecretPass1',
        ])->assertRedirect(route('login.activate'));

        $this->assertGuest();
        $this->assertSame($user->id, session('activate_user_id'));

        $this->post(route('login.activate.submit'), [
            'phone' => '09123334455',
        ])->assertRedirect(route('login.verify'));

        $this->assertSame('09123334455', session('otp_phone'));
        $this->assertSame($user->id, session('activate_user_id'));

        $code = app(OtpService::class)->peekLatestCode('09123334455');
        $this->assertNotNull($code);

        $this->post(route('login.verify.submit'), ['code' => $code])
            ->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => User::STATUS_ACTIVE,
            'mobile' => '09123334455',
            'phone' => '09123334455',
            'claimed_mobile' => null,
        ]);
        $this->assertNull(session('otp_phone'));
        $this->assertNull(session('activate_user_id'));
    }

    public function test_otp_send_rejects_pending_activation_phone(): void
    {
        User::factory()->create([
            'status' => User::STATUS_PENDING_ACTIVATION,
            'mobile' => '09121112233',
            'phone' => '09121112233',
        ]);

        $this->from(route('login'))
            ->post(route('login.otp'), ['phone' => '09121112233'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_otp_send_rejects_banned_phone(): void
    {
        User::factory()->create([
            'status' => User::STATUS_BANNED,
            'mobile' => '09124445566',
            'phone' => '09124445566',
        ]);

        $this->from(route('login'))
            ->post(route('login.otp'), ['phone' => '09124445566'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('phone');

        $this->assertGuest();
    }

    public function test_wrong_otp_code_keeps_guest(): void
    {
        $this->post(route('login.otp'), ['phone' => '09125556677'])->assertRedirect(route('login.verify'));

        $this->from(route('login.verify'))
            ->post(route('login.verify.submit'), ['code' => '000000'])
            ->assertRedirect(route('login.verify'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_expired_otp_code_is_rejected(): void
    {
        $this->post(route('login.otp'), ['phone' => '09126667788'])->assertRedirect(route('login.verify'));

        $code = app(OtpService::class)->peekLatestCode('09126667788');
        $this->assertNotNull($code);

        OtpVerification::query()->latest('id')->firstOrFail()->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->from(route('login.verify'))
            ->post(route('login.verify.submit'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_max_otp_attempts_block_even_correct_code(): void
    {
        $this->post(route('login.otp'), ['phone' => '09127778899'])->assertRedirect(route('login.verify'));

        $code = app(OtpService::class)->peekLatestCode('09127778899');
        $this->assertNotNull($code);

        $maxAttempts = (int) config('otp.max_attempts', 5);
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->from(route('login.verify'))
                ->post(route('login.verify.submit'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $this->from(route('login.verify'))
            ->post(route('login.verify.submit'), ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_verify_without_session_redirects_to_login(): void
    {
        $this->post(route('login.verify.submit'), ['code' => '123456'])
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_show_verify_without_otp_phone_redirects_to_login(): void
    {
        $this->get(route('login.verify'))->assertRedirect(route('login'));
    }

    public function test_activate_rejects_phone_already_taken(): void
    {
        User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'mobile' => '09128889900',
            'phone' => '09128889900',
        ]);

        User::factory()->create([
            'email' => 'pending2@example.com',
            'password' => 'SecretPass1',
            'status' => User::STATUS_PENDING_ACTIVATION,
            'mobile' => null,
            'phone' => null,
            'is_wp_password' => false,
        ]);

        $this->post(route('login.password'), [
            'login' => 'pending2@example.com',
            'password' => 'SecretPass1',
        ])->assertRedirect(route('login.activate'));

        $this->from(route('login.activate'))
            ->post(route('login.activate.submit'), ['phone' => '09128889900'])
            ->assertRedirect(route('login.activate'))
            ->assertSessionHasErrors('phone');
    }

    public function test_password_login_authenticates_active_user(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'password' => 'SecretPass1',
            'status' => User::STATUS_ACTIVE,
            'is_wp_password' => false,
        ]);

        $this->post(route('login.password'), [
            'login' => 'active@example.com',
            'password' => 'SecretPass1',
        ])->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_clears_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_otp_login_honors_safe_intended_redirect(): void
    {
        $this->get(route('login', ['redirect' => '/panel/profile']))
            ->assertOk();

        $this->post(route('login.otp'), ['phone' => '09129990011'])
            ->assertRedirect(route('login.verify'));

        $code = app(OtpService::class)->peekLatestCode('09129990011');
        $this->assertNotNull($code);

        $this->post(route('login.verify.submit'), ['code' => $code])
            ->assertRedirect('/panel/profile');

        $this->assertAuthenticated();
    }

    public function test_returning_user_otp_login_does_not_duplicate_user(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'mobile' => '09120001122',
            'phone' => '09120001122',
        ]);

        $this->post(route('login.otp'), ['phone' => '09120001122'])
            ->assertRedirect(route('login.verify'));

        $code = app(OtpService::class)->peekLatestCode('09120001122');
        $this->assertNotNull($code);

        $this->post(route('login.verify.submit'), ['code' => $code])
            ->assertRedirect(route('panel.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->where('phone', '09120001122')->count());
    }

    public function test_invalid_phone_is_rejected_on_otp_send(): void
    {
        $this->from(route('login'))
            ->post(route('login.otp'), ['phone' => '123'])
            ->assertSessionHasErrors('phone');
    }
}
