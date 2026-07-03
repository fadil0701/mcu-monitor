<?php

namespace Tests\Unit;

use App\Models\Participant;
use App\Models\Schedule;
use App\Support\McuDailyQuota;
use App\Support\ParticipantMcuScheduleEligibility;
use App\Support\ScheduleExaminationTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McuScheduleEligibilityTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_participant_with_ckg_and_old_mcu_can_request_schedule_with_info_note(): void
    {
        $participant = $this->makeParticipant([
            'ckg_peserta_id' => 42,
            'ckg_registration_code' => 'CKG-0042',
            'tanggal_mcu_terakhir' => Carbon::now()->subYears(4)->toDateString(),
            'status_mcu' => 'Sudah MCU',
        ]);

        $eligibility = new ParticipantMcuScheduleEligibility($participant);

        $this->assertTrue($eligibility->canRequest());
        $this->assertNull($eligibility->blockingReason());
        $this->assertStringContainsString('belum melakukan MCU dalam 3 tahun terakhir', $eligibility->infoNotes()[0]);
    }

    public function test_participant_with_recent_mcu_cannot_request_schedule(): void
    {
        $participant = $this->makeParticipant([
            'ckg_peserta_id' => 42,
            'ckg_registration_code' => 'CKG-0042',
            'tanggal_mcu_terakhir' => Carbon::now()->subYear()->toDateString(),
            'status_mcu' => 'Sudah MCU',
        ]);

        $eligibility = new ParticipantMcuScheduleEligibility($participant);

        $this->assertFalse($eligibility->canRequest());
        $this->assertStringContainsString('belum 3 tahun', (string) $eligibility->blockingReason());
        $this->assertTrue($eligibility->hasMcuWithinInterval());
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
