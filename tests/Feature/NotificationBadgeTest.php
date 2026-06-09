<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\NewRegistrationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_menu_shows_unread_notification_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->notify(new NewRegistrationNotification('ulang', [
            'participant_name' => 'Peserta Baru',
            'nik_ktp' => '3202180701930005',
            'tanggal_pemeriksaan' => '2026-06-16',
            'jam_pemeriksaan' => '08:00',
        ]));

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('badge-notifications', false)
            ->assertSee('Pendaftaran Ulang Peserta');
    }

    public function test_participant_receives_notification_when_reschedule_approved(): void
    {
        $participant = $this->makeParticipant();
        $user = User::factory()->create([
            'role' => 'user',
            'nik_ktp' => $participant->nik_ktp,
            'email' => $participant->email,
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
            'tanggal_pemeriksaan' => now()->addWeek()->toDateString(),
            'jam_pemeriksaan' => '08:00:00',
            'lokasi_pemeriksaan' => 'Klinik Utama Balaikota',
            'status' => 'Terjadwal',
            'reschedule_requested' => true,
            'reschedule_new_date' => now()->addWeeks(2)->toDateString(),
            'reschedule_new_time' => '09:00',
            'reschedule_reason' => 'Ada keperluan',
            'reschedule_requested_at' => now(),
        ]);

        \App\Support\ScheduleParticipantNotifier::notify($schedule->fresh(), 'reschedule_approved');

        $this->assertSame(1, $user->fresh()->unreadNotifications()->count());

        $this->actingAs($user)
            ->get(route('client.notifications.index'))
            ->assertOk()
            ->assertSee('Reschedule Disetujui')
            ->assertSee('badge-notifications', false);
    }

    public function test_new_registration_notification_has_readable_message(): void
    {
        $notification = new NewRegistrationNotification('ulang', [
            'participant_name' => 'Fadillah Asseggaf',
            'nik_ktp' => '3202180701930005',
            'tanggal_pemeriksaan' => '2026-06-16',
            'jam_pemeriksaan' => '07:30',
        ]);

        $data = $notification->toArray(new User);

        $this->assertStringContainsString('Fadillah Asseggaf', $data['message']);
        $this->assertStringContainsString('2026-06-16', $data['message']);
    }

    private function makeParticipant(): Participant
    {
        return Participant::query()->create([
            'nik_ktp' => '3202180701930005',
            'nrk_pegawai' => '123456',
            'nama_lengkap' => 'Peserta MCU',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1993-01-07',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Kesehatan',
            'ukpd' => 'UPT Test',
            'no_telp' => '081234567890',
            'email' => 'peserta.notify@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
        ]);
    }
}
