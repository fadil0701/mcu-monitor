<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    /**
     * Boot the auditable trait
     */
    protected static function bootAuditable()
    {
        static::created(function (Model $model) {
            AuditLog::log('created', get_class($model), $model->id, null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $changes = $model->getChanges();
            if (!empty($changes)) {
                $original = $model->getOriginal();
                $oldValues = array_intersect_key($original, $changes);
                AuditLog::log('updated', get_class($model), $model->id, $oldValues, $changes);
            }
        });

        static::deleted(function (Model $model) {
            AuditLog::log('deleted', get_class($model), $model->id, $model->getAttributes(), null);
        });
    }

    /**
     * Log a custom action
     */
    public function logAction(string $action, string $description = null, array $data = null): void
    {
        AuditLog::log(
            $action,
            get_class($this),
            $this->id,
            null,
            $data,
            $description
        );
    }

    /**
     * Get audit logs for this model
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'model', 'model_type', 'model_id');
    }

    /**
     * Get recent audit logs for this model
     */
    public function getRecentAuditLogs(int $limit = 10)
    {
        return $this->auditLogs()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}

