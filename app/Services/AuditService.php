<?php

namespace App\Services;

use App\Models\CmsAuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(string $action, ?Model $subject = null, array $payload = []): void
    {
        CmsAuditLog::record($action, $subject, $payload);
    }
}
