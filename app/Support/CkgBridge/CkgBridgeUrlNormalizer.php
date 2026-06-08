<?php

namespace App\Support\CkgBridge;

final class CkgBridgeUrlNormalizer
{
    /**
     * Base URL saja — tanpa path /api/bridge/mcu/* dan tanpa subpath portal /sikerja.
     */
    public static function normalize(string $url): string
    {
        $url = rtrim(trim($url), '/');

        $url = preg_replace('#/api/bridge/mcu(?:/(?:health|participants))?/?$#i', '', $url) ?? $url;
        $url = preg_replace('#/sikerja/?$#i', '', $url) ?? $url;
        $url = rtrim($url, '/');

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return $url;
        }

        $host = strtolower((string) $parts['host']);
        $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $port = $parts['port'] ?? null;

        if (in_array($host, ['10.15.101.117', '127.0.0.1', 'localhost'], true)) {
            $scheme = 'http';
            $port ??= 9006;
        }

        $normalized = $scheme.'://'.$host;
        if ($port !== null) {
            $normalized .= ':'.$port;
        }

        return $normalized;
    }
}
