<?php

namespace App\Services\Sms;

use App\Contracts\OtpSmsDriver;
use App\Support\PhoneNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmsIrDriver implements OtpSmsDriver
{
    private const VERIFY_URL = 'https://api.sms.ir/v1/send/verify';

    public function __construct(private SmsDriverRegistry $registry) {}

    public function sendOtp(string $phone, string $code): void
    {
        $settings = $this->registry->configuration('smsir');

        if (! PhoneNormalizer::isValidIranMobile($phone)) {
            throw new RuntimeException('شماره موبایل معتبر نیست.');
        }

        try {
            $response = Http::withHeaders(['x-api-key' => $settings['api_key']])
                ->acceptJson()
                ->asJson()
                ->connectTimeout(3)
                ->timeout(10)
                ->withOptions(['allow_redirects' => false])
                ->post(self::VERIFY_URL, [
                    'mobile' => PhoneNormalizer::toLocal($phone),
                    'templateId' => $settings['template_id'],
                    'parameters' => [
                        ['name' => $settings['code_parameter'], 'value' => $code],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new SmsDeliveryException('smsir', 'connection_failed');
        }

        $body = $response->json();
        $status = is_array($body) ? ($body['status'] ?? null) : null;
        $providerStatus = (is_int($status) || is_string($status))
            && preg_match('/\A-?\d{1,9}\z/D', (string) $status)
                ? (int) $status
                : null;

        if (! $response->successful()) {
            throw new SmsDeliveryException('smsir', 'http_error', $response->status(), $providerStatus);
        }

        if ($providerStatus === null) {
            throw new SmsDeliveryException('smsir', 'invalid_response', $response->status());
        }

        if (! in_array($status, [1, '1'], true)) {
            throw new SmsDeliveryException('smsir', 'provider_rejected', $response->status(), $providerStatus);
        }
    }
}
