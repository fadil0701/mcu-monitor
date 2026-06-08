<?php

namespace Tests\Unit;

use App\Models\CkgBridgeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CkgBridgeConfigModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_url_is_normalized_on_save_and_read(): void
    {
        $config = CkgBridgeConfig::query()->create([
            'name' => 'CKG Bridge',
            'base_url' => 'https://172.22.0.1:9006',
            'api_key_header' => 'X-Mcu-Api-Key',
            'is_active' => true,
        ]);

        $this->assertSame('http://172.22.0.1:9006', $config->fresh()->base_url);
    }
}
