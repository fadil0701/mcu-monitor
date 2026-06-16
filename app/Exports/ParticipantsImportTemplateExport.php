<?php

namespace App\Exports;

use App\Support\ParticipantEducation;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ParticipantsImportTemplateExport
{
    /** @return list<string> */
    public static function headings(): array
    {
        return [
            'NIK',
            'Nama',
            'Jenis Kelamin',
            'NRK',
            'Tempat Lahir',
            'Tanggal Lahir',
            'SKPD',
            'UKPD',
            'No Telp',
            'Email',
            'Status Pegawai',
            'Pendidikan Terakhir',
            'Status MCU',
            'Tanggal MCU Terakhir',
            'Catatan',
        ];
    }

    public static function saveTo(string $path): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Peserta');

        $headings = self::headings();
        $examples = [
            [
                '3173012345678901',
                'Budi Santoso',
                'L',
                '123456',
                'Jakarta',
                '1990-01-15',
                'Dinas Kesehatan',
                'Puskesmas Kecamatan',
                '081234567890',
                'budi@example.com',
                'PNS',
                'Sarjana',
                'Belum MCU',
                '',
                'Contoh baris lengkap',
            ],
            [
                '3174012345678902',
                'Siti Aminah',
                'P',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'Contoh minimal (hanya NIK, Nama, JK)',
            ],
        ];

        foreach ($headings as $colIndex => $heading) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $heading);
        }

        foreach ($examples as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $col = $colIndex + 1;
                $rowNum = $rowIndex + 2;

                if (in_array($col, [1, 4, 9], true)) {
                    $sheet->setCellValueExplicitByColumnAndRow($col, $rowNum, (string) $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValueByColumnAndRow($col, $rowNum, $value);
                }
            }
        }

        $lastCol = count($headings);
        $sheet->getStyleByColumnAndRow(1, 1, $lastCol, 1)->getFont()->setBold(true);
        $sheet->getStyleByColumnAndRow(1, 1, $lastCol, 1)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE7E9ED');

        foreach ([1, 4, 9] as $col) {
            $sheet->getStyleByColumnAndRow($col, 2, $col, 500)
                ->getNumberFormat()
                ->setFormatCode('@');
        }

        foreach (range(1, $lastCol) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        self::fillGuideSheet($guide);

        $spreadsheet->setActiveSheetIndex(0);

        (new Xlsx($spreadsheet))->save($path);
    }

    private static function fillGuideSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $lines = [
            ['PETUNJUK IMPORT DATA PESERTA MCU'],
            [],
            ['Kolom wajib'],
            ['NIK', '16 digit angka. Format kolom sebagai Teks di Excel agar tidak berubah.'],
            ['Nama', 'Nama lengkap peserta.'],
            ['Jenis Kelamin', 'L (Laki-laki) atau P (Perempuan).'],
            [],
            ['Kolom opsional'],
            ['NRK', 'Nomor registrasi kepegawaian. Kosongkan → sistem isi NRK-{NIK}.'],
            ['Tempat Lahir', 'Kosongkan → "-".'],
            ['Tanggal Lahir', 'Format YYYY-MM-DD. Kosongkan → diambil dari NIK jika memungkinkan.'],
            ['SKPD', 'Nama SKPD/instansi.'],
            ['UKPD', 'Unit kerja.'],
            ['No Telp', 'Format Teks. Kosongkan → "-".'],
            ['Email', 'Kosongkan jika belum ada.'],
            ['Status Pegawai', 'CPNS, PNS, atau PPPK. Default: PNS.'],
            ['Pendidikan Terakhir', implode(', ', ParticipantEducation::levels())],
            ['Status MCU', 'Belum MCU, Sudah MCU, atau Ditolak. Default: Belum MCU.'],
            ['Tanggal MCU Terakhir', 'Format YYYY-MM-DD.'],
            ['Catatan', 'Catatan bebas.'],
            [],
            ['Catatan umum'],
            ['• Baris dengan NIK yang sudah ada akan diperbarui (update), bukan dibuat duplikat.'],
            ['• Pencocokan update: NIK terlebih dahulu, lalu NRK (jika bukan NRK-{NIK}).'],
            ['• File didukung: XLSX, XLS, CSV.'],
        ];

        foreach ($lines as $rowIndex => $line) {
            foreach ($line as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('A8')->getFont()->setBold(true);
        $sheet->getStyle('A22')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(72);
        $sheet->getStyle('B5:B20')->getFont()->setColor(new Color('FF566A7F'));
    }
}
