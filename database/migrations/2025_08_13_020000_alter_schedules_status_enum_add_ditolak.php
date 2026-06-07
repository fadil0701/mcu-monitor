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

		DB::statement("ALTER TABLE `schedules` MODIFY `status` ENUM('Terjadwal','Selesai','Batal','Ditolak') NOT NULL DEFAULT 'Terjadwal'");
	}

	public function down(): void
	{
		if (Schema::getConnection()->getDriverName() !== 'mysql') {
			return;
		}

		DB::statement("ALTER TABLE `schedules` MODIFY `status` ENUM('Terjadwal','Selesai','Batal') NOT NULL DEFAULT 'Terjadwal'");
	}
};

