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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('nik_ktp', 16)->unique();
            $table->string('nrk_pegawai')->unique();
            $table->string('nama_lengkap');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('skpd');
            $table->string('ukpd');
            $table->string('no_telp');
            $table->string('email');
            $table->enum('status_pegawai', ['CPNS', 'PNS', 'PPPK']);
            $table->enum('status_mcu', ['Belum MCU', 'Sudah MCU', 'Ditolak'])->default('Belum MCU');
            $table->date('tanggal_mcu_terakhir')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
