<?php

namespace App\Support;

final class ScheduleExaminationTime
{
    public static function start(): string
    {
        return (string) config('mcu.examination_hours.start', '07:30');
    }

    public static function end(): string
    {
        return (string) config('mcu.examination_hours.end', '10:00');
    }

    public static function defaultLocation(): string
    {
        return McuScheduleSettings::defaultLocation();
    }

    public static function isAllowed(string $time): bool
    {
        $minutes = self::toMinutes(self::normalize($time));

        return $minutes >= self::startMinutes() && $minutes <= self::endMinutes();
    }

    public static function allowedRangeMessage(): string
    {
        return 'Jam pemeriksaan harus antara '.self::start().' dan '.self::end().'.';
    }

    private static function normalize(string $time): string
    {
        if (preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            return $time;
        }

        return substr($time, 0, 5);
    }

    private static function toMinutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }

    private static function startMinutes(): int
    {
        return self::toMinutes(self::start());
    }

    private static function endMinutes(): int
    {
        return self::toMinutes(self::end());
    }
}
