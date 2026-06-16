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

    public function test_participant_cannot_submit_without_ckg_screening(): void
    {
        [$user] = $this->makeEligibleParticipant(withCkg: false);

        $this->actingAs($user)
            ->post(route('client.schedule.request.store'), [
                'tanggal_pemeriksaan' => now()->addWeek()->toDateString(),
                'jam_pemeriksaan' => '08:30',
            ])
            ->assertSessionHasErrors('request');

        $this->assertSame(0, Schedule::query()->count());
    }

    public function test_participant_cannot_submit_when_daily_quota_full(): void
    {
        config(['mcu.daily_quota' => 1]);
        [$user, $participant] = $this->makeEligibleParticipant();
        $date = now()->addWeek()->toDateString();

        $other = Participant::query()->create([
            'nik_ktp' => '9999999999999999',
            'nrk_pegawai' => 'NRK-FULL',
            'nama_lengkap' => 'Peserta Lain',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'SKPD',
            'ukpd' => 'UKPD',
            'no_telp' => '081111111111',
            'email' => 'lain@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
            'ckg_peserta_id' => 1,
        ]);

        Schedule::query()->create([
            'participant_id' => $other->id,
            'nik_ktp' => $other->nik_ktp,
            'nrk_pegawai' => $other->nrk_pegawai,
            'nama_lengkap' => $other->nama_lengkap,
            'tanggal_lahir' => $other->tanggal_lahir,
            'jenis_kelamin' => $other->jenis_kelamin,
            'skpd' => $other->skpd,
            'ukpd' => $other->ukpd,
            'no_telp' => $other->no_telp,
            'email' => $other->email,
            'tanggal_pemeriksaan' => $date,
            'jam_pemeriksaan' => '08:00:00',
            'lokasi_pemeriksaan' => ScheduleExaminationTime::defaultLocation(),
            'status' => 'Terjadwal',
        ]);

        $this->actingAs($user)
            ->post(route('client.schedule.request.store'), [
                'tanggal_pemeriksaan' => $date,
                'jam_pemeriksaan' => '08:30',
            ])
            ->assertSessionHasErrors('tanggal_pemeriksaan');

        $this->assertSame(1, Schedule::query()->count());
        $this->assertDatabaseMissing('schedules', [
            'participant_id' => $participant->id,
        ]);
    }

    public function test_schedule_quota_endpoint_returns_remaining_slots(): void
    {
        [$user, $participant] = $this->makeEligibleParticipant();
        config(['mcu.daily_quota' => 5]);
        $date = now()->addWeek()->toDateString();

        Schedule::query()->create([
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

        $this->actingAs($user)
            ->getJson(route('client.schedule.quota', ['date' => $date]))
            ->assertOk()
            ->assertJson([
                'limit' => 5,
                'booked' => 1,
                'remaining' => 4,
                'available' => true,
            ]);
    }

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
    private function makeEligibleParticipant(bool $withCkg = true): array
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
            'ckg_peserta_id' => $withCkg ? 99 : null,
            'ckg_registration_code' => $withCkg ? 'CKG-0099' : null,
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'nik_ktp' => $participant->nik_ktp,
            'email' => $participant->email,
        ]);

        return [$user, $participant];
    }
}
