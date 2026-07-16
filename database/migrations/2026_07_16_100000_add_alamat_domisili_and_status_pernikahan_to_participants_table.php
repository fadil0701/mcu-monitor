<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->text('alamat_domisili')->nullable()->after('no_telp');
            $table->string('status_pernikahan')->nullable()->after('alamat_domisili');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['alamat_domisili', 'status_pernikahan']);
        });
    }
};
