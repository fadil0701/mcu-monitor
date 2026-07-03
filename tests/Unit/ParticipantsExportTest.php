<?php

namespace Tests\Unit;

use App\Exports\ParticipantsExport;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ParticipantsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_stores_nik_column_as_plain_text(): void
    {
        $nik = '3174012345678901';

        Participant::query()->create([
            'nik_ktp' => $nik,
            'nrk_pegawai' => '192656',
            'nama_lengkap' => 'Abdul Arif',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Pendidikan',
            'ukpd' => 'SMK Negeri 56 Jakarta',
            'no_telp' => '081234567890',
            'email' => 'abdul@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'participants_export_').'.xlsx';
        file_put_contents($path, Excel::raw(new ParticipantsExport, \Maatwebsite\Excel\Excel::XLSX));

        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertSame($nik, $sheet->getCell('A2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A2')->getDataType());
        $this->assertSame('@', $sheet->getStyle('A2')->getNumberFormat()->getFormatCode());

        @unlink($path);
    }
}
