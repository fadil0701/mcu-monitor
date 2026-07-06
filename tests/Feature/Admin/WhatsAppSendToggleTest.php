<?php

namespace Tests\Feature\Admin;

use App\Models\Participant;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppSendToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedules_page_shows_whatsapp_button_when_enabled_and_schedule_exists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Setting::setValue('whatsapp_send_enabled', '1', 'boolean', 'whatsapp', 'WA send toggle');

        $participant = Participant::query()->create([
            'nik_ktp' => '3173012345678901',
            'nrk_pegawai' => 'NRK-001',
            'nama_lengkap' => 'Peserta WA Toggle',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Test',
            'ukpd' => 'UPT Test',
            'no_telp' => '081234567890',
            'email' => 'wa@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
        ]);

        Schedule::query()->create([
            'participant_id' => $participant->id,
            'nik_ktp' => '3173012345678901',
            'nrk_pegawai' => 'NRK-001',
            'nama_lengkap' => 'Peserta WA Toggle',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Test',
            'ukpd' => 'UPT Test',
            'no_telp' => '081234567890',
            'email' => 'wa@test.local',
            'tanggal_pemeriksaan' => now()->toDateString(),
            'jam_pemeriksaan' => '08:00:00',
            'lokasi_pemeriksaan' => 'PPKP',
            'status' => 'Terjadwal',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertSee('bx bxl-whatsapp', false);
    }

    public function test_super_admin_can_enable_whatsapp_send_toggle_in_settings(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->put(route('admin.settings.update-section', 'whatsapp'), [
                'whatsapp_send_enabled' => '1',
                'whatsapp_provider' => 'apico',
                'whatsapp_instance_id' => '',
                'whatsapp_phone_number' => '',
                'whatsapp_apico_template_name' => '',
                'whatsapp_apico_template_language' => 'id',
                'whatsapp_apico_invitation_param_keys' => '',
                'whatsapp_apico_result_template_name' => '',
                'whatsapp_apico_result_template_language' => 'en_US',
                'whatsapp_apico_result_param_keys' => '',
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'whatsapp']));

        $this->assertTrue(Setting::getValue('whatsapp_send_enabled'));
    }
}
