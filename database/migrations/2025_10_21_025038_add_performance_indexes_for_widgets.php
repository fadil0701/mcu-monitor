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
        // Add indexes for better widget performance
        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['tanggal_pemeriksaan', 'status'], 'idx_schedules_date_status');
            $table->index(['status', 'participant_confirmed'], 'idx_schedules_status_confirmed');
            $table->index(['tanggal_pemeriksaan', 'status', 'participant_confirmed'], 'idx_schedules_date_status_confirmed');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->index(['skpd', 'created_at'], 'idx_participants_skpd_created');
        });

        Schema::table('mcu_results', function (Blueprint $table) {
            $table->index(['created_at'], 'idx_mcu_results_created');
            $table->index(['status_kesehatan'], 'idx_mcu_results_health_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_date_status');
            $table->dropIndex('idx_schedules_status_confirmed');
            $table->dropIndex('idx_schedules_date_status_confirmed');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->dropIndex('idx_participants_skpd_created');
        });

        Schema::table('mcu_results', function (Blueprint $table) {
            $table->dropIndex('idx_mcu_results_created');
            $table->dropIndex('idx_mcu_results_health_status');
        });
    }
};
