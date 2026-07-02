<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class SqlDialect
{
    public static function isPgsql(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    public static function monthBucket(string $expression): string
    {
        return self::isPgsql()
            ? "TO_CHAR({$expression}, 'YYYY-MM')"
            : "DATE_FORMAT({$expression}, '%Y-%m')";
    }
}
