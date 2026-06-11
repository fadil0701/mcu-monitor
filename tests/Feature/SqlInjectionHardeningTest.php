<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SqlInjectionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_search_with_sql_payload_does_not_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.participants.index', [
                'search' => "%' OR 1=1 UNION SELECT * FROM users --",
                'status_mcu' => "'; DROP TABLE participants; --",
            ]))
            ->assertOk();
    }

    public function test_schedule_search_rejects_invalid_status_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('admin.schedules.index', [
                'status' => "Terjadwal' OR '1'='1",
            ]));

        $response->assertOk();
        $this->assertStringNotContainsString('SQLSTATE', (string) $response->getContent());
    }
}
