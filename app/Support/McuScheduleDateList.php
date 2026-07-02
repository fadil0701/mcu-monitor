<?php

namespace App\Support;

use Carbon\Carbon;

final class McuScheduleDateList
{
    /**
     * @return list<string> Daftar tanggal YYYY-MM-DD
     */
    public static function parse(string $raw): array
    {
        $dates = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $datePart = trim(explode('|', $line, 2)[0]);

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePart) !== 1) {
                continue;
            }

            try {
                $dates[] = Carbon::createFromFormat('Y-m-d', $datePart)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return array_values(array_unique($dates));
    }

    /**
     * @param  list<string>  $dates
     */
    public static function format(array $dates): string
    {
        sort($dates);

        return implode("\n", $dates);
    }

    public static function validationError(string $raw): ?string
    {
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $lineNumber => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $datePart = trim(explode('|', $line, 2)[0]);

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePart) !== 1) {
                return 'Baris '.($lineNumber + 1).': format tanggal harus YYYY-MM-DD (opsional: | keterangan).';
            }

            try {
                Carbon::createFromFormat('Y-m-d', $datePart);
            } catch (\Throwable) {
                return 'Baris '.($lineNumber + 1).': tanggal tidak valid.';
            }
        }

        return null;
    }
}
