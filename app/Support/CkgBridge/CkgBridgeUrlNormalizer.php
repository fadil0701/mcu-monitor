<?php

namespace App\Support\CkgBridge;

final class CkgBridgeUrlNormalizer
{
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
        $internalHost = strtolower((string) config('ckg_bridge.internal_host', '127.0.0.1'));
        $internalPort = (int) config('ckg_bridge.internal_port', 9006);

        if (in_array($host, ['127.0.0.1', 'localhost'], true)) {
            $host = $internalHost;
            $port = $internalPort;
        }

        if ($host === 'web') {
            $scheme = 'http';
            $port ??= 80;
        }

        if (self::isInternalDockerHost($host) || in_array($host, ['host.docker.internal', $internalHost], true)) {
            $scheme = 'http';
            $port ??= $internalPort;
        }

        $normalized = $scheme.'://'.$host;
        if ($port !== null && ! ($scheme === 'http' && $port === 80)) {
            $normalized .= ':'.$port;
        }

        return $normalized;
    }

    private static function isInternalDockerHost(string $host): bool
    {
        return preg_match('/^172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}$/', $host) === 1;
    }
}
