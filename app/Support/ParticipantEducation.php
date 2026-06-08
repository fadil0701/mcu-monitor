<?php

namespace App\Support;

class ParticipantEducation
{
    public const LEVELS = [
        'Tidak Sekolah',
        'SD',
        'SMP',
        'SMA/Sederajat',
        'Diploma',
        'Sarjana',
        'Magister',
        'Doktor',
    ];

    /**
     * @return list<string>
     */
    public static function levels(): array
    {
        return self::LEVELS;
    }
}
