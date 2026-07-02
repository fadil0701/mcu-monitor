<?php

namespace App\Support;

use App\Models\McuWorkCalendarClosure;
use App\Models\McuWorkCalendarSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class McuWorkCalendar
{
    private const CACHE_KEY_SETTINGS = 'mcu_work_calendar.settings';

    private const CACHE_KEY_CLOSURES_PREFIX = 'mcu_work_calendar.closures.';

    private const CACHE_TTL_SECONDS = 300;

    public function blockWeekends(): bool
    {
        return (bool) Cache::remember(self::CACHE_KEY_SETTINGS, self::CACHE_TTL_SECONDS, function () {
            return McuWorkCalendarSetting::current()->block_weekends;
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_SETTINGS);

        $year = (int) now()->format('Y');
        for ($y = $year - 1; $y <= $year + 2; $y++) {
            Cache::forget(self::CACHE_KEY_CLOSURES_PREFIX.$y);
        }
    }

    public function isWeekend(Carbon $date): bool
    {
        return $date->isWeekend();
    }

    public function closureForDate(Carbon $date): ?McuWorkCalendarClosure
    {
        return $this->closuresForYear((int) $date->format('Y'))->get($date->toDateString());
    }

    public function isClosed(Carbon $date): bool
    {
        if ($this->blockWeekends() && $this->isWeekend($date)) {
            return true;
        }

        return $this->closureForDate($date) !== null;
    }

    public function isBookable(Carbon|string $date): bool
    {
        $parsed = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();

        return ! $this->isClosed($parsed);
    }

    public function closureReason(Carbon|string $date): ?string
    {
        $parsed = $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay();

        if ($this->blockWeekends() && $this->isWeekend($parsed)) {
            return $parsed->isSaturday()
                ? 'Hari Sabtu (bukan hari kerja)'
                : 'Hari Minggu (bukan hari kerja)';
        }

        $closure = $this->closureForDate($parsed);
        if ($closure === null) {
            return null;
        }

        $typeLabel = config('mcu_work_calendar.closure_types.'.$closure->type, $closure->type);

        return $closure->label !== ''
            ? $closure->label.' ('.$typeLabel.')'
            : $typeLabel;
    }

    public function unbookableReason(Carbon|string $date): ?string
    {
        if ($this->isBookable($date)) {
            return null;
        }

        return $this->closureReason($date) ?? 'Tanggal tidak tersedia untuk pemeriksaan MCU.';
    }

    /**
     * @return array<string, mixed>
     */
    public function dayPayload(Carbon $date): array
    {
        $bookable = $this->isBookable($date);

        return [
            'date' => $date->toDateString(),
            'bookable' => $bookable,
            'closure_reason' => $bookable ? null : $this->closureReason($date),
            'is_weekend' => $date->isWeekend(),
            'is_closure' => $this->closureForDate($date) !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function monthPayload(int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $days = [];

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $days[] = $this->dayPayload($cursor->copy());
        }

        return [
            'year' => $year,
            'month' => $month,
            'block_weekends' => $this->blockWeekends(),
            'days' => $days,
        ];
    }

    /**
     * @return Collection<string, McuWorkCalendarClosure>
     */
    private function closuresForYear(int $year): Collection
    {
        return Cache::remember(self::CACHE_KEY_CLOSURES_PREFIX.$year, self::CACHE_TTL_SECONDS, function () use ($year) {
            return McuWorkCalendarClosure::query()
                ->whereYear('closure_date', $year)
                ->orderBy('closure_date')
                ->get()
                ->keyBy(fn (McuWorkCalendarClosure $closure) => $closure->closure_date->toDateString());
        });
    }
}
