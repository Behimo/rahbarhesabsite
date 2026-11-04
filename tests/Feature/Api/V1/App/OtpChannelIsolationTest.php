<?php

namespace Tests\Feature\Api\V1\App;

use App\Services\OtpRequestException;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;

class OtpChannelIsolationTest extends MobileApiTestCase
{
    public function test_web_and_app_codes_cannot_be_used_across_channels(): void
    {
        $otp = app(OtpService::class);
        $otp->send('09124445566', OtpService::PURPOSE_WEB);
        $webCode = $otp->peekLatestCode('09124445566', OtpService::PURPOSE_WEB);

        Cache::forget('otp:cooldown:+989124445566');
        $challenge = $otp->send('09124445566', OtpService::PURPOSE_APP);
        $appCode = $otp->peekLatestCode('09124445566', OtpService::PURPOSE_APP);

        $this->assertNotSame($webCode, $appCode);
        $this->assertFalse($otp->verify('09124445566', $appCode));
        $this->assertFalse($otp->verify('09124445566', $webCode, OtpService::PURPOSE_APP, $challenge));
        $this->assertFalse($otp->verify('09124445566', $appCode, OtpService::PURPOSE_APP, 'wrong-challenge-value-wrong-challenge-val'));
        $this->assertTrue($otp->verify('09124445566', $appCode, OtpService::PURPOSE_APP, $challenge));
        $this->assertFalse($otp->verify('09124445566', $appCode, OtpService::PURPOSE_APP, $challenge));
        $this->assertTrue($otp->verify('09124445566', $webCode));
    }

    public function test_repeated_failures_lock_the_phone_number(): void
    {
        $otp = app(OtpService::class);
        $challenge = $otp->send('09125556677', OtpService::PURPOSE_APP);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->assertFalse($otp->verify('09125556677', '000000', OtpService::PURPOSE_APP, $challenge));
        }

        try {
            $otp->send('09125556677', OtpService::PURPOSE_APP);
            $this->fail('A locked phone was allowed to request another code.');
        } catch (OtpRequestException $exception) {
            $this->assertStringContainsString('تلاش', $exception->getMessage());
        }
    }
}
