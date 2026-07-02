<?php

namespace Tests\Unit;

use App\Models\McuWorkCalendarClosure;
use App\Support\McuExaminationCalendar;
use App\Support\McuWorkCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McuExaminationCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekend_is_not_bookable_when_blocked(): void
    {
        $saturday = Carbon::parse('next saturday')->toDateString();

        $this->assertFalse(McuExaminationCalendar::isBookable($saturday));
        $this->assertStringContainsString('Sabtu', (string) McuExaminationCalendar::unbookableReason($saturday));
    }

    public function test_weekday_is_bookable_without_closures(): void
    {
        $weekday = Carbon::parse('next monday')->toDateString();

        $this->assertTrue(McuExaminationCalendar::isBookable($weekday));
        $this->assertNull(McuExaminationCalendar::unbookableReason($weekday));
    }

    public function test_closure_makes_date_unbookable(): void
    {
        McuWorkCalendarClosure::query()->create([
            'closure_date' => '2026-12-25',
            'type' => 'libur_nasional',
            'label' => 'Natal',
        ]);

        app(McuWorkCalendar::class)->clearCache();

        $this->assertFalse(McuExaminationCalendar::isBookable('2026-12-25'));
        $this->assertStringContainsString('Natal', (string) McuExaminationCalendar::unbookableReason('2026-12-25'));
    }

    public function test_daily_quota_reads_from_settings(): void
    {
        \App\Models\Setting::setValue('mcu_daily_quota', '42', 'string', 'schedule_quota');

        $this->assertSame(42, \App\Support\McuScheduleSettings::dailyQuota());
    }
}
