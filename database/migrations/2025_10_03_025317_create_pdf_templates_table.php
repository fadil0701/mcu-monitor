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
        Schema::create('pdf_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Template name');
            $table->string('type')->default('mcu_letter')->comment('Template type (mcu_letter, reminder_letter, etc)');
            $table->string('title')->comment('Document title');
            $table->text('header_html')->nullable()->comment('Header HTML content');
            $table->text('body_html')->nullable()->comment('Body HTML content');
            $table->text('footer_html')->nullable()->comment('Footer HTML content');
            $table->json('variables')->nullable()->comment('Available template variables');
            $table->json('settings')->nullable()->comment('PDF settings (margins, page size, etc)');
            $table->boolean('is_active')->default(true)->comment('Template active status');
            $table->boolean('is_default')->default(false)->comment('Default template for this type');
            $table->text('description')->nullable()->comment('Template description');
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->unique(['type', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_templates');
    }
};
