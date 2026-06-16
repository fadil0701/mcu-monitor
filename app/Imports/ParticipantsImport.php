<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ParticipantsImport implements WithMultipleSheets
{
    public function __construct(
        public readonly ParticipantsRowsImport $rows = new ParticipantsRowsImport,
    ) {}

    public function sheets(): array
    {
        return [
            'Data Peserta' => $this->rows,
        ];
    }

    public function __get(string $name): mixed
    {
        if (in_array($name, ['createdCount', 'updatedCount'], true)) {
            return $this->rows->$name;
        }

        throw new \InvalidArgumentException("Property [{$name}] tidak ada pada ".self::class);
    }
}
