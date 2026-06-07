<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::table('mcu_results', function (Blueprint $table) {
			$table->json('file_hasil_files')->nullable()->after('file_hasil');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::table('mcu_results', function (Blueprint $table) {
			$table->dropColumn('file_hasil_files');
		});
	}
};

