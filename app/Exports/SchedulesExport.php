<?php

namespace App\Exports;

use App\Models\Schedule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SchedulesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly ?array $filters = null) {}

    public function collection(): Collection
    {
        $query = Schedule::query();

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('tanggal_pemeriksaan', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('tanggal_pemeriksaan', '<=', $this->filters['end_date']);
        }
        if (!empty($this->filters['skpd'])) {
            $query->where('skpd', $this->filters['skpd']);
        }

        return $query->orderByDesc('tanggal_pemeriksaan')->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal Pemeriksaan', 'Jam Pemeriksaan', 'Status', 'No. Antrian', 'Lokasi',
            'NIK', 'NRK', 'Nama', 'JK', 'SKPD', 'UKPD', 'No Telp', 'Email', 'Catatan',
        ];
    }

    /** @param \App\Models\Schedule $row */
    public function map($row): array
    {
        return [
            optional($row->tanggal_pemeriksaan)->format('Y-m-d'),
            optional($row->jam_pemeriksaan)->format('H:i'),
            $row->status,
            $row->queue_number,
            $row->lokasi_pemeriksaan,
            $row->nik_ktp,
            $row->nrk_pegawai,
            $row->nama_lengkap,
            $row->jenis_kelamin,
            $row->skpd,
            $row->ukpd,
            $row->no_telp,
            $row->email,
            $row->catatan,
        ];
    }
}


