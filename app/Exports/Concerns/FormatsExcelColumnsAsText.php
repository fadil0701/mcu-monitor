<?php

namespace App\Exports\Concerns;

use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

trait FormatsExcelColumnsAsText
{
    /**
     * @return list<string> Kolom Excel (mis. A, F) yang harus disimpan sebagai teks.
     */
    abstract protected function textFormattedColumns(): array;

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                if ($highestRow < 2) {
                    return;
                }

                foreach ($this->textFormattedColumns() as $column) {
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $coordinate = "{$column}{$row}";
                        $value = $sheet->getCell($coordinate)->getValue();

                        if ($value === null || $value === '') {
                            continue;
                        }

                        $sheet->setCellValueExplicit(
                            $coordinate,
                            (string) $value,
                            DataType::TYPE_STRING,
                        );
                    }

                    $sheet->getStyle("{$column}2:{$column}{$highestRow}")
                        ->getNumberFormat()
                        ->setFormatCode('@');
                }
            },
        ];
    }
}
