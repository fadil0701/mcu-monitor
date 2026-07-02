<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = "'Menunggu Konfirmasi','Terjadwal','Selesai','Batal','Ditolak'";

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `schedules` MODIFY `status` ENUM('.self::STATUSES.") NOT NULL DEFAULT 'Terjadwal'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE schedules DROP CONSTRAINT IF EXISTS schedules_status_check');
            DB::statement('ALTER TABLE schedules ADD CONSTRAINT schedules_status_check CHECK (status IN ('.self::STATUSES.'))');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE `schedules` SET `status` = 'Terjadwal' WHERE `status` = 'Menunggu Konfirmasi'");
            DB::statement("ALTER TABLE `schedules` MODIFY `status` ENUM('Terjadwal','Selesai','Batal','Ditolak') NOT NULL DEFAULT 'Terjadwal'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("UPDATE schedules SET status = 'Terjadwal' WHERE status = 'Menunggu Konfirmasi'");
            DB::statement('ALTER TABLE schedules DROP CONSTRAINT IF EXISTS schedules_status_check');
            DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_status_check CHECK (status IN ('Terjadwal', 'Selesai', 'Batal', 'Ditolak'))");
        }
    }
};
