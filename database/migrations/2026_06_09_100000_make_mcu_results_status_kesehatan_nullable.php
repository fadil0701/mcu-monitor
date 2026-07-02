<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE mcu_results MODIFY status_kesehatan ENUM('Sehat', 'Kurang Sehat', 'Tidak Sehat') NULL DEFAULT NULL");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE mcu_results ALTER COLUMN status_kesehatan DROP NOT NULL');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE mcu_results SET status_kesehatan = 'Sehat' WHERE status_kesehatan IS NULL");
            DB::statement("ALTER TABLE mcu_results MODIFY status_kesehatan ENUM('Sehat', 'Kurang Sehat', 'Tidak Sehat') NOT NULL");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("UPDATE mcu_results SET status_kesehatan = 'Sehat' WHERE status_kesehatan IS NULL");
            DB::statement('ALTER TABLE mcu_results ALTER COLUMN status_kesehatan SET NOT NULL');
        }
    }
};
