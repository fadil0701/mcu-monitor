<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('schedules', function (Blueprint $table) {
			$table->boolean('participant_confirmed')->nullable()->after('queue_number');
			$table->timestamp('participant_confirmed_at')->nullable()->after('participant_confirmed');
			$table->boolean('reschedule_requested')->default(false)->after('participant_confirmed_at');
			$table->date('reschedule_new_date')->nullable()->after('reschedule_requested');
			$table->time('reschedule_new_time')->nullable()->after('reschedule_new_date');
			$table->text('reschedule_reason')->nullable()->after('reschedule_new_time');
			$table->timestamp('reschedule_requested_at')->nullable()->after('reschedule_reason');
		});
	}

	public function down(): void
	{
		Schema::table('schedules', function (Blueprint $table) {
			$table->dropColumn([
				'participant_confirmed',
				'participant_confirmed_at',
				'reschedule_requested',
				'reschedule_new_date',
				'reschedule_new_time',
				'reschedule_reason',
				'reschedule_requested_at',
			]);
		});
	}
};
