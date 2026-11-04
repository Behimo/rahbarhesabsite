<?php

namespace Tests\Feature\Api\V1\App;

use App\Models\OtpVerification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTokenTest extends MobileApiTestCase
{
    public function test_otp_login_issues_a_personal_token_and_logout_revokes_it(): void
    {
        $phone = '09121112233';
        $challenge = $this->withHeaders($this->appHeaders())
            ->postJson('/api/v1/app/auth/otp', ['phone' => $phone])
            ->assertOk()
            ->json('data.challenge_id');

        $code = app(OtpService::class)->peekLatestCode($phone, OtpService::PURPOSE_APP);
        $stored = OtpVerification::query()->latest('id')->value('code');
        $this->assertIsString($stored);
        $this->assertStringStartsWith('enc:', $stored);
        $this->assertStringNotContainsString($code, $stored);

        $token = $this->withHeaders($this->appHeaders())
            ->postJson('/api/v1/app/auth/token', [
                'phone' => $phone,
                'code' => $code,
                'challenge_id' => $challenge,
                'device_name' => 'Pixel',
            ])
            ->assertCreated()
            ->json('data.token');

        $this->withHeaders([
            'Accept' => 'application/json',
            'X-App-Key' => 'test-app-key',
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/app/me')->assertOk()->assertJsonPath('data.mobile', $phone);

        $this->withHeaders([
            'Accept' => 'application/json',
            'X-App-Key' => 'test-app-key',
            'Authorization' => 'Bearer '.$token,
        ])->deleteJson('/api/v1/app/auth/token')->assertNoContent();

        $this->withHeaders([
            'Accept' => 'application/json',
            'X-App-Key' => 'test-app-key',
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/app/me')->assertUnauthorized();
    }

    public function test_requests_without_the_app_key_are_rejected(): void
    {
        $this->postJson('/api/v1/app/auth/otp', ['phone' => '09120000000'])->assertUnauthorized();
    }

    public function test_a_token_without_the_app_ability_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->withHeaders($this->appHeaders($user, ['browse']))
            ->getJson('/api/v1/app/me')
            ->assertForbidden()
            ->assertJsonPath('code', 'token_forbidden');
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $this->appToken($user, ['app'], now()->subMinute());

        $this->withHeaders([
            'Accept' => 'application/json',
            'X-App-Key' => 'test-app-key',
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/app/me')->assertUnauthorized();
    }

    public function test_blocking_a_user_revokes_app_tokens(): void
    {
        $user = User::factory()->create();
        $token = $this->appToken($user);

        $user->update(['status' => User::STATUS_BANNED]);

        $this->assertSame(0, PersonalAccessToken::query()->where('tokenable_id', $user->id)->count());
        $this->withHeaders([
            'Accept' => 'application/json',
            'X-App-Key' => 'test-app-key',
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/app/me')->assertUnauthorized();
    }

    public function test_only_the_newest_tokens_are_kept(): void
    {
        config(['cms.app.max_tokens' => 2]);
        $user = User::factory()->create(['mobile' => '09123334455', 'phone' => '09123334455']);

        foreach (['one', 'two', 'three'] as $device) {
            Cache::forget('otp:cooldown:+989123334455');
            $challenge = app(OtpService::class)->send('09123334455', OtpService::PURPOSE_APP);
            $code = app(OtpService::class)->peekLatestCode('09123334455', OtpService::PURPOSE_APP);
            $this->withHeaders($this->appHeaders())->postJson('/api/v1/app/auth/token', [
                'phone' => '09123334455',
                'code' => $code,
                'challenge_id' => $challenge,
                'device_name' => $device,
            ])->assertCreated();
        }

        $this->assertSame(2, $user->tokens()->count());
        $this->assertSame(['two', 'three'], $user->tokens()->orderBy('id')->pluck('name')->all());
    }
}
