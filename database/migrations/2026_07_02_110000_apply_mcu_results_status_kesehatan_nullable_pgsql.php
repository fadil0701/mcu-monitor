<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PG schema pada VM yang sudah migrate sebelum 2026_06_09 mendukung pgsql —
 * kolom status_kesehatan masih NOT NULL tanpa migrasi ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (! Schema::hasTable('mcu_results') || ! Schema::hasColumn('mcu_results', 'status_kesehatan')) {
            return;
        }

        DB::statement('ALTER TABLE mcu_results ALTER COLUMN status_kesehatan DROP NOT NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("UPDATE mcu_results SET status_kesehatan = 'Sehat' WHERE status_kesehatan IS NULL");
        DB::statement('ALTER TABLE mcu_results ALTER COLUMN status_kesehatan SET NOT NULL');
    }
};
