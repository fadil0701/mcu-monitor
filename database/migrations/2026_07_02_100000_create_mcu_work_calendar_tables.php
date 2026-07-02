<?php

use App\Models\Setting;
use App\Support\McuScheduleDateList;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcu_work_calendar_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('block_weekends')->default(true);
            $table->timestamps();
        });

        DB::table('mcu_work_calendar_settings')->insert([
            'block_weekends' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('mcu_work_calendar_closures', function (Blueprint $table) {
            $table->id();
            $table->date('closure_date')->unique();
            $table->string('type', 32);
            $table->string('label');
            $table->timestamps();
        });

        $this->importLegacySettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('mcu_work_calendar_closures');
        Schema::dropIfExists('mcu_work_calendar_settings');
    }

    private function importLegacySettings(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();

        foreach (McuScheduleDateList::parse((string) Setting::getValue('mcu_hari_libur', '')) as $date) {
            DB::table('mcu_work_calendar_closures')->insertOrIgnore([
                'closure_date' => $date,
                'type' => 'libur_nasional',
                'label' => 'Libur nasional',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (McuScheduleDateList::parse((string) Setting::getValue('mcu_cuti_bersama', '')) as $date) {
            DB::table('mcu_work_calendar_closures')->updateOrInsert(
                ['closure_date' => $date],
                [
                    'type' => 'cuti_bersama',
                    'label' => 'Cuti bersama',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
};
