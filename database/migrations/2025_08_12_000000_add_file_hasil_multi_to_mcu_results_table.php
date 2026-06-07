<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->json('file_hasil_multi')->nullable()->after('file_hasil');
        });
    }

    public function down(): void
    {
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->dropColumn('file_hasil_multi');
        });
    }
};



