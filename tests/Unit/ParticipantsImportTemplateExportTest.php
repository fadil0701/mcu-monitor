<?php

namespace Tests\Unit;

use App\Exports\ParticipantsImportTemplateExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ParticipantsImportTemplateExportTest extends TestCase
{
    public function test_template_has_data_referensi_and_guide_sheets(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl_test_').'.xlsx';
        ParticipantsImportTemplateExport::saveTo($path);

        $spreadsheet = IOFactory::load($path);
        $dataSheet = $spreadsheet->getSheetByName('Data Peserta');
        $referensiSheet = $spreadsheet->getSheetByName('Referensi');
        $guideSheet = $spreadsheet->getSheetByName('Petunjuk');

        $this->assertNotNull($dataSheet);
        $this->assertNotNull($referensiSheet);
        $this->assertNotNull($guideSheet);
        $this->assertSame('NIK *', $dataSheet->getCell('A1')->getValue());
        $this->assertSame('Tanggal Lahir *', $dataSheet->getCell('F1')->getValue());
        $this->assertSame('3173012345678901', $dataSheet->getCell('A2')->getValue());
        $this->assertSame('Jenis Kelamin', $referensiSheet->getCell('A1')->getValue());
        $this->assertSame('SKPD / Instansi Pemprov DKI', $referensiSheet->getCell('E1')->getValue());
        $this->assertSame('L', $referensiSheet->getCell('A2')->getValue());
        $this->assertNotEmpty($referensiSheet->getCell('E2')->getValue());
        $this->assertStringContainsString('PETUNJUK IMPORT', (string) $guideSheet->getCell('A1')->getValue());
        $this->assertStringContainsString('Merah muda', (string) $guideSheet->getCell('A4')->getValue());

        @unlink($path);
    }
}
