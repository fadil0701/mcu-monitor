<?php

namespace App\Support;

use App\Models\Schedule;
use App\Models\User;
use App\Notifications\ParticipantScheduleNotification;

final class ScheduleParticipantNotifier
{
    public static function notify(Schedule $schedule, string $type, array $extra = []): void
    {
        $user = User::query()
            ->where('nik_ktp', $schedule->nik_ktp)
            ->first();

        if (! $user) {
            return;
        }

        $user->notify(new ParticipantScheduleNotification($type, $schedule, $extra));
    }
}
