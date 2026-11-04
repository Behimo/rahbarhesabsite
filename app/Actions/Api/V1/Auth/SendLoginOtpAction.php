<?php

namespace App\Actions\Api\V1\Auth;

use App\Exceptions\Api\ApiException;
use App\Services\OtpRequestException;
use App\Services\OtpService;
use App\Services\Sms\SmsConfigurationException;
use App\Services\Sms\SmsDeliveryException;

class SendLoginOtpAction
{
    public function __construct(private OtpService $otp)
    {
    }

    public function handle(string $phone): string
    {
        try {
            return $this->otp->send($phone, OtpService::PURPOSE_APP);
        } catch (OtpRequestException $exception) {
            throw new ApiException($exception->getMessage(), 'otp_throttled', 429);
        } catch (SmsConfigurationException | SmsDeliveryException) {
            throw new ApiException('ارسال کد تأیید ناموفق بود. کمی بعد دوباره تلاش کنید.', 'otp_delivery_failed', 503);
        } catch (\InvalidArgumentException $exception) {
            throw new ApiException($exception->getMessage(), 'otp_invalid_phone', 422);
        }
    }
}
