<?php

namespace Tests\Feature;

use App\Contracts\OtpSmsDriver;
use App\Jobs\SendOtpSmsJob;
use App\Models\OtpVerification;
use App\Services\Sms\SmsConfigurationException;
use App\Services\Sms\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Support\FakeOtpSmsDriver;
use Tests\TestCase;

class SmsDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_driver_receives_the_otp(): void
    {
        app(SmsManager::class)->sendOtp('+989121234567', '123456');

        $this->assertSame([
            ['phone' => '+989121234567', 'code' => '123456'],
        ], app(FakeOtpSmsDriver::class)->sent);
    }

    public function test_missing_driver_rejects_the_request_without_creating_an_otp(): void
    {
        config()->set('sms.default', null);
        Queue::fake();

        $this->from(route('login'))
            ->post(route('login.otp'), ['phone' => '09121234567'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('phone', 'سرویس پیامک در حال حاضر در دسترس نیست.');

        $this->assertDatabaseCount('otp_verifications', 0);
        $this->assertDatabaseCount('otp_logs', 0);
        $this->assertFalse(Cache::has('otp:cooldown:+989121234567'));
        Queue::assertNotPushed(SendOtpSmsJob::class);
    }

    public function test_unknown_or_invalid_driver_is_rejected(): void
    {
        config()->set('sms.default', 'unknown');

        try {
            app(SmsManager::class)->ensureConfigured();
            $this->fail('Unknown driver was accepted.');
        } catch (SmsConfigurationException $exception) {
            $this->assertSame('سرویس پیامک در حال حاضر در دسترس نیست.', $exception->getMessage());
        }

        config()->set('sms.default', 'invalid');
        config()->set('sms.drivers', ['invalid' => ['class' => \stdClass::class, 'fields' => []]]);

        $this->expectException(SmsConfigurationException::class);
        app(SmsManager::class)->ensureConfigured();
    }

    public function test_broken_driver_resolution_does_not_expose_its_exception_to_users(): void
    {
        config()->set('sms.default', 'broken');
        config()->set('sms.drivers', ['broken' => ['class' => BrokenOtpSmsDriver::class, 'fields' => []]]);

        $this->from(route('login'))
            ->post(route('login.otp'), ['phone' => '09121234567'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('phone', 'سرویس پیامک در حال حاضر در دسترس نیست.');

        $this->assertDatabaseCount('otp_verifications', 0);
    }

    public function test_job_loads_the_active_code_without_serializing_it(): void
    {
        $verification = $this->verification();
        $job = new SendOtpSmsJob($verification->id);

        $this->assertStringNotContainsString($verification->code, serialize($job));

        $job->handle(app(SmsManager::class));

        $this->assertSame([
            ['phone' => $verification->phone, 'code' => $verification->code],
        ], app(FakeOtpSmsDriver::class)->sent);
    }

    public function test_job_skips_a_superseded_deleted_expired_or_verified_code(): void
    {
        $superseded = $this->verification();
        $this->verification('654321');
        (new SendOtpSmsJob($superseded->id))->handle(app(SmsManager::class));

        $expired = $this->verification('222222', '+989121234568');
        $expired->update(['expires_at' => now()->subMinute()]);
        (new SendOtpSmsJob($expired->id))->handle(app(SmsManager::class));

        $verified = $this->verification('333333', '+989121234569');
        $verified->update(['verified_at' => now()]);
        (new SendOtpSmsJob($verified->id))->handle(app(SmsManager::class));

        $deleted = $this->verification('444444', '+989121234570');
        $deleted->delete();
        (new SendOtpSmsJob($deleted->id))->handle(app(SmsManager::class));

        $this->assertSame([], app(FakeOtpSmsDriver::class)->sent);
    }

    public function test_driver_failure_escapes_the_job_for_queue_retries(): void
    {
        config()->set('sms.drivers', ['fake' => ['class' => FailingOtpSmsDriver::class, 'fields' => []]]);
        $job = new SendOtpSmsJob($this->verification()->id);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30, 60], $job->backoff);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('provider failed');

        $job->handle(app(SmsManager::class));
    }

    private function verification(string $code = '123456', string $phone = '+989121234567'): OtpVerification
    {
        return OtpVerification::query()->create([
            'phone' => $phone,
            'code' => $code,
            'expires_at' => now()->addMinutes(5),
        ]);
    }
}

class FailingOtpSmsDriver implements OtpSmsDriver
{
    public function sendOtp(string $phone, string $code): void
    {
        throw new RuntimeException('provider failed');
    }
}

class BrokenOtpSmsDriver implements OtpSmsDriver
{
    public function __construct()
    {
        throw new RuntimeException('internal provider secret');
    }

    public function sendOtp(string $phone, string $code): void {}
}
