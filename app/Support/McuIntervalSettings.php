<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Sumber tunggal interval MCU: nilai di Pengaturan Admin (DB),
 * fallback ke config/mcu.php (.env MCU_INTERVAL_YEARS) hanya jika belum diset.
 */
final class McuIntervalSettings
{
    public static function years(): int
    {
        $value = Setting::getValue('mcu_interval_years');

        if ($value !== null && $value !== '') {
            return max(1, (int) $value);
        }

        return max(1, (int) config('mcu.interval_years', 3));
    }

    /**
     * Tahun kalender paling awal agar peserta boleh mengajukan lagi
     * setelah MCU terakhir di tahun $lastMcuYear.
     */
    public static function eligibleCalendarYear(int $lastMcuYear): int
    {
        return $lastMcuYear + self::years();
    }

    /**
     * Masih dalam interval (belum boleh ajukan ulang) bila selisih
     * tahun berjalan dengan tahun MCU terakhir kurang dari interval.
     */
    public static function isWithinInterval(?int $lastMcuYear, ?int $currentYear = null): bool
    {
        if ($lastMcuYear === null) {
            return false;
        }

        $currentYear ??= (int) now()->format('Y');

        return ($currentYear - $lastMcuYear) < self::years();
    }
}
