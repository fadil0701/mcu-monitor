<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->unsignedBigInteger('ckg_peserta_id')->nullable()->unique()->after('id');
            $table->string('ckg_registration_code')->nullable()->after('ckg_peserta_id');
            $table->timestamp('ckg_synced_at')->nullable()->after('ckg_registration_code');
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn(['ckg_peserta_id', 'ckg_registration_code', 'ckg_synced_at']);
        });
    }
};
