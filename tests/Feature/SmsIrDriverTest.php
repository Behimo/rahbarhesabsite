<?php

namespace Tests\Feature;

use App\Jobs\SendOtpSmsJob;
use App\Models\CmsSetting;
use App\Models\OtpVerification;
use App\Services\Sms\SmsDeliveryException;
use App\Services\Sms\SmsDriverRegistry;
use App\Services\Sms\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class SmsIrDriverTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_request_uses_local_mobile_numeric_template_and_named_parameter(): void
    {
        $this->configureDriver();
        Http::preventStrayRequests();
        Http::fake(['api.sms.ir/v1/send/verify' => Http::response(['status' => 1, 'data' => ['messageId' => 123]], 200)]);

        app(SmsManager::class)->sendOtp('+989121234567', '123456');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.sms.ir/v1/send/verify'
            && $request->method() === 'POST'
            && $request->hasHeader('x-api-key', 'synthetic-test-key')
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->data() === [
                'mobile' => '09121234567',
                'templateId' => 731,
                'parameters' => [['name' => 'OtpCode', 'value' => '123456']],
            ]);
    }

    public function test_code_placeholder_receives_the_saved_numeric_code_without_losing_leading_zeros(): void
    {
        app(SmsDriverRegistry::class)->save('smsir', ['smsir' => true], [
            'smsir' => ['api_key' => 'synthetic-test-key', 'template_id' => 249450, 'code_parameter' => 'CODE'],
        ], []);
        Http::preventStrayRequests();
        Http::fake(['api.sms.ir/v1/send/verify' => Http::response(['status' => 1], 200)]);
        $verification = OtpVerification::query()->create([
            'phone' => '+989121234567',
            'code' => '001234',
            'expires_at' => now()->addMinutes(5),
        ]);

        (new SendOtpSmsJob($verification->id))->handle(app(SmsManager::class));

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            $this->assertSame(249450, $request['templateId']);
            $this->assertSame([['name' => 'CODE', 'value' => '001234']], $request['parameters']);
            $parameter = $request['parameters'][0];
            $template = "کد احراز هویت شما : #CODE#\n\nrahbarhesab.com";
            $rendered = str_replace('#'.$parameter['name'].'#', $parameter['value'], $template);
            $this->assertStringNotContainsString('#CODE#', $rendered);
            $this->assertStringContainsString('001234', $rendered);

            return true;
        });
    }

    public function test_delivery_failures_preserve_only_safe_status_codes_in_job_logs(): void
    {
        $this->configureDriver();
        Http::preventStrayRequests();
        Http::fake(['api.sms.ir/v1/send/verify' => Http::response([
            'status' => 999,
            'message' => 'synthetic-private-provider-message',
        ], 400)]);
        Log::spy();
        Log::shouldReceive('channel')->with('jobs')->andReturnSelf();
        $verification = OtpVerification::query()->create([
            'phone' => '+989121234567', 'code' => '001234', 'expires_at' => now()->addMinutes(5),
        ]);
        $job = new SendOtpSmsJob($verification->id);

        try {
            $job->handle(app(SmsManager::class));
            $this->fail('A provider rejection was accepted.');
        } catch (SmsDeliveryException $exception) {
            $this->assertSame([
                'sms_driver' => 'smsir', 'reason' => 'http_error', 'http_status' => 400, 'provider_status' => 999,
            ], $exception->safeContext());
            $this->assertNull($exception->getPrevious());
            $job->failed($exception);
        }

        foreach (['warning', 'error'] as $level) {
            Log::shouldHaveReceived($level)->once()->withArgs(function (string $message, array $context): bool {
                $this->assertSame(400, $context['http_status']);
                $this->assertSame(999, $context['provider_status']);
                $logged = json_encode($context);
                foreach (['001234', 'synthetic-test-key', 'synthetic-private-provider-message', '+989121234567'] as $secret) {
                    $this->assertStringNotContainsString($secret, $logged);
                }

                return true;
            });
        }
    }

    public function test_provider_response_failures_and_timeouts_raise_safe_errors(): void
    {
        $this->configureDriver();

        foreach ([
            [['status' => 0, 'message' => 'synthetic-provider-error'], 200],
            [['status' => 1], 503],
            ['malformed response', 200],
            [['status' => 'invalid'], 200],
            [['status' => 1], 302],
        ] as [$body, $status]) {
            Http::swap(new Factory);
            Http::preventStrayRequests();
            Http::fake(['api.sms.ir/v1/send/verify' => Http::response($body, $status)]);

            try {
                app(SmsManager::class)->sendOtp('+989121234567', '123456');
                $this->fail('Provider failure was accepted.');
            } catch (RuntimeException $exception) {
                $this->assertStringNotContainsString('synthetic-provider-error', $exception->getMessage());
                $this->assertStringNotContainsString('synthetic-test-key', $exception->getMessage());
            }
        }

        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(fn () => throw new ConnectionException('synthetic network failure'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('درخواست ارسال پیامک به سرویس‌دهنده نرسید.');
        app(SmsManager::class)->sendOtp('+989121234567', '123456');
    }

    public function test_a_queued_otp_uses_the_selected_driver_only_when_configured(): void
    {
        Queue::fake();
        $this->configureDriver();
        Http::preventStrayRequests();
        Http::fake(['api.sms.ir/v1/send/verify' => Http::response(['status' => 1], 200)]);

        $this->post(route('login.otp'), ['phone' => '09121234567'])->assertSessionHasNoErrors();
        Queue::assertPushed(SendOtpSmsJob::class, 1);
        Http::assertNothingSent();

        $verification = OtpVerification::query()->firstOrFail();
        (new SendOtpSmsJob($verification->id))->handle(app(SmsManager::class));
        Http::assertSentCount(1);
    }

    public function test_missing_template_or_parameter_fails_closed_before_otp_creation(): void
    {
        Queue::fake();
        app(SmsDriverRegistry::class)->save(null, ['smsir' => false], [
            'smsir' => ['api_key' => 'synthetic-test-key', 'template_id' => null, 'code_parameter' => 'OtpCode'],
        ], []);

        CmsSetting::set('sms.driver_state', json_encode([
            'default' => 'smsir',
            'enabled' => ['smsir' => true],
            'settings' => ['smsir' => ['template_id' => null, 'code_parameter' => 'OtpCode']],
        ]));
        Http::preventStrayRequests();

        $this->from(route('login'))->post(route('login.otp'), ['phone' => '09121234567'])
            ->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('otp_verifications', 0);
        Http::assertNothingSent();
        Queue::assertNotPushed(SendOtpSmsJob::class);
    }

    private function configureDriver(): void
    {
        app(SmsDriverRegistry::class)->save('smsir', ['smsir' => true], [
            'smsir' => ['api_key' => 'synthetic-test-key', 'template_id' => 731, 'code_parameter' => 'OtpCode'],
        ], []);
    }
}
