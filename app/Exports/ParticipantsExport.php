<?php

namespace App\Exports;

use App\Models\Participant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ParticipantsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly ?array $filters = null) {}

    public function collection(): Collection
    {
        $query = Participant::query();

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('tanggal_mcu_terakhir', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('tanggal_mcu_terakhir', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['skpd'])) {
            $query->where('skpd', $this->filters['skpd']);
        }

        if (!empty($this->filters['status_pegawai'])) {
            $query->where('status_pegawai', $this->filters['status_pegawai']);
        }

        return $query->orderBy('nama_lengkap')->get();
    }

    public function headings(): array
    {
        return [
            'NIK', 'NRK', 'Nama', 'Tempat Lahir', 'Tanggal Lahir', 'JK', 'SKPD', 'UKPD', 'No Telp', 'Email', 'Status Pegawai', 'Status MCU', 'Tanggal MCU Terakhir', 'Catatan',
        ];
    }

    /** @param \App\Models\Participant $row */
    public function map($row): array
    {
        return [
            $row->nik_ktp,
            $row->nrk_pegawai,
            $row->nama_lengkap,
            $row->tempat_lahir,
            optional($row->tanggal_lahir)->format('Y-m-d'),
            $row->jenis_kelamin,
            $row->skpd,
            $row->ukpd,
            $row->no_telp,
            $row->email,
            $row->status_pegawai,
            $row->status_mcu,
            optional($row->tanggal_mcu_terakhir)->format('Y-m-d'),
            $row->catatan,
        ];
    }
}






