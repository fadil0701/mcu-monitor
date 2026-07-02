<?php

namespace App\Support;

use Carbon\Carbon;

final class McuExaminationCalendar
{
    private static function calendar(): McuWorkCalendar
    {
        return app(McuWorkCalendar::class);
    }

    public static function isWeekend(Carbon $date): bool
    {
        return self::calendar()->isWeekend($date);
    }

    public static function isBookable(string|Carbon $date): bool
    {
        return self::calendar()->isBookable($date);
    }

    public static function unbookableReason(string|Carbon $date): ?string
    {
        return self::calendar()->unbookableReason($date);
    }

    /**
     * @return array{
     *     daily_quota: int,
     *     default_location: string,
     *     block_weekends: bool
     * }
     */
    public static function clientConfig(): array
    {
        $calendar = self::calendar();

        return [
            'daily_quota' => McuScheduleSettings::dailyQuota(),
            'default_location' => McuScheduleSettings::defaultLocation(),
            'block_weekends' => $calendar->blockWeekends(),
        ];
    }
}
