<?php

namespace App\Support\CkgBridge;

use App\Models\CkgBridgeConfig;

final class CkgBridgeSettings
{
    public static function baseUrl(): string
    {
        $config = CkgBridgeConfig::current();
        if ($config?->is_active && filled($config->base_url)) {
            return rtrim((string) $config->base_url, '/');
        }

        return rtrim((string) config('ckg_bridge.base_url', 'http://127.0.0.1:9006'), '/');
    }

    public static function apiKey(): string
    {
        $config = CkgBridgeConfig::current();
        if ($config?->is_active && filled($config->api_key)) {
            return (string) $config->api_key;
        }

        return (string) config('ckg_bridge.api_key', '');
    }

    public static function apiKeyHeader(): string
    {
        $config = CkgBridgeConfig::current();
        if ($config?->is_active && filled($config->api_key_header)) {
            return (string) $config->api_key_header;
        }

        return (string) config('ckg_bridge.api_key_header', 'X-Mcu-Api-Key');
    }

    public static function perPage(): int
    {
        $config = CkgBridgeConfig::current();
        if ($config?->is_active && $config->per_page > 0) {
            return (int) $config->per_page;
        }

        return (int) config('ckg_bridge.per_page', 100);
    }

    public static function timeoutSeconds(): int
    {
        $config = CkgBridgeConfig::current();
        if ($config?->is_active && $config->timeout_seconds > 0) {
            return (int) $config->timeout_seconds;
        }

        return 60;
    }
}
