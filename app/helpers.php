<?php

/**
 * URL aset statis dengan query ?v=filemtime agar browser tidak memakai CSS/JS lama setelah deploy.
 */
function versioned_asset(string $path): string
{
    $normalized = ltrim($path, '/');
    $fullPath = public_path($normalized);
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();

    return asset($normalized).'?v='.$version;
}
