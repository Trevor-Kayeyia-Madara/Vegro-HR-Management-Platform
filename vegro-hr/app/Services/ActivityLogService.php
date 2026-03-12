<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function log(string $action, ?int $companyId = null, ?string $entityType = null, ?int $entityId = null, array $metadata = []): ActivityLog
    {
        $request = app(Request::class);

        return ActivityLog::create([
            'company_id' => $companyId,
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
