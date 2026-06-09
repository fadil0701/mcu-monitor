<?php

namespace App\Support;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class NotificationBadgeCounts
{
    public static function unreadFor(?User $user = null): int
    {
        $user ??= Auth::user();

        if (! $user) {
            return 0;
        }

        return (int) $user->unreadNotifications()->count();
    }

    public static function pendingReschedules(): int
    {
        if (! Auth::check() || ! Auth::user()->isAdmin()) {
            return 0;
        }

        return (int) Schedule::query()
            ->where('reschedule_requested', true)
            ->count();
    }
}
