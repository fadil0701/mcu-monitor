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
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->unsignedBigInteger('schedule_id')->nullable()->change();
        });
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->unsignedBigInteger('schedule_id')->nullable(false)->change();
        });
        Schema::table('mcu_results', function (Blueprint $table) {
            $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('cascade');
        });
    }
};
