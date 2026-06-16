<?php

namespace Tests\Unit;

use App\Exports\ParticipantsImportTemplateExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ParticipantsImportTemplateExportTest extends TestCase
{
    public function test_template_has_expected_headings_and_guide_sheet(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl_test_').'.xlsx';
        ParticipantsImportTemplateExport::saveTo($path);

        $spreadsheet = IOFactory::load($path);
        $dataSheet = $spreadsheet->getSheetByName('Data Peserta');
        $guideSheet = $spreadsheet->getSheetByName('Petunjuk');

        $this->assertNotNull($dataSheet);
        $this->assertNotNull($guideSheet);
        $this->assertSame('NIK', $dataSheet->getCell('A1')->getValue());
        $this->assertSame('Pendidikan Terakhir', $dataSheet->getCell('L1')->getValue());
        $this->assertSame('3173012345678901', $dataSheet->getCell('A2')->getValue());
        $this->assertStringContainsString('PETUNJUK IMPORT', (string) $guideSheet->getCell('A1')->getValue());

        @unlink($path);
    }
}
