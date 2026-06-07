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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Template name');
            $table->string('type')->default('mcu_invitation')->comment('Template type (mcu_invitation, reminder, etc)');
            $table->string('subject')->comment('Email subject');
            $table->text('body_html')->nullable()->comment('HTML template body');
            $table->text('body_text')->nullable()->comment('Plain text template body');
            $table->json('variables')->nullable()->comment('Available template variables');
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
        Schema::dropIfExists('email_templates');
    }
};
