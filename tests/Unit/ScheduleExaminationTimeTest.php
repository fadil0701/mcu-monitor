<?php

namespace Tests\Unit;

use App\Support\ScheduleExaminationTime;
use Tests\TestCase;

class ScheduleExaminationTimeTest extends TestCase
{
    public function test_allows_times_within_window(): void
    {
        $this->assertTrue(ScheduleExaminationTime::isAllowed('07:30'));
        $this->assertTrue(ScheduleExaminationTime::isAllowed('09:00'));
        $this->assertTrue(ScheduleExaminationTime::isAllowed('10:00'));
    }

    public function test_rejects_times_outside_window(): void
    {
        $this->assertFalse(ScheduleExaminationTime::isAllowed('07:00'));
        $this->assertFalse(ScheduleExaminationTime::isAllowed('10:30'));
        $this->assertFalse(ScheduleExaminationTime::isAllowed('14:00'));
    }
}
