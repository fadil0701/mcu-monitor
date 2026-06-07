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
        Schema::table('schedules', function (Blueprint $table) {
            $table->index('participant_id', 'schedules_participant_id_index');
            $table->index('tanggal_pemeriksaan', 'schedules_tanggal_index');
            $table->index('status', 'schedules_status_index');
            $table->index(['tanggal_pemeriksaan', 'status'], 'schedules_tanggal_status_index');
            $table->index('jam_pemeriksaan', 'schedules_jam_index');
            $table->index('created_at', 'schedules_created_at_index');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->index('nama_lengkap', 'participants_nama_index');
            $table->index('skpd', 'participants_skpd_index');
            $table->index('status_pegawai', 'participants_status_pegawai_index');
            $table->index('status_mcu', 'participants_status_mcu_index');
            $table->index('created_at', 'participants_created_at_index');
        });

        Schema::table('mcu_results', function (Blueprint $table) {
            $table->index('participant_id', 'mcu_results_participant_id_index');
            $table->index('schedule_id', 'mcu_results_schedule_id_index');
            $table->index('tanggal_pemeriksaan', 'mcu_results_tanggal_index');
            $table->index('status_kesehatan', 'mcu_results_status_kesehatan_index');
            $table->index('created_at', 'mcu_results_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_participant_id_index');
            $table->dropIndex('schedules_tanggal_index');
            $table->dropIndex('schedules_status_index');
            $table->dropIndex('schedules_tanggal_status_index');
            $table->dropIndex('schedules_jam_index');
            $table->dropIndex('schedules_created_at_index');
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->dropIndex('participants_nama_index');
            $table->dropIndex('participants_skpd_index');
            $table->dropIndex('participants_status_pegawai_index');
            $table->dropIndex('participants_status_mcu_index');
            $table->dropIndex('participants_created_at_index');
        });

        Schema::table('mcu_results', function (Blueprint $table) {
            $table->dropIndex('mcu_results_participant_id_index');
            $table->dropIndex('mcu_results_schedule_id_index');
            $table->dropIndex('mcu_results_tanggal_index');
            $table->dropIndex('mcu_results_status_kesehatan_index');
            $table->dropIndex('mcu_results_created_at_index');
        });
    }
};


