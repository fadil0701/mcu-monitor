@extends('layouts.sneat.app')

@section('title', 'Detail Peserta')

@section('pageTitle', 'Detail Peserta')

@section('content')

<x-common.component-card title="{{ $participant->nama_lengkap }}">
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.participants.edit', $participant) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('admin.participants.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <dt class="text-muted mb-1">NIK KTP</dt>
            <dd class="mb-0">{{ $participant->nik_ktp }}</dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">NRK Pegawai</dt>
            <dd class="mb-0">{{ $participant->nrk_pegawai }}</dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">Tempat, Tanggal Lahir</dt>
            <dd class="mb-0">{{ $participant->tempat_lahir }}, {{ $participant->tanggal_lahir?->format('d/m/Y') }}</dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">Jenis Kelamin</dt>
            <dd class="mb-0">{{ $participant->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">SKPD / UKPD</dt>
            <dd class="mb-0">{{ $participant->skpd }} / {{ $participant->ukpd }}</dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">Status Pegawai</dt>
            <dd class="mb-0">{{ $participant->status_pegawai }}</dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">Pendidikan Terakhir</dt>
            <dd class="mb-0">{{ $participant->pendidikan_terakhir ?: '-' }}</dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">No. Telp</dt>
            <dd class="mb-0">{{ $participant->no_telp }}</dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">Email</dt>
            <dd class="mb-0">{{ $participant->email }}</dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">Status MCU</dt>
            <dd class="mb-0">
                <span class="badge {{ $participant->status_mcu === 'Sudah MCU' ? 'bg-success' : ($participant->status_mcu === 'Ditolak' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $participant->status_mcu }}</span>
            </dd>
        </div>
        <div class="col-md-6">
            <dt class="text-muted mb-1">Status CKG Terakhir</dt>
            <dd class="mb-0">
                @include('partials.participant-ckg-status-badge', ['participant' => $participant])
                @if($participant->ckgStatusHint())
                    <small class="text-muted d-block mt-1">{{ $participant->ckgStatusHint() }}</small>
                @endif
            </dd>
        </div>
        @if($participant->catatan)
            <div class="col-12">
                <dt class="text-muted mb-1">Catatan</dt>
                <dd class="mb-0">{{ $participant->catatan }}</dd>
            </div>
        @endif
    </div>
</x-common.component-card>

<x-common.component-card title="Jadwal MCU Terbaru" class="mt-6">
    @if($participant->schedules->count())
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($participant->schedules as $s)
                        <tr>
                            <td>{{ $s->tanggal_pemeriksaan?->format('d/m/Y') }}</td>
                            <td>{{ $s->jam_pemeriksaan ? \Carbon\Carbon::parse($s->jam_pemeriksaan)->format('H:i') : '-' }}</td>
                            <td class="text-truncate" style="max-width: 200px;">{{ Str::limit($s->lokasi_pemeriksaan, 30) }}</td>
                            <td>{{ $s->status }}</td>
                            <td><a href="{{ route('admin.schedules.edit', $s) }}">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-2 small text-muted"><a href="{{ route('admin.schedules.index', ['search' => $participant->nik_ktp]) }}">Lihat semua jadwal</a></p>
    @else
        <p class="text-muted mb-0">Belum ada jadwal MCU.</p>
    @endif
</x-common.component-card>

<x-common.component-card title="Hasil MCU Terbaru" class="mt-6">
    @if($participant->mcuResults->count())
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Publikasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($participant->mcuResults as $r)
                        <tr>
                            <td>{{ $r->tanggal_pemeriksaan?->format('d/m/Y') }}</td>
                            <td>{{ $r->is_published ? 'Ya' : 'Tidak' }}</td>
                            <td><a href="{{ route('admin.mcu-results.edit', $r) }}">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-muted mb-0">Belum ada hasil MCU.</p>
    @endif
</x-common.component-card>
@endsection
