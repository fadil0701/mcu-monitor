<?php

namespace App\Support;

use App\Models\Schedule;

final class McuDailyQuota
{
    public static function limit(): int
    {
        return McuScheduleSettings::dailyQuota();
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
            ->whereIn('status', ScheduleStatuses::QUOTA_COUNTED)
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
        if (! McuExaminationCalendar::isBookable($date)) {
            return false;
        }

        $location ??= ScheduleExaminationTime::defaultLocation();

        return Schedule::hasQuotaAvailable($date, $location);
    }

    /**
     * @return array{
     *     limit: int,
     *     booked: int,
     *     remaining: ?int,
     *     available: bool,
     *     bookable: bool,
     *     bookable_reason: ?string,
     *     unlimited: bool
     * }
     */
    public static function snapshot(string $date, ?string $location = null): array
    {
        $limit = self::limit();
        $booked = self::bookedCount($date, $location);
        $unlimited = self::isUnlimited();
        $bookableReason = McuExaminationCalendar::unbookableReason($date);
        $bookable = $bookableReason === null;
        $quotaAvailable = $unlimited || $booked < $limit;

        return [
            'limit' => $limit,
            'booked' => $booked,
            'remaining' => $unlimited ? null : max(0, $limit - $booked),
            'available' => $bookable && $quotaAvailable,
            'bookable' => $bookable,
            'bookable_reason' => $bookableReason,
            'unlimited' => $unlimited,
        ];
    }

    /**
     * @return array{
     *     year: int,
     *     month: int,
     *     month_label: string,
     *     limit: int,
     *     unlimited: bool,
     *     days: list<array{
     *         date: string,
     *         day: int,
     *         booked: int,
     *         remaining: ?int,
     *         available: bool,
     *         bookable: bool,
     *         bookable_reason: ?string,
     *         is_past: bool,
     *         is_closed: bool
     *     }>
     * }
     */
    public static function monthCalendar(int $year, int $month, ?string $location = null): array
    {
        $location ??= ScheduleExaminationTime::defaultLocation();
        $start = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $today = now()->startOfDay();
        $limit = self::limit();
        $unlimited = self::isUnlimited();

        $bookedByDate = Schedule::query()
            ->whereYear('tanggal_pemeriksaan', $year)
            ->whereMonth('tanggal_pemeriksaan', $month)
            ->where('lokasi_pemeriksaan', $location)
            ->whereIn('status', ScheduleStatuses::QUOTA_COUNTED)
            ->get()
            ->groupBy(fn (Schedule $schedule) => $schedule->tanggal_pemeriksaan->format('Y-m-d'))
            ->map->count();

        $days = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dateStr = $cursor->toDateString();
            $booked = (int) ($bookedByDate[$dateStr] ?? 0);
            $bookableReason = app(McuWorkCalendar::class)->unbookableReason($cursor);
            $bookable = $bookableReason === null;
            $quotaAvailable = $unlimited || $booked < $limit;
            $isPast = $cursor->lt($today);

            $days[] = [
                'date' => $dateStr,
                'day' => (int) $cursor->format('j'),
                'booked' => $booked,
                'remaining' => $unlimited ? null : max(0, $limit - $booked),
                'available' => $bookable && $quotaAvailable && ! $isPast,
                'bookable' => $bookable,
                'bookable_reason' => $bookableReason,
                'is_past' => $isPast,
                'is_closed' => ! $bookable,
            ];

            $cursor->addDay();
        }

        return [
            'year' => $year,
            'month' => $month,
            'month_label' => self::indonesianMonthLabel($month).' '.$year,
            'limit' => $limit,
            'unlimited' => $unlimited,
            'days' => $days,
        ];
    }

    public static function indonesianMonthLabel(int $month): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$month] ?? '';
    }
}
