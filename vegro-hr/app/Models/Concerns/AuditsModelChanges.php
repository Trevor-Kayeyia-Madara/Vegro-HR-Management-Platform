<?php

namespace App\Models\Concerns;

use App\Models\ModelChangeAudit;
use Illuminate\Http\Request;

trait AuditsModelChanges
{
    protected static function bootAuditsModelChanges(): void
    {
        static::created(function ($model) {
            $model->writeChangeAudit('created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            if (empty($changes)) {
                return;
            }

            $before = [];
            foreach (array_keys($changes) as $key) {
                $before[$key] = $model->getOriginal($key);
            }
            $model->writeChangeAudit('updated', $before, $changes);
        });

        static::deleted(function ($model) {
            $model->writeChangeAudit('deleted', $model->getOriginal(), null);
        });
    }

    protected function writeChangeAudit(string $action, ?array $beforeData, ?array $afterData): void
    {
        try {
            $request = app()->bound(Request::class) ? app(Request::class) : null;
            $companyId = $this->company_id ?? auth()->user()?->company_id;

            ModelChangeAudit::create([
                'company_id' => $companyId,
                'actor_user_id' => auth()->id(),
                'entity_type' => static::class,
                'entity_id' => (int) $this->getKey(),
                'action' => $action,
                'before_data' => $beforeData,
                'after_data' => $afterData,
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            // keep business operation alive even if audit logging fails
        }
    }
}
