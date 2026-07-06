<?php

namespace Tests\Feature;

use App\Models\McuResult;
use App\Models\Participant;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class McuResultAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-16');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_index_defaults_to_current_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeParticipant([
            'nama_lengkap' => 'MCU Bulan Ini',
            'nik_ktp' => '3173012345678921',
            'nrk_pegawai' => 'NRK-021',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-06-12',
        ]);
        $this->makeParticipant([
            'nama_lengkap' => 'MCU Bulan Lalu',
            'nik_ktp' => '3173012345678922',
            'nrk_pegawai' => 'NRK-022',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-05-20',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.mcu-results.index'))
            ->assertOk()
            ->assertSee('MCU Bulan Ini')
            ->assertDontSee('MCU Bulan Lalu');
    }

    public function test_index_can_show_previous_month_via_period_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeParticipant([
            'nama_lengkap' => 'MCU Bulan Lalu',
            'nik_ktp' => '3173012345678923',
            'nrk_pegawai' => 'NRK-023',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-05-20',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.mcu-results.index', [
                'period' => 'bulan',
                'period_value' => '2026-05',
            ]))
            ->assertOk()
            ->assertSee('MCU Bulan Lalu');
    }

    public function test_index_can_show_all_periods_when_period_is_empty(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeParticipant([
            'nama_lengkap' => 'MCU Bulan Ini',
            'nik_ktp' => '3173012345678924',
            'nrk_pegawai' => 'NRK-024',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-06-12',
        ]);
        $this->makeParticipant([
            'nama_lengkap' => 'MCU Bulan Lalu',
            'nik_ktp' => '3173012345678925',
            'nrk_pegawai' => 'NRK-025',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-05-20',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.mcu-results.index', ['period' => '']))
            ->assertOk()
            ->assertSee('MCU Bulan Ini')
            ->assertSee('MCU Bulan Lalu');
    }

    public function test_index_lists_completed_schedules_for_sudah_mcu_participants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = $this->makeParticipant([
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-06-15',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.mcu-results.index'))
            ->assertOk()
            ->assertSee('Peserta MCU Selesai')
            ->assertSee('Belum di upload');
    }

    public function test_index_lists_sudah_mcu_participant_without_completed_schedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = $this->makeParticipant([
            'nama_lengkap' => 'MCU Via Import',
            'nik_ktp' => '3173012345678903',
            'nrk_pegawai' => 'NRK-003',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-06-10',
        ]);

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
            'tanggal_pemeriksaan' => '2026-06-20',
            'jam_pemeriksaan' => '08:00:00',
            'lokasi_pemeriksaan' => 'PPKP',
            'status' => 'Terjadwal',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.mcu-results.index'))
            ->assertOk()
            ->assertSee('MCU Via Import')
            ->assertSee('10/06/2026');
    }

    public function test_store_syncs_participant_and_shows_on_index(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $participant = $this->makeParticipant([
            'nama_lengkap' => 'Peserta Baru Upload',
            'nik_ktp' => '3173012345678930',
            'nrk_pegawai' => 'NRK-030',
            'status_mcu' => 'Belum MCU',
            'tanggal_mcu_terakhir' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.mcu-results.store'), [
                'participant_id' => $participant->id,
                'tanggal_pemeriksaan' => '2026-06-15',
                'file_hasil' => [
                    UploadedFile::fake()->create('hasil-mcu.pdf', 100, 'application/pdf'),
                ],
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.mcu-results.index', [
                'period' => 'bulan',
                'period_value' => '2026-06',
            ]));

        $participant->refresh();
        $this->assertSame('Sudah MCU', $participant->status_mcu);
        $this->assertSame('2026-06-15', $participant->tanggal_mcu_terakhir?->format('Y-m-d'));

        $this->actingAs($admin)
            ->get(route('admin.mcu-results.index', [
                'period' => 'bulan',
                'period_value' => '2026-06',
            ]))
            ->assertOk()
            ->assertSee('Peserta Baru Upload')
            ->assertSee('Sudah di Upload');
    }

    public function test_index_filters_by_status_hasil_uploaded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $uploaded = $this->makeParticipant([
            'nama_lengkap' => 'Sudah Upload',
            'nik_ktp' => '3173012345678901',
            'nrk_pegawai' => 'NRK-001',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-06-15',
        ]);
        $pending = $this->makeParticipant([
            'nama_lengkap' => 'Belum Upload',
            'nik_ktp' => '3173012345678902',
            'nrk_pegawai' => 'NRK-002',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-06-16',
        ]);

        McuResult::query()->create([
            'participant_id' => $uploaded->id,
            'schedule_id' => null,
            'tanggal_pemeriksaan' => '2026-06-15',
            'hasil_pemeriksaan' => '',
            'status_kesehatan' => 'Sehat',
            'file_hasil' => 'mcu-results/sample.pdf',
            'uploaded_by' => (string) $admin->id,
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.mcu-results.index', ['status_hasil' => 'uploaded']))
            ->assertOk()
            ->assertSee('Sudah Upload')
            ->assertDontSee('Belum Upload');
    }

    public function test_index_filters_by_period_day(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participantA = $this->makeParticipant([
            'nama_lengkap' => 'MCU 15 Juni',
            'nik_ktp' => '3173012345678911',
            'nrk_pegawai' => 'NRK-011',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-06-15',
        ]);
        $participantB = $this->makeParticipant([
            'nama_lengkap' => 'MCU 20 Juni',
            'nik_ktp' => '3173012345678912',
            'nrk_pegawai' => 'NRK-012',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2026-06-20',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.mcu-results.index', [
                'period' => 'hari',
                'period_value' => '2026-06-15',
            ]))
            ->assertOk()
            ->assertSee('MCU 15 Juni')
            ->assertDontSee('MCU 20 Juni');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeParticipant(array $overrides = []): Participant
    {
        return Participant::query()->create(array_merge([
            'nik_ktp' => '3173012345678999',
            'nrk_pegawai' => 'NRK-999',
            'nama_lengkap' => 'Peserta MCU Selesai',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Test',
            'ukpd' => 'UPT Test',
            'no_telp' => '081234567899',
            'email' => 'peserta@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Sudah MCU',
        ], $overrides));
    }

    private function makeSchedule(Participant $participant, string $date): Schedule
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
            'lokasi_pemeriksaan' => 'PPKP',
            'status' => 'Selesai',
        ]);
    }
}
