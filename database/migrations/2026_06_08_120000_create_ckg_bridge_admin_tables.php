<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ckg_bridge_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('CKG Bridge');
            $table->string('base_url', 500)->default('http://127.0.0.1:9006');
            $table->text('api_key')->nullable();
            $table->string('api_key_header', 64)->default('X-Mcu-Api-Key');
            $table->unsignedSmallInteger('per_page')->default(100);
            $table->unsignedSmallInteger('timeout_seconds')->default(60);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('ckg_bridge_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['running', 'success', 'failed'])->default('running');
            $table->enum('trigger', ['manual', 'schedule', 'command'])->default('command');
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('inserted')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('pages')->default(0);
            $table->timestamp('since')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ckg_bridge_sync_logs');
        Schema::dropIfExists('ckg_bridge_configs');
    }
};
