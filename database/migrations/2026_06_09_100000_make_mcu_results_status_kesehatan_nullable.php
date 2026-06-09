<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE mcu_results MODIFY status_kesehatan ENUM('Sehat', 'Kurang Sehat', 'Tidak Sehat') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE mcu_results SET status_kesehatan = 'Sehat' WHERE status_kesehatan IS NULL");
        DB::statement("ALTER TABLE mcu_results MODIFY status_kesehatan ENUM('Sehat', 'Kurang Sehat', 'Tidak Sehat') NOT NULL");
    }
};
