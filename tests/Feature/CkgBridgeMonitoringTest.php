<?php

namespace Tests\Feature;

use App\Models\CkgBridgeSyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CkgBridgeMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_ckg_bridge_config(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.ckg-bridge.config.update'), [
                'base_url' => 'http://ckg.test',
                'api_key' => 'secret-from-ckg',
                'api_key_header' => 'X-Mcu-Api-Key',
                'per_page' => 50,
                'timeout_seconds' => 30,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.ckg-bridge.index'));

        $this->assertDatabaseHas('ckg_bridge_configs', [
            'name' => 'CKG Bridge',
            'base_url' => 'http://ckg.test',
            'is_active' => 1,
        ]);
    }

    public function test_manual_sync_creates_success_log(): void
    {
        Http::fake([
            'http://ckg.test/api/bridge/mcu/*' => Http::sequence()
                ->push(['status' => 'ok', 'service' => 'ckg-mcu-bridge'])
                ->push([
                    'meta' => ['page' => 1, 'per_page' => 100, 'total' => 0, 'last_page' => 1],
                    'data' => [],
                ]),
        ]);

        config(['ckg_bridge.base_url' => 'http://ckg.test', 'ckg_bridge.api_key' => 'test-mcu-api-key']);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.ckg-bridge.sync'))
            ->assertRedirect(route('admin.ckg-bridge.index'));

        $this->assertDatabaseHas('ckg_bridge_sync_logs', [
            'status' => 'success',
            'trigger' => 'manual',
        ]);
        $this->assertSame(1, CkgBridgeSyncLog::query()->count());
    }

    public function test_sync_logs_are_paginated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 25; $i++) {
            CkgBridgeSyncLog::query()->create([
                'status' => 'success',
                'trigger' => 'schedule',
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0,
                'started_at' => now(),
                'finished_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.ckg-bridge.index', ['per_page' => 20]))
            ->assertOk()
            ->assertSee('Menampilkan 1–20 dari 25 log', false)
            ->assertSee('page=2', false);
    }
}
