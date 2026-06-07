<?php

namespace App\Exports;

use App\Models\McuResult;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class McuResultsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
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

        return $query->with(['participant'])->orderByDesc('tanggal_pemeriksaan')->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal Pemeriksaan', 'Status Kesehatan', 'Diagnosis', 'Rekomendasi',
            'NIK', 'NRK', 'Nama', 'JK', 'SKPD', 'UKPD', 'No Telp', 'Email',
        ];
    }

    /** @param \App\Models\McuResult $row */
    public function map($row): array
    {
        return [
            optional($row->tanggal_pemeriksaan)->format('Y-m-d'),
            $row->status_kesehatan,
            $row->diagnosis_text,
            $row->rekomendasi,
            optional($row->participant)->nik_ktp,
            optional($row->participant)->nrk_pegawai,
            optional($row->participant)->nama_lengkap,
            optional($row->participant)->jenis_kelamin,
            optional($row->participant)->skpd,
            optional($row->participant)->ukpd,
            optional($row->participant)->no_telp,
            optional($row->participant)->email,
        ];
    }
}


