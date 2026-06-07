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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->onDelete('cascade');
            $table->string('nik_ktp', 16);
            $table->string('nrk_pegawai');
            $table->string('nama_lengkap');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('skpd');
            $table->string('ukpd');
            $table->string('no_telp');
            $table->string('email');
            $table->date('tanggal_pemeriksaan');
            $table->time('jam_pemeriksaan');
            $table->string('lokasi_pemeriksaan');
            $table->enum('status', ['Terjadwal', 'Selesai', 'Batal', 'Ditolak'])->default('Terjadwal');
            $table->boolean('email_sent')->default(false);
            $table->boolean('whatsapp_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
