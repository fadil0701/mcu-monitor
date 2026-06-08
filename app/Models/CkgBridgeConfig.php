<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CkgBridgeConfig extends Model
{
    protected $fillable = [
        'name',
        'base_url',
        'api_key',
        'api_key_header',
        'per_page',
        'timeout_seconds',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return static::query()->where('name', 'CKG Bridge')->first();
    }
}
