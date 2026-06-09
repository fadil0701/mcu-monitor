@extends('layouts.sneat.app')

@section('title', 'Laporan')
@section('pageTitle', 'Laporan')

@section('content')
<x-common.component-card title="Download Laporan Excel">
    <p class="text-muted mb-4">Pilih filter (opsional) lalu klik tombol download.</p>
    <form method="GET" class="row g-3 align-items-end mb-4">
        <div class="col-md-6 col-lg-3">
            <label class="form-label mb-1">Tanggal Mulai</label>
            <input type="date" name="start_date" value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-6 col-lg-3">
            <label class="form-label mb-1">Tanggal Selesai</label>
            <input type="date" name="end_date" value="{{ request('end_date', now()->format('Y-m-d')) }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-6 col-lg-3">
            <label class="form-label mb-1">SKPD</label>
            <select name="skpd" class="form-select form-select-sm">
                <option value="">Semua SKPD</option>
                @foreach($instansiPemprov ?? [] as $instansi)
                    <option value="{{ $instansi }}" {{ request('skpd') === $instansi ? 'selected' : '' }}>{{ $instansi }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 col-lg-3">
            <label class="form-label mb-1">Status Pegawai</label>
            <select name="status_pegawai" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="PNS" {{ request('status_pegawai') === 'PNS' ? 'selected' : '' }}>PNS</option>
                <option value="CPNS" {{ request('status_pegawai') === 'CPNS' ? 'selected' : '' }}>CPNS</option>
                <option value="PPPK" {{ request('status_pegawai') === 'PPPK' ? 'selected' : '' }}>PPPK</option>
            </select>
        </div>
    </form>

    @php
        $query = request()->query();
    @endphp
    <div class="row g-3">
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('admin.reports.download', ['type' => 'participants'] + $query) }}" class="card h-100 text-body text-decoration-none">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-group bx-sm"></i></span>
                    </div>
                    <span class="fw-medium">Download Peserta</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('admin.reports.download', ['type' => 'schedules'] + $query) }}" class="card h-100 text-body text-decoration-none">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success"><i class="bx bx-calendar bx-sm"></i></span>
                    </div>
                    <span class="fw-medium">Download Jadwal</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('admin.reports.download', ['type' => 'mcu'] + $query) }}" class="card h-100 text-body text-decoration-none">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info"><i class="bx bx-file bx-sm"></i></span>
                    </div>
                    <span class="fw-medium">Download Hasil MCU</span>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="{{ route('admin.reports.download', ['type' => 'diagnoses'] + $query) }}" class="card h-100 text-body text-decoration-none">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-bar-chart-alt-2 bx-sm"></i></span>
                    </div>
                    <span class="fw-medium">Download Diagnosis</span>
                </div>
            </a>
        </div>
    </div>
</x-common.component-card>
@endsection
