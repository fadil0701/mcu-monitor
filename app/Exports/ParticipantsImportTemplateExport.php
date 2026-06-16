<?php

namespace App\Exports;

use App\Support\InstansiPemprovDkiCatalog;
use App\Support\ParticipantEducation;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ParticipantsImportTemplateExport
{
    private const MANDATORY_FILL = 'FFFFE0E0';

    private const OPTIONAL_FILL = 'FFE8F4FD';

    private const DATA_ROWS = 500;

    /** @return list<string> */
    public static function mandatoryHeadings(): array
    {
        return ['NIK *', 'Nama *'];
    }

    /** @return list<int> indeks kolom 1-based yang wajib */
    public static function mandatoryColumnIndexes(): array
    {
        return [1, 2];
    }

    /** @return list<string> */
    public static function headings(): array
    {
        return [
            'NIK *',
            'Nama *',
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

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data Peserta');

        $referensi = $spreadsheet->createSheet();
        $referensi->setTitle('Referensi');
        $ranges = self::fillReferensiSheet($referensi);

        self::fillDataSheet($dataSheet, $ranges);

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        self::fillGuideSheet($guide);

        $spreadsheet->setActiveSheetIndex(0);

        (new Xlsx($spreadsheet))->save($path);
    }

    /**
     * @return array{jk: string, status_pegawai: string, status_mcu: string, pendidikan: string, skpd: string}
     */
    private static function fillReferensiSheet(Worksheet $sheet): array
    {
        $headers = [
            'Jenis Kelamin',
            'Status Pegawai',
            'Status MCU',
            'Pendidikan Terakhir',
            'SKPD / Instansi Pemprov DKI',
        ];

        foreach ($headers as $colIndex => $header) {
            $col = $colIndex + 1;
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $sheet->getStyleByColumnAndRow($col, 1)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($col, 1)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE7E9ED');
        }

        $lists = [
            ['L', 'P'],
            ['CPNS', 'PNS', 'PPPK'],
            ['Belum MCU', 'Sudah MCU', 'Ditolak'],
            ParticipantEducation::levels(),
            InstansiPemprovDkiCatalog::defaultNames(),
        ];

        $maxRows = max(array_map('count', $lists));

        foreach ($lists as $colIndex => $items) {
            $col = $colIndex + 1;
            foreach ($items as $rowIndex => $value) {
                $sheet->setCellValueByColumnAndRow($col, $rowIndex + 2, $value);
            }
        }

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(48);

        $lastSkpdRow = count($lists[4]) + 1;

        return [
            'jk' => sprintf("'Referensi'!\$A\$2:\$A\$%d", count($lists[0]) + 1),
            'status_pegawai' => sprintf("'Referensi'!\$B\$2:\$B\$%d", count($lists[1]) + 1),
            'status_mcu' => sprintf("'Referensi'!\$C\$2:\$C\$%d", count($lists[2]) + 1),
            'pendidikan' => sprintf("'Referensi'!\$D\$2:\$D\$%d", count($lists[3]) + 1),
            'skpd' => sprintf("'Referensi'!\$E\$2:\$E\$%d", $lastSkpdRow),
        ];
    }

    /**
     * @param  array{jk: string, status_pegawai: string, status_mcu: string, pendidikan: string, skpd: string}  $ranges
     */
    private static function fillDataSheet(Worksheet $sheet, array $ranges): void
    {
        $headings = self::headings();
        $mandatoryCols = self::mandatoryColumnIndexes();

        foreach ($headings as $colIndex => $heading) {
            $col = $colIndex + 1;
            $sheet->setCellValueByColumnAndRow($col, 1, $heading);
            $fill = in_array($col, $mandatoryCols, true) ? self::MANDATORY_FILL : self::OPTIONAL_FILL;
            $sheet->getStyleByColumnAndRow($col, 1)->getFont()->setBold(true);
            $sheet->getStyleByColumnAndRow($col, 1)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($fill);
        }

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
                '',
                'Contoh minimal (kolom merah wajib)',
            ],
        ];

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

        foreach ([1, 4, 9] as $col) {
            $sheet->getStyleByColumnAndRow($col, 2, $col, self::DATA_ROWS)
                ->getNumberFormat()
                ->setFormatCode('@');
        }

        foreach (range(1, count($headings)) as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $sheet->freezePane('A2');

        self::applyListValidation($sheet, 'C', 2, self::DATA_ROWS, $ranges['jk']);
        self::applyListValidation($sheet, 'G', 2, self::DATA_ROWS, $ranges['skpd']);
        self::applyListValidation($sheet, 'K', 2, self::DATA_ROWS, $ranges['status_pegawai']);
        self::applyListValidation($sheet, 'L', 2, self::DATA_ROWS, $ranges['pendidikan']);
        self::applyListValidation($sheet, 'M', 2, self::DATA_ROWS, $ranges['status_mcu']);
    }

    private static function applyListValidation(
        Worksheet $sheet,
        string $columnLetter,
        int $startRow,
        int $endRow,
        string $listFormula,
    ): void {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $validation = $sheet->getCell("{$columnLetter}{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowInputMessage(true);
            $validation->setFormula1($listFormula);
        }
    }

    private static function fillGuideSheet(Worksheet $sheet): void
    {
        $lines = [
            ['PETUNJUK IMPORT DATA PESERTA MCU'],
            [],
            ['Legenda warna header (sheet Data Peserta)'],
            ['Merah muda', 'Kolom WAJIB diisi'],
            ['Biru muda', 'Kolom OPSIONAL'],
            [],
            ['Kolom wajib'],
            ['NIK *', '16 digit angka. Format kolom sebagai Teks di Excel.'],
            ['Nama *', 'Nama lengkap peserta.'],
            ['Jenis Kelamin', 'Opsional. L atau P — pilih dari dropdown (lihat sheet Referensi).'],
            ['Tanggal Lahir', 'Opsional. Format YYYY-MM-DD (contoh: 1990-01-15).'],
            [],
            ['Kolom opsional'],
            ['NRK', 'Kosongkan → sistem isi NRK-{NIK}.'],
            ['Tempat Lahir', 'Kosongkan → "-".'],
            ['SKPD', 'Pilih dari dropdown — daftar lengkap di sheet Referensi.'],
            ['UKPD', 'Unit kerja (isi bebas).'],
            ['No Telp', 'Format Teks. Kosongkan → "-".'],
            ['Email', 'Kosongkan jika belum ada.'],
            ['Status Pegawai', 'CPNS, PNS, PPPK — dropdown.'],
            ['Pendidikan Terakhir', 'Pilih dari dropdown — daftar di sheet Referensi.'],
            ['Status MCU', 'Belum MCU, Sudah MCU, Ditolak — dropdown.'],
            ['Tanggal MCU Terakhir', 'Format YYYY-MM-DD.'],
            ['Catatan', 'Catatan bebas.'],
            [],
            ['Sheet Referensi'],
            ['Berisi daftar nilai yang boleh dipilih untuk SKPD, Pendidikan, Status Pegawai, Status MCU, dan Jenis Kelamin.'],
            [],
            ['Catatan umum'],
            ['• Isi data hanya di sheet Data Peserta (bukan sheet Referensi/Petunjuk).'],
            ['• Baris dengan NIK yang sudah ada akan diperbarui (update), bukan dibuat duplikat.'],
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
        $sheet->getStyle('A14')->getFont()->setBold(true);
        $sheet->getStyle('A26')->getFont()->setBold(true);
        $sheet->getStyle('A29')->getFont()->setBold(true);
        $sheet->getStyle('A4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::MANDATORY_FILL);
        $sheet->getStyle('A5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::OPTIONAL_FILL);
        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(72);
        $sheet->getStyle('B9:B24')->getFont()->setColor(new Color('FF566A7F'));
    }
}
