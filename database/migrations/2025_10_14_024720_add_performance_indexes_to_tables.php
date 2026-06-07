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
        // Add indexes to participants table
        Schema::table('participants', function (Blueprint $table) {
            $table->index('skpd', 'idx_participants_skpd');
            $table->index('created_at', 'idx_participants_created_at');
            $table->index(['skpd', 'created_at'], 'idx_participants_skpd_created_at');
        });

        // Add indexes to schedules table
        Schema::table('schedules', function (Blueprint $table) {
            $table->index('status', 'idx_schedules_status');
            $table->index('tanggal_pemeriksaan', 'idx_schedules_tanggal');
            $table->index(['tanggal_pemeriksaan', 'status'], 'idx_schedules_tanggal_status');
            $table->index(['participant_confirmed', 'status'], 'idx_schedules_confirmed_status');
        });

        // Add indexes to mcu_results table
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->index('status_kesehatan', 'idx_mcu_status_kesehatan');
            $table->index('created_at', 'idx_mcu_created_at');
            $table->index('participant_id', 'idx_mcu_participant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from participants table
        Schema::table('participants', function (Blueprint $table) {
            $table->dropIndex('idx_participants_skpd');
            $table->dropIndex('idx_participants_created_at');
            $table->dropIndex('idx_participants_skpd_created_at');
        });

        // Drop indexes from schedules table
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_status');
            $table->dropIndex('idx_schedules_tanggal');
            $table->dropIndex('idx_schedules_tanggal_status');
            $table->dropIndex('idx_schedules_confirmed_status');
        });

        // Drop indexes from mcu_results table
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->dropIndex('idx_mcu_status_kesehatan');
            $table->dropIndex('idx_mcu_created_at');
            $table->dropIndex('idx_mcu_participant_id');
        });
    }
};
