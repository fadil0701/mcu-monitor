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

        if (in_array($host, ['127.0.0.1', 'localhost'], true)) {
            $host = (string) config('ckg_bridge.internal_host', '10.15.101.117');
            $port = (int) config('ckg_bridge.internal_port', 9006);
        }

        if ($host === 'web') {
            $scheme = 'http';
            $port ??= 80;
        }

        if (self::isInternalDockerHost($host)) {
            $scheme = 'http';
            $port ??= (int) config('ckg_bridge.internal_port', 9006);
        } elseif (in_array($host, ['10.15.101.117', 'host.docker.internal'], true)) {
            $scheme = 'http';
            $port ??= (int) config('ckg_bridge.internal_port', 9006);
        }

        $normalized = $scheme.'://'.$host;
        if ($port !== null && ! ($scheme === 'http' && $port === 80)) {
            $normalized .= ':'.$port;
        }

        return $normalized;
    }

    /**
     * IP gateway jaringan Docker (172.16–172.31) — jangan remap ke IP VM.
     */
    private static function isInternalDockerHost(string $host): bool
    {
        return preg_match('/^172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}$/', $host) === 1;
    }
}
