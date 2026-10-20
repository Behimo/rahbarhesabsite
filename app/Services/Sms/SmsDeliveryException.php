<?php

namespace App\Services\Sms;

use RuntimeException;

class SmsDeliveryException extends RuntimeException
{
    public function __construct(
        private readonly string $driver,
        private readonly string $reason,
        private readonly ?int $httpStatus = null,
        private readonly ?int $providerStatus = null,
    ) {
        $message = $reason === 'connection_failed'
            ? 'درخواست ارسال پیامک به سرویس‌دهنده نرسید.'
            : 'ارسال پیامک توسط سرویس‌دهنده پذیرفته نشد.';

        parent::__construct($message);
    }

    public function safeContext(): array
    {
        return [
            'sms_driver' => $this->driver,
            'reason' => $this->reason,
            'http_status' => $this->httpStatus,
            'provider_status' => $this->providerStatus,
        ];
    }
}
