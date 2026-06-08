<?php

namespace Tests\Unit;

use App\Support\CkgBridge\CkgBridgeUrlNormalizer;
use PHPUnit\Framework\TestCase;

class CkgBridgeUrlNormalizerTest extends TestCase
{
    public function test_strips_api_path_and_subpath(): void
    {
        $this->assertSame(
            'http://10.15.101.117:9006',
            CkgBridgeUrlNormalizer::normalize('https://10.15.101.117/sikerja/api/bridge/mcu/health')
        );
    }

    public function test_keeps_valid_base_url(): void
    {
        $this->assertSame(
            'http://10.15.101.117:9006',
            CkgBridgeUrlNormalizer::normalize('http://10.15.101.117:9006/')
        );
    }
}
