<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->json('specialist_doctor_ids')->nullable()->after('rekomendasi');
        });
    }

    public function down(): void
    {
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->dropColumn('specialist_doctor_ids');
        });
    }
};
