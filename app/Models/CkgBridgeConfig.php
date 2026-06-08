<?php

namespace App\Models;

use App\Support\CkgBridge\CkgBridgeUrlNormalizer;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function baseUrl(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => filled($value)
                ? CkgBridgeUrlNormalizer::normalize($value)
                : $value,
            set: fn (?string $value) => filled($value)
                ? CkgBridgeUrlNormalizer::normalize($value)
                : $value,
        );
    }

    public static function current(): ?self
    {
        return static::query()->where('name', 'CKG Bridge')->first();
    }
}
