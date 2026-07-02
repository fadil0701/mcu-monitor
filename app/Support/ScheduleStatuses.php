<?php

namespace App\Support;

final class ScheduleStatuses
{
    /** @var list<string> */
    public const ALL = [
        'Menunggu Konfirmasi',
        'Terjadwal',
        'Selesai',
        'Batal',
        'Ditolak',
    ];

    /** @var list<string> */
    public const QUOTA_COUNTED = [
        'Terjadwal',
        'Selesai',
    ];

    public const PENDING_ADMIN = 'Menunggu Konfirmasi';
}
