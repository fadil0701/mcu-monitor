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
        Schema::create('mcu_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->onDelete('cascade');
            $table->foreignId('schedule_id')->constrained()->onDelete('cascade');
            $table->date('tanggal_pemeriksaan');
            $table->string('diagnosis')->nullable();
            $table->text('hasil_pemeriksaan');
            $table->enum('status_kesehatan', ['Sehat', 'Kurang Sehat', 'Tidak Sehat']);
            $table->text('rekomendasi')->nullable();
            $table->string('file_hasil')->nullable();
            $table->boolean('is_downloaded')->default(false);
            $table->timestamp('downloaded_at')->nullable();
            $table->string('uploaded_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcu_results');
    }
};
