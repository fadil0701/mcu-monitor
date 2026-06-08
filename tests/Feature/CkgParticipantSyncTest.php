<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Services\CkgParticipantSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CkgParticipantSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_inserts_eligible_participant_from_ckg(): void
    {
        Http::fake([
            'http://ckg.test/api/bridge/mcu/participants*' => Http::response([
                'meta' => [
                    'page' => 1,
                    'per_page' => 100,
                    'total' => 1,
                    'last_page' => 1,
                ],
                'data' => [[
                    'ckg_peserta_id' => 42,
                    'ckg_registration_code' => 'CKG-0042',
                    'nik' => '3173012345678001',
                    'nama_lengkap' => 'Budi ASN',
                    'tanggal_lahir' => '1990-05-10',
                    'jenis_kelamin' => 'male',
                    'no_hp' => '081234567890',
                    'work_unit' => 'ASN DKI Jakarta',
                    'participant_category' => 'pns',
                    'employee_nrk' => '998877',
                    'skpd' => 'Dinas Kesehatan',
                    'ukpd' => 'UPT A',
                ]],
            ]),
        ]);

        $stats = app(CkgParticipantSyncService::class)->sync();

        $this->assertSame(1, $stats['inserted']);
        $this->assertDatabaseHas('participants', [
            'ckg_peserta_id' => 42,
            'nik_ktp' => '3173012345678001',
            'nrk_pegawai' => '998877',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
            'email' => '3173012345678001@ckg-sync.local',
        ]);
    }

    public function test_sync_updates_existing_participant_without_resetting_mcu_status(): void
    {
        $participant = Participant::query()->create([
            'ckg_peserta_id' => 77,
            'nik_ktp' => '3173012345678077',
            'nrk_pegawai' => '111111',
            'nama_lengkap' => 'Nama Lama',
            'tempat_lahir' => '-',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'SKPD Lama',
            'ukpd' => '-',
            'no_telp' => '081111111111',
            'email' => '3173012345678077@ckg-sync.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Sudah MCU',
            'tanggal_mcu_terakhir' => '2024-06-01',
        ]);

        Http::fake([
            'http://ckg.test/api/bridge/mcu/participants*' => Http::response([
                'meta' => ['page' => 1, 'per_page' => 100, 'total' => 1, 'last_page' => 1],
                'data' => [[
                    'ckg_peserta_id' => 77,
                    'ckg_registration_code' => 'CKG-0077',
                    'nik' => '3173012345678077',
                    'nama_lengkap' => 'Nama Baru',
                    'tanggal_lahir' => '1990-01-01',
                    'jenis_kelamin' => 'male',
                    'no_hp' => '082222222222',
                    'work_unit' => 'ASN DKI Jakarta',
                    'participant_category' => 'pns',
                    'employee_nrk' => '222222',
                    'skpd' => 'SKPD Baru',
                    'ukpd' => 'UPT Baru',
                ]],
            ]),
        ]);

        $stats = app(CkgParticipantSyncService::class)->sync();

        $this->assertSame(1, $stats['updated']);
        $participant->refresh();
        $this->assertSame('Nama Baru', $participant->nama_lengkap);
        $this->assertSame('Sudah MCU', $participant->status_mcu);
        $this->assertSame('2024-06-01', $participant->tanggal_mcu_terakhir?->format('Y-m-d'));
    }

    public function test_sync_skips_ineligible_payload(): void
    {
        Http::fake([
            'http://ckg.test/api/bridge/mcu/participants*' => Http::response([
                'meta' => ['page' => 1, 'per_page' => 100, 'total' => 1, 'last_page' => 1],
                'data' => [[
                    'ckg_peserta_id' => 99,
                    'nik' => '3173012345678099',
                    'nama_lengkap' => 'Non ASN',
                    'tanggal_lahir' => '1990-01-01',
                    'jenis_kelamin' => 'female',
                    'work_unit' => 'NON ASN DKI Jakarta',
                    'participant_category' => 'pns',
                ]],
            ]),
        ]);

        $stats = app(CkgParticipantSyncService::class)->sync();

        $this->assertSame(1, $stats['skipped']);
        $this->assertDatabaseCount('participants', 0);
    }
}
