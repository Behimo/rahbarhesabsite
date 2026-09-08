<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpPanelSmsService
{
    public function sendOtp(string $phone, string $code): bool
    {
        $apiKey = config('otp.ippanel.api_key');
        $pattern = config('otp.ippanel.pattern_code');
        $sender = config('otp.ippanel.sender');
        $baseUrl = rtrim(config('otp.ippanel.base_url'), '/');

        if (! $apiKey) {
            if (app()->environment('local', 'testing')) {
                Log::info('OTP (dev mode)', ['phone' => $phone, 'code' => $code]);

                return true;
            }

            throw new \RuntimeException('IPPANEL_API_KEY تنظیم نشده است.');
        }

        if ($pattern) {
            $response = Http::withHeaders([
                'Authorization' => 'AccessKey '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post($baseUrl.'/sms/pattern/normal/send', [
                'code' => $pattern,
                'sender' => $sender,
                'recipient' => $phone,
                'variable' => ['code' => $code],
            ]);
        } else {
            $message = 'کد تأیید راهبر حساب: '.$code;

            $response = Http::withHeaders([
                'Authorization' => 'AccessKey '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post($baseUrl.'/sms/send/webservice/single', [
                'originator' => $sender,
                'recipient' => $phone,
                'message' => $message,
            ]);
        }

        if (! $response->successful()) {
            Log::error('IPPanel SMS failed', ['response' => $response->json(), 'status' => $response->status()]);

            return false;
        }

        return true;
    }
}
