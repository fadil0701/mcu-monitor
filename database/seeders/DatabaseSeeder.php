<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SettingSeeder::class,
            InstansiPemprovDkiSeeder::class,
            // ParticipantSeeder::class,
            ScheduleSeeder::class,
        ]);
    }
}
