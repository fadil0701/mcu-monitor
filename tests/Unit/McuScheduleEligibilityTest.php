<?php

namespace Tests\Unit;

use App\Models\Participant;
use App\Models\Schedule;
use App\Models\Setting;
use App\Support\McuDailyQuota;
use App\Support\McuIntervalSettings;
use App\Support\ParticipantMcuScheduleEligibility;
use App\Support\ScheduleExaminationTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McuScheduleEligibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-07-14'));
        Setting::setValue('mcu_interval_years', '1', 'string', 'general');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_interval_setting_is_preferred_over_env_config(): void
    {
        config(['mcu.interval_years' => 3]);
        Setting::setValue('mcu_interval_years', '1', 'string', 'general');

        $this->assertSame(1, McuIntervalSettings::years());
    }

    public function test_participant_without_ckg_can_request_schedule_with_admin_confirmation(): void
    {
        $participant = $this->makeParticipant();

        $eligibility = new ParticipantMcuScheduleEligibility($participant);

        $this->assertTrue($eligibility->canRequest());
        $this->assertNull($eligibility->blockingReason());
        $this->assertTrue($eligibility->requiresAdminConfirmation());
        $this->assertFalse($eligibility->hasCkgScreening());
        $this->assertStringContainsString('belum tersinkron', $eligibility->infoNotes()[1]);
    }

    public function test_participant_with_ckg_and_no_recent_mcu_can_request_schedule(): void
    {
        $participant = $this->makeParticipant([
            'ckg_peserta_id' => 42,
            'ckg_registration_code' => 'CKG-0042',
        ]);

        $eligibility = new ParticipantMcuScheduleEligibility($participant);

        $this->assertTrue($eligibility->canRequest());
        $this->assertNull($eligibility->blockingReason());
        $this->assertNotEmpty($eligibility->infoNotes());
        $this->assertStringContainsString('belum pernah melakukan MCU', $eligibility->infoNotes()[0]);
    }

    public function test_participant_with_mcu_previous_calendar_year_can_request_when_interval_is_one(): void
    {
        $participant = $this->makeParticipant([
            'ckg_peserta_id' => 42,
            'ckg_registration_code' => 'CKG-2026-0042',
            'ckg_synced_at' => '2026-03-01 10:00:00',
            'tanggal_mcu_terakhir' => '2025-12-31',
            'status_mcu' => 'Sudah MCU',
        ]);

        $eligibility = new ParticipantMcuScheduleEligibility($participant);

        $this->assertTrue($eligibility->canRequest());
        $this->assertNull($eligibility->blockingReason());
        $this->assertStringContainsString('tahun berjalan', $eligibility->infoNotes()[0]);
        $this->assertFalse($eligibility->requiresAdminConfirmation());
    }

    public function test_participant_with_mcu_same_calendar_year_cannot_request_when_interval_is_one(): void
    {
        $participant = $this->makeParticipant([
            'ckg_peserta_id' => 42,
            'ckg_registration_code' => 'CKG-2026-0042',
            'ckg_synced_at' => '2026-03-01 10:00:00',
            'tanggal_mcu_terakhir' => '2026-01-05',
            'status_mcu' => 'Sudah MCU',
        ]);

        $eligibility = new ParticipantMcuScheduleEligibility($participant);

        $this->assertFalse($eligibility->canRequest());
        $this->assertStringContainsString('tahun berjalan', (string) $eligibility->blockingReason());
        $this->assertTrue($eligibility->hasMcuWithinInterval());
        $this->assertSame('2027-01-01', $eligibility->mcuEligibleFrom()?->toDateString());
    }

    public function test_participant_respects_multi_year_interval_by_calendar_year(): void
    {
        Setting::setValue('mcu_interval_years', '3', 'string', 'general');

        $blocked = $this->makeParticipant([
            'tanggal_mcu_terakhir' => '2024-06-01',
            'status_mcu' => 'Sudah MCU',
        ]);
        $eligible = $this->makeParticipant([
            'nik_ktp' => '3202180701930006',
            'nrk_pegawai' => '123457',
            'email' => 'peserta2@test.local',
            'tanggal_mcu_terakhir' => '2023-06-01',
            'status_mcu' => 'Sudah MCU',
        ]);

        $this->assertFalse((new ParticipantMcuScheduleEligibility($blocked))->canRequest());
        $this->assertTrue((new ParticipantMcuScheduleEligibility($eligible))->canRequest());
        $this->assertSame('2027-01-01', $blocked->mcuEligibleFrom()?->toDateString());
    }

    public function test_daily_quota_remaining_decreases_when_schedule_exists(): void
    {
        config(['mcu.daily_quota' => 2]);
        $participant = $this->makeParticipant([
            'ckg_peserta_id' => 1,
        ]);

        $this->createSchedule($participant, '2026-06-22');

        $snapshot = McuDailyQuota::snapshot('2026-06-22');

        $this->assertSame(2, $snapshot['limit']);
        $this->assertSame(1, $snapshot['booked']);
        $this->assertSame(1, $snapshot['remaining']);
        $this->assertTrue($snapshot['available']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeParticipant(array $overrides = []): Participant
    {
        return Participant::query()->create(array_merge([
            'nik_ktp' => '3202180701930005',
            'nrk_pegawai' => '123456',
            'nama_lengkap' => 'Peserta MCU',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1993-01-07',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Kesehatan',
            'ukpd' => 'UPT Test',
            'no_telp' => '081234567890',
            'email' => 'peserta.mcu@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
            'tanggal_mcu_terakhir' => null,
        ], $overrides));
    }

    private function createSchedule(Participant $participant, string $date): Schedule
    {
        return Schedule::query()->create([
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
            'tanggal_pemeriksaan' => $date,
            'jam_pemeriksaan' => '08:00:00',
            'lokasi_pemeriksaan' => ScheduleExaminationTime::defaultLocation(),
            'status' => 'Terjadwal',
        ]);
    }
}
