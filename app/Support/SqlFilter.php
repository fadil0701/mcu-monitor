<?php

namespace App\Support;

/**
 * Whitelist helpers for query filter values — defense-in-depth against injection.
 */
final class SqlFilter
{
    /**
     * @param  list<string>  $allowed
     */
    public static function enum(?string $value, array $allowed): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return in_array($value, $allowed, true) ? $value : null;
    }

    public static function numericId(string $value, int $maxLength = 8): ?string
    {
        $pattern = '/^\d{1,'.$maxLength.'}$/';

        return preg_match($pattern, $value) === 1 ? $value : null;
    }
}
