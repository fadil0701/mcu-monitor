<?php

namespace App\Support;

/**
 * Helpers for parameterized LIKE queries — escapes % and _ wildcards in user input.
 */
final class SqlLike
{
    public static function escapeWildcards(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public static function contains(string $value): string
    {
        return '%'.self::escapeWildcards($value).'%';
    }
}
