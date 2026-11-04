<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class AuditLog
{
    /** @param  array<string, mixed>  $context */
    public static function record(string $event, array $context = []): void
    {
        Log::channel('audit')->info($event, $context);
    }
}
