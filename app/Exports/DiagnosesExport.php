<?php

namespace App\Exports;

use App\Models\McuResult;
use App\Models\Diagnosis;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DiagnosesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private array $rows = [];

    public function __construct(private readonly ?array $filters = null) {}

    public function collection(): Collection
    {
        $query = McuResult::query();

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('tanggal_pemeriksaan', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('tanggal_pemeriksaan', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['skpd'])) {
            $query->whereHas('participant', function ($q) {
                $q->where('skpd', $this->filters['skpd']);
            });
        }

        $results = $query->select(['diagnosis', 'diagnosis_list', 'participant_id'])->with('participant:id,skpd,ukpd')->get();

        $aggregation = [];
        foreach ($results as $res) {
            $skpd = optional($res->participant)->skpd ?? '-';
            $ukpd = optional($res->participant)->ukpd ?? '-';
            $items = [];
            if (is_array($res->diagnosis_list) && count($res->diagnosis_list) > 0) {
                $items = $res->diagnosis_list;
            } elseif (!empty($res->diagnosis)) {
                $items = array_filter(array_map('trim', explode(',', (string) $res->diagnosis)));
            }
            if (empty($items)) {
                continue;
            }
            foreach ($items as $nameOrCode) {
                $diag = Diagnosis::where('name', $nameOrCode)->orWhere('code', $nameOrCode)->first();
                $code = $diag?->code ?? '';
                $name = $diag?->name ?? $nameOrCode;
                $key = implode('|', [$code, $name, $skpd, $ukpd]);
                $aggregation[$key] = ($aggregation[$key] ?? 0) + 1;
            }
        }

        $this->rows = [];
        foreach ($aggregation as $key => $count) {
            [$code, $name, $skpd, $ukpd] = explode('|', $key);
            $this->rows[] = [
                'code' => $code,
                'name' => $name,
                'skpd' => $skpd,
                'ukpd' => $ukpd,
                'total' => $count,
            ];
        }

        return collect($this->rows);
    }

    public function headings(): array
    {
        return [
            'Kode Diagnosis', 'Nama Diagnosis', 'SKPD', 'UKPD', 'Jumlah',
        ];
    }

    public function map($row): array
    {
        return [
            $row['code'],
            $row['name'],
            $row['skpd'],
            $row['ukpd'],
            $row['total'],
        ];
    }
}


