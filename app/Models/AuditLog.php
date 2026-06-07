<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Log an action
     */
    public static function log(
        string $action,
        string $modelType,
        $modelId = null,
        array $oldValues = null,
        array $newValues = null,
        string $description = null
    ): void {
        $user = auth()->user();
        
        self::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * Get audit logs for a specific model
     */
    public static function forModel(string $modelType, $modelId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get audit logs for a specific user
     */
    public static function forUser($userId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent audit logs
     */
    public static function recent(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get the user that performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get formatted action description
     */
    public function getFormattedActionAttribute(): string
    {
        $modelName = class_basename($this->model_type);
        
        return match($this->action) {
            'created' => "Created {$modelName}",
            'updated' => "Updated {$modelName}",
            'deleted' => "Deleted {$modelName}",
            'viewed' => "Viewed {$modelName}",
            'exported' => "Exported {$modelName}",
            'imported' => "Imported {$modelName}",
            'sent_email' => "Sent email for {$modelName}",
            'sent_whatsapp' => "Sent WhatsApp for {$modelName}",
            default => ucfirst($this->action) . " {$modelName}",
        };
    }

    /**
     * Get changes summary
     */
    public function getChangesSummaryAttribute(): string
    {
        if (!$this->old_values || !$this->new_values) {
            return 'No changes recorded';
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[] = "{$key}: '{$oldValue}' → '{$newValue}'";
            }
        }

        return implode(', ', $changes);
    }
}

