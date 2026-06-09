@extends('layouts.sneat.app')

@section('title', 'Permintaan Reschedule')
@section('pageTitle', 'Permintaan Reschedule')

@section('content')
<x-common.component-card title="Daftar Permintaan Reschedule">
    <form method="GET" class="row g-3 align-items-end mb-4">
        <div class="col-md-3 col-lg-2">
            <label class="form-label mb-1">SKPD</label>
            <select name="skpd" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach($instansiPemprov ?? [] as $instansi)
                    <option value="{{ $instansi }}" {{ request('skpd') == $instansi ? 'selected' : '' }}>{{ $instansi }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 col-lg-2">
            <label class="form-label mb-1">Dari Tanggal</label>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 col-lg-2">
            <label class="form-label mb-1">Sampai Tanggal</label>
            <input type="date" name="until" value="{{ request('until') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-filter-alt me-1"></i> Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Peserta</th>
                    <th>NIK</th>
                    <th>Tgl Lama</th>
                    <th>Jam Lama</th>
                    <th>Tgl Baru</th>
                    <th>Jam Baru</th>
                    <th>Alasan</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schedules as $s)
                    <tr>
                        <td class="fw-medium">{{ $s->participant->nama_lengkap ?? $s->nama_lengkap }}</td>
                        <td>{{ $s->nik_ktp }}</td>
                        <td>{{ $s->tanggal_pemeriksaan?->format('d/m/Y') }}</td>
                        <td>{{ $s->jam_pemeriksaan ? (\Carbon\Carbon::parse($s->jam_pemeriksaan)->format('H:i')) : '-' }}</td>
                        <td>{{ $s->reschedule_new_date?->format('d/m/Y') }}</td>
                        <td>{{ $s->reschedule_new_time ? (\Carbon\Carbon::parse($s->reschedule_new_time)->format('H:i')) : '-' }}</td>
                        <td class="text-truncate" style="max-width: 200px;" title="{{ $s->reschedule_reason }}">{{ Str::limit($s->reschedule_reason, 30) }}</td>
                        <td>
                            <div class="d-flex flex-wrap justify-content-center gap-1">
                                <form method="POST" action="{{ route('admin.reschedule-center.approve', $s) }}" class="d-inline" onsubmit="return confirm('Setujui reschedule ini?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success"><i class="bx bx-check me-1"></i> Setujui</button>
                                </form>
                                <form method="POST" action="{{ route('admin.reschedule-center.reject', $s) }}" class="d-inline" onsubmit="return confirm('Tolak permintaan reschedule?');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bx bx-x me-1"></i> Tolak</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada permintaan reschedule.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $schedules->links() }}</div>
</x-common.component-card>
@endsection
