<?php

namespace App\Contracts;

interface OtpSmsDriver
{
    /**
     * The phone is an Iranian mobile in E.164 format (+98...). Return only
     * when the provider accepts the request; throw on failure.
     */
    public function sendOtp(string $phone, string $code): void;
}
