<?php

namespace App\Services\Sms;

use RuntimeException;
use Throwable;

class SmsConfigurationException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('سرویس پیامک در حال حاضر در دسترس نیست.', 0, $previous);
    }
}
