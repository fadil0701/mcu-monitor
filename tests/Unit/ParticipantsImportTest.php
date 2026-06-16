<?php

namespace Tests\Unit;

use App\Imports\ParticipantsRowsImport;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantsImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_skips_existing_participant_with_same_nrk(): void
    {
        Participant::query()->create([
            'nik_ktp' => '3175095701960003',
            'nrk_pegawai' => '220747',
            'nama_lengkap' => 'Nama Lama',
            'tempat_lahir' => '-',
            'tanggal_lahir' => '1996-01-17',
            'jenis_kelamin' => 'P',
            'skpd' => '-',
            'ukpd' => '-',
            'no_telp' => '081234567890',
            'email' => '',
            'status_pegawai' => 'CPNS',
            'status_mcu' => 'Belum MCU',
        ]);

        $import = new ParticipantsRowsImport;
        $row = [
            'nik_ktp' => '3175095701960003',
            'nrk_pegawai' => '220747',
            'nama_lengkap' => 'SRI CHERLY ASIH SITORUS',
            'tempat_lahir' => '-',
            'tanggal_lahir' => '1996-01-17',
            'jenis_kelamin' => 'P',
            'skpd' => 'Dinas Kesehatan',
            'ukpd' => '-',
            'no_telp' => '085795092233',
            'email' => '',
            'status_pegawai' => 'CPNS',
            'status_mcu' => 'Belum MCU',
        ];

        $model = $import->model($row);

        $this->assertNull($model);
        $this->assertSame(0, $import->createdCount);
        $this->assertSame(0, $import->updatedCount);
        $this->assertSame(1, $import->skippedCount);
        $this->assertSame(1, Participant::query()->count());
        $this->assertDatabaseHas('participants', [
            'nrk_pegawai' => '220747',
            'nama_lengkap' => 'Nama Lama',
            'skpd' => '-',
        ]);
    }

    public function test_import_creates_new_participant_when_nrk_is_unique(): void
    {
        $import = new ParticipantsRowsImport;
        $row = [
            'nik_ktp' => '3175095701960004',
            'nrk_pegawai' => '220748',
            'nama_lengkap' => 'Peserta Baru',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1992-08-12',
            'no_telp' => '081234567891',
            'status_pegawai' => 'PNS',
        ];

        $model = $import->model($row);
        $this->assertInstanceOf(Participant::class, $model);
        $model?->save();

        $this->assertSame(1, $import->createdCount);
        $this->assertSame(0, $import->updatedCount);
        $this->assertSame(0, $import->skippedCount);
        $this->assertDatabaseHas('participants', [
            'nrk_pegawai' => '220748',
            'nama_lengkap' => 'Peserta Baru',
        ]);
    }

    public function test_import_accepts_only_mandatory_fields_for_new_participant(): void
    {
        $import = new ParticipantsRowsImport;
        $row = [
            'nik_ktp' => '3175095701960005',
            'nama_lengkap' => 'Peserta Minimal',
        ];

        $model = $import->model($row);
        $this->assertInstanceOf(Participant::class, $model);
        $model?->save();

        $this->assertDatabaseHas('participants', [
            'nik_ktp' => '3175095701960005',
            'nama_lengkap' => 'Peserta Minimal',
            'jenis_kelamin' => 'L',
            'nrk_pegawai' => 'NRK-3175095701960005',
            'no_telp' => '-',
            'skpd' => '-',
        ]);
    }

    public function test_import_skips_existing_when_only_mandatory_fields_provided(): void
    {
        Participant::query()->create([
            'nik_ktp' => '3175095701960006',
            'nrk_pegawai' => '220749',
            'nama_lengkap' => 'Nama Lama',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-05-05',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Kesehatan',
            'ukpd' => 'UKPD A',
            'no_telp' => '081111111111',
            'email' => 'lama@example.com',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
        ]);

        $import = new ParticipantsRowsImport;
        $import->model([
            'nik_ktp' => '3175095701960006',
            'nama_lengkap' => 'Nama Baru',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1990-05-05',
        ]);

        $this->assertDatabaseHas('participants', [
            'nik_ktp' => '3175095701960006',
            'nama_lengkap' => 'Nama Lama',
            'skpd' => 'Dinas Kesehatan',
            'no_telp' => '081111111111',
            'email' => 'lama@example.com',
        ]);
        $this->assertSame(1, $import->skippedCount);
    }

    public function test_import_accepts_pendidikan_terakhir(): void
    {
        $import = new ParticipantsRowsImport;
        $row = [
            'nik_ktp' => '3175095701960007',
            'nama_lengkap' => 'Peserta Pendidikan',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '1990-03-10',
            'pendidikan_terakhir' => 'Sarjana',
        ];

        $model = $import->model($row);
        $model?->save();

        $this->assertDatabaseHas('participants', [
            'nik_ktp' => '3175095701960007',
            'pendidikan_terakhir' => 'Sarjana',
        ]);
    }
}