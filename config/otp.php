<?php

return [
    'length' => 6,
    'expires_minutes' => 5,
    'resend_cooldown_seconds' => 60,
    'max_attempts' => 5,

    'driver' => env('OTP_DRIVER', 'ippanel'),

    'ippanel' => [
        'api_key' => env('IPPANEL_API_KEY'),
        'sender' => env('IPPANEL_SENDER', '+983000505'),
        'pattern_code' => env('IPPANEL_OTP_PATTERN'),
        'base_url' => env('IPPANEL_BASE_URL', 'https://api2.ippanel.com/api/v1'),
    ],
];
