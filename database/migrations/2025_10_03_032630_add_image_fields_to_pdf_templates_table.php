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
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->comment('Path to organization logo image');
            $table->string('signature_image_path')->nullable()->comment('Path to signature image');
            $table->string('stamp_image_path')->nullable()->comment('Path to official stamp image');
            $table->json('image_settings')->nullable()->comment('Image display settings (width, height, position)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pdf_templates', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'signature_image_path', 'stamp_image_path', 'image_settings']);
        });
    }
};
