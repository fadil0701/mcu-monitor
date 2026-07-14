<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class SqlDialect
{
    public static function isPgsql(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    public static function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    public static function yearExpr(string $expression): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "EXTRACT(YEAR FROM {$expression})",
            'sqlite' => "CAST(strftime('%Y', {$expression}) AS INTEGER)",
            default => "YEAR({$expression})",
        };
    }

    public static function monthBucket(string $expression): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "TO_CHAR({$expression}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$expression})",
            default => "DATE_FORMAT({$expression}, '%Y-%m')",
        };
    }
}
