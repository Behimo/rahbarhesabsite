<?php

namespace Tests\Support;

use App\Contracts\OtpSmsDriver;

class FakeOtpSmsDriver implements OtpSmsDriver
{
    /** @var list<array{phone: string, code: string}> */
    public array $sent = [];

    public function sendOtp(string $phone, string $code): void
    {
        $this->sent[] = compact('phone', 'code');
    }
}
