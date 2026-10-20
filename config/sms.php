<?php

use App\Services\Sms\SmsIrDriver;

return [
    // Used only before the admin panel saves a driver selection.
    'default' => env('SMS_DRIVER'),

    'drivers' => [
        'smsir' => [
            'class' => SmsIrDriver::class,
            'label' => 'sms.ir',
            'fields' => [
                'api_key' => ['label' => 'کلید API', 'type' => 'secret', 'required' => true],
                'template_id' => ['label' => 'شناسه عددی قالب پیامک', 'type' => 'integer', 'required' => true],
                'code_parameter' => ['label' => 'نام متغیر کد در قالب', 'type' => 'identifier', 'required' => true],
            ],
        ],
    ],
];
