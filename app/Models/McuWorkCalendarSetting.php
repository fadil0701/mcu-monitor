<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McuWorkCalendarSetting extends Model
{
    protected $fillable = [
        'block_weekends',
    ];

    protected function casts(): array
    {
        return [
            'block_weekends' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['block_weekends' => true]);
    }
}
