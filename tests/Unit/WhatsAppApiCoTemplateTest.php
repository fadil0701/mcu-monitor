<?php

namespace Tests\Unit;

use App\Models\McuResult;
use App\Models\Participant;
use App\Models\Schedule;
use App\Models\Setting;
use App\Services\WhatsAppService;
use App\Support\WhatsAppTemplateDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppApiCoTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function seedApiCoSettings(array $overrides = []): void
    {
        $defaults = [
            'whatsapp_provider' => 'apico',
            'whatsapp_token' => 'test-token',
            'whatsapp_instance_id' => 'cmqj83dp7ebifo8dyegp1b6ki',
            'whatsapp_apico_template_name' => 'undangan_mcu_baru',
            'whatsapp_apico_template_language' => 'id',
            'whatsapp_apico_result_template_name' => 'hasil_mcu_baru',
            'whatsapp_apico_result_template_language' => 'en_US',
            'whatsapp_apico_result_param_keys' => '{{1}},{{2}},{{3}}',
        ];

        foreach (array_merge($defaults, $overrides) as $key => $value) {
            Setting::setValue($key, $value, 'string', 'whatsapp');
        }
    }

    private function fakeApiCoSuccess(): void
    {
        Http::fake([
            '*/api/v1/public/customers' => Http::response(['success' => true], 200),
            '*/api/v1/public/messages/send' => Http::response(['success' => true], 200),
        ]);
    }

    public function test_mcu_result_sends_template_with_result_language_and_resolved_param_keys(): void
    {
        $this->seedApiCoSettings();
        $this->fakeApiCoSuccess();

        $participant = Participant::query()->create([
            'nik_ktp' => '3173012345678901',
            'nrk_pegawai' => 'NRK-901',
            'nama_lengkap' => 'Dwian Andhika',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Test',
            'ukpd' => 'UPT Test',
            'no_telp' => '081234567890',
            'email' => 'dwian@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Sudah MCU',
        ]);

        $result = McuResult::query()->create([
            'participant_id' => $participant->id,
            'tanggal_pemeriksaan' => '2025-07-09',
            'hasil_pemeriksaan' => 'Normal',
            'status_kesehatan' => 'Sehat',
            'uploaded_by' => 'admin',
            'is_published' => true,
        ]);

        $service = new WhatsAppService;
        $this->assertTrue($service->sendMcuResult($result));

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/messages/send')) {
                return false;
            }

            $template = $request['template'] ?? [];

            return ($template['name'] ?? null) === 'hasil_mcu_baru'
                && ($template['language']['code'] ?? null) === 'en_US'
                && ($template['components'][0]['parameters'][0]['text'] ?? null) === 'Dwian Andhika'
                && ($template['components'][0]['parameters'][1]['text'] ?? null) === '09/07/2025';
        });
    }

    public function test_invitation_sends_six_body_params_when_settings_only_have_five_placeholders(): void
    {
        $this->seedApiCoSettings([
            'whatsapp_apico_invitation_param_keys' => '{{1}},{{2}},{{3}},{{4}},{{5}}',
        ]);
        $this->fakeApiCoSuccess();

        $participant = Participant::query()->create([
            'nik_ktp' => '3173012345678902',
            'nrk_pegawai' => 'NRK-902',
            'nama_lengkap' => 'Fadillah Asseggaf',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Test',
            'ukpd' => 'UPT Test',
            'no_telp' => '085659550010',
            'email' => 'fadillah@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
        ]);

        $schedule = Schedule::query()->create([
            'participant_id' => $participant->id,
            'nik_ktp' => $participant->nik_ktp,
            'nrk_pegawai' => $participant->nrk_pegawai,
            'nama_lengkap' => $participant->nama_lengkap,
            'tanggal_lahir' => $participant->tanggal_lahir,
            'jenis_kelamin' => $participant->jenis_kelamin,
            'skpd' => $participant->skpd,
            'ukpd' => $participant->ukpd,
            'no_telp' => $participant->no_telp,
            'email' => $participant->email,
            'tanggal_pemeriksaan' => '2026-06-19',
            'jam_pemeriksaan' => '08:00:00',
            'lokasi_pemeriksaan' => 'PPKP DKI Jakarta',
            'queue_number' => '12',
            'status' => 'Terjadwal',
        ]);

        $service = new WhatsAppService;
        $this->assertTrue($service->sendMcuInvitation($schedule));

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/messages/send')) {
                return false;
            }

            $parameters = $request['template']['components'][0]['parameters'] ?? [];

            return count($parameters) === 6
                && ($parameters[0]['text'] ?? null) === 'Fadillah Asseggaf'
                && ($parameters[2]['text'] ?? null) === 'Jumat'
                && ($parameters[4]['text'] ?? null) === '12'
                && ($parameters[5]['text'] ?? null) === 'PPKP DKI Jakarta';
        });
    }

    public function test_meta_style_param_keys_are_normalized_from_placeholders(): void
    {
        $legend = WhatsAppTemplateDefaults::invitationVariableLegend();
        $service = new WhatsAppService;
        $method = new \ReflectionMethod($service, 'parseApiCoParamKeys');
        $method->setAccessible(true);

        $keys = $method->invoke($service, '{{1}},{{2}},{{3}},{{4}},{{5}}', $legend);

        $this->assertSame(array_values($legend), $keys);
    }

    public function test_result_param_keys_are_normalized_from_three_placeholders(): void
    {
        $legend = WhatsAppTemplateDefaults::resultVariableLegend();
        $service = new WhatsAppService;
        $method = new \ReflectionMethod($service, 'parseApiCoParamKeys');
        $method->setAccessible(true);

        $keys = $method->invoke($service, '{{1}},{{2}},{{3}}', $legend);

        $this->assertSame(['participant_name', 'tanggal_pemeriksaan', 'hasil_url'], $keys);
    }
}
