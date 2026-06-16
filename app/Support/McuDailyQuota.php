<?php

namespace App\Support;

use App\Models\Schedule;

final class McuDailyQuota
{
    public static function limit(): int
    {
        return max(0, (int) config('mcu.daily_quota', 100));
    }

    public static function isUnlimited(): bool
    {
        return self::limit() <= 0;
    }

    public static function bookedCount(string $date, ?string $location = null): int
    {
        $location ??= ScheduleExaminationTime::defaultLocation();

        return Schedule::query()
            ->whereDate('tanggal_pemeriksaan', $date)
            ->where('lokasi_pemeriksaan', $location)
            ->whereIn('status', ['Terjadwal', 'Selesai'])
            ->count();
    }

    public static function remaining(string $date, ?string $location = null): ?int
    {
        if (self::isUnlimited()) {
            return null;
        }

        return max(0, self::limit() - self::bookedCount($date, $location));
    }

    public static function isAvailable(string $date, ?string $location = null): bool
    {
        $location ??= ScheduleExaminationTime::defaultLocation();

        return Schedule::hasQuotaAvailable($date, $location);
    }

    /**
     * @return array{limit: int, booked: int, remaining: ?int, available: bool, unlimited: bool}
     */
    public static function snapshot(string $date, ?string $location = null): array
    {
        $limit = self::limit();
        $booked = self::bookedCount($date, $location);
        $unlimited = self::isUnlimited();

        return [
            'limit' => $limit,
            'booked' => $booked,
            'remaining' => $unlimited ? null : max(0, $limit - $booked),
            'available' => $unlimited || $booked < $limit,
            'unlimited' => $unlimited,
        ];
    }
}
