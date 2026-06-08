<?php

namespace Tests\Unit;

use App\Support\CkgBridge\CkgBridgeUrlNormalizer;
use Tests\TestCase;

class CkgBridgeUrlNormalizerTest extends TestCase
{
    public function test_strips_api_path_and_subpath(): void
    {
        $this->assertSame(
            'http://172.22.0.1:9006',
            CkgBridgeUrlNormalizer::normalize('https://172.22.0.1/sikerja/api/bridge/mcu/health')
        );
    }

    public function test_keeps_valid_base_url(): void
    {
        $this->assertSame(
            'http://172.22.0.1:9006',
            CkgBridgeUrlNormalizer::normalize('http://172.22.0.1:9006/')
        );
    }

    public function test_remaps_localhost_to_vm_internal_host(): void
    {
        config([
            'ckg_bridge.internal_host' => '172.22.0.1',
            'ckg_bridge.internal_port' => 9006,
        ]);

        $this->assertSame(
            'http://172.22.0.1:9006',
            CkgBridgeUrlNormalizer::normalize('http://127.0.0.1:9006')
        );
    }

    public function test_supports_ckg_web_service_hostname(): void
    {
        $this->assertSame(
            'http://web',
            CkgBridgeUrlNormalizer::normalize('http://web')
        );
    }

    public function test_preserves_docker_compose_gateway_ip(): void
    {
        $this->assertSame(
            'http://172.22.0.1:9006',
            CkgBridgeUrlNormalizer::normalize('http://172.22.0.1:9006')
        );
    }
}
