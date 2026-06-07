<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return match($setting->type) {
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true),
            'text' => $setting->value,
            default => $setting->value,
        };
    }

    public static function setValue(string $key, $value, string $type = 'string', string $group = 'general', string $description = null): void
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            $setting = new static();
            $setting->key = $key;
            $setting->type = $type;
            $setting->group = $group;
            $setting->description = $description;
        }

        $setting->value = match($type) {
            'boolean' => (string) $value,
            'json' => json_encode($value),
            default => (string) $value,
        };

        $setting->save();
    }

    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->keyBy('key')
            ->map(function ($setting) {
                return match($setting->type) {
                    'boolean' => (bool) $setting->value,
                    'json' => json_decode($setting->value, true),
                    default => $setting->value,
                };
            })
            ->toArray();
    }
}
