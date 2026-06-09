<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Schedule;
use App\Models\User;
use App\Support\ScheduleExaminationTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientScheduleRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_submit_schedule_within_allowed_hours(): void
    {
        [$user, $participant] = $this->makeEligibleParticipant();

        $this->actingAs($user)
            ->post(route('client.schedule.request.store'), [
                'tanggal_pemeriksaan' => now()->addWeek()->toDateString(),
                'jam_pemeriksaan' => '08:30',
                'catatan' => 'Siap hadir',
            ])
            ->assertRedirect(route('client.schedules'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('schedules', [
            'participant_id' => $participant->id,
            'jam_pemeriksaan' => '08:30',
            'lokasi_pemeriksaan' => ScheduleExaminationTime::defaultLocation(),
            'status' => 'Terjadwal',
        ]);
    }

    public function test_participant_cannot_submit_schedule_outside_allowed_hours(): void
    {
        [$user] = $this->makeEligibleParticipant();

        $this->actingAs($user)
            ->post(route('client.schedule.request.store'), [
                'tanggal_pemeriksaan' => now()->addWeek()->toDateString(),
                'jam_pemeriksaan' => '11:00',
            ])
            ->assertSessionHasErrors('jam_pemeriksaan');

        $this->assertSame(0, Schedule::query()->count());
    }

    public function test_location_is_forced_from_system_default(): void
    {
        [$user, $participant] = $this->makeEligibleParticipant();

        $this->actingAs($user)
            ->post(route('client.schedule.request.store'), [
                'tanggal_pemeriksaan' => now()->addWeek()->toDateString(),
                'jam_pemeriksaan' => '07:30',
                'lokasi_pemeriksaan' => 'Lokasi Palsu Peserta',
            ])
            ->assertRedirect(route('client.schedules'));

        $schedule = Schedule::query()->where('participant_id', $participant->id)->first();
        $this->assertNotNull($schedule);
        $this->assertSame(ScheduleExaminationTime::defaultLocation(), $schedule->lokasi_pemeriksaan);
    }

    /**
     * @return array{0: User, 1: Participant}
     */
    private function makeEligibleParticipant(): array
    {
        $participant = Participant::query()->create([
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
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'nik_ktp' => $participant->nik_ktp,
            'email' => $participant->email,
        ]);

        return [$user, $participant];
    }
}
