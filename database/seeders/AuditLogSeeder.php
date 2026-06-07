<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuditLog;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample audit logs for demonstration
        $sampleLogs = [
            [
                'user_id' => 1,
                'user_name' => 'Admin',
                'action' => 'created',
                'model_type' => 'App\Models\Participant',
                'model_id' => 1,
                'description' => 'Created new participant',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            [
                'user_id' => 1,
                'user_name' => 'Admin',
                'action' => 'updated',
                'model_type' => 'App\Models\Participant',
                'model_id' => 1,
                'old_values' => ['status_mcu' => 'Belum MCU'],
                'new_values' => ['status_mcu' => 'Sudah MCU'],
                'description' => 'Updated participant MCU status',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            [
                'user_id' => 1,
                'user_name' => 'Admin',
                'action' => 'sent_email',
                'model_type' => 'App\Models\Schedule',
                'model_id' => 1,
                'description' => 'Email invitation sent to participant',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ];

        foreach ($sampleLogs as $log) {
            AuditLog::create($log);
        }
    }
}

