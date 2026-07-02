<?php

namespace App\Support;

use App\Models\Setting;

final class McuScheduleSettings
{
    public static function dailyQuota(): int
    {
        $value = Setting::getValue('mcu_daily_quota');

        if ($value !== null && $value !== '') {
            return max(0, (int) $value);
        }

        return max(0, (int) config('mcu.daily_quota', 100));
    }

    public static function defaultLocation(): string
    {
        $value = Setting::getValue('mcu_default_location');

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return (string) config('mcu.default_location', 'Klinik Utama Balaikota');
    }
}
