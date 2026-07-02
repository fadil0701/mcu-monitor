@extends('layouts.sneat.app')

@section('title', 'Kalender Libur')
@section('pageTitle', 'Kalender Libur & Hari Kerja')

@section('content')
<div class="mb-4">
    <p class="text-muted mb-0">Kelola libur nasional, cuti bersama, dan blokir akhir pekan untuk jadwal pengajuan MCU peserta.</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <x-common.component-card title="Pengaturan umum">
            <p class="text-muted small mb-3">Sabtu dan Minggu otomatis tidak dapat dipilih saat opsi ini aktif.</p>
            <form method="POST" action="{{ route('admin.mcu-work-calendar.settings.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="block_weekends" value="0">
                <div class="form-check mb-3">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        id="block_weekends"
                        name="block_weekends"
                        value="1"
                        @checked($settings->block_weekends)
                    >
                    <label class="form-check-label" for="block_weekends">
                        <span class="fw-semibold">Blokir Sabtu &amp; Minggu</span>
                        <span class="d-block text-muted small">Berlaku untuk form pengajuan jadwal MCU oleh peserta.</span>
                    </label>
                </div>
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bx bx-save me-1"></i> Simpan pengaturan
                </button>
            </form>
        </x-common.component-card>
    </div>

    <div class="col-lg-6">
        <x-common.component-card title="Tambah libur">
            <p class="text-muted small mb-3">Libur nasional, cuti bersama, atau penutupan khusus.</p>
            <form method="POST" action="{{ route('admin.mcu-work-calendar.closures.store') }}" class="row g-3">
                @csrf
                <div class="col-12">
                    <label for="closure_date" class="form-label">Tanggal</label>
                    <input
                        type="date"
                        id="closure_date"
                        name="closure_date"
                        value="{{ old('closure_date') }}"
                        class="form-control @error('closure_date') is-invalid @enderror"
                        required
                    >
                    @error('closure_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="type" class="form-label">Jenis</label>
                    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                        @foreach($closureTypes as $code => $label)
                            <option value="{{ $code }}" @selected(old('type', 'libur_nasional') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="label" class="form-label">Keterangan</label>
                    <input
                        type="text"
                        id="label"
                        name="label"
                        value="{{ old('label') }}"
                        maxlength="255"
                        placeholder="Contoh: Hari Kemerdekaan RI"
                        class="form-control @error('label') is-invalid @enderror"
                        required
                    >
                    @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus me-1"></i> Simpan libur
                    </button>
                </div>
            </form>
        </x-common.component-card>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Ringkasan {{ $monthLabel }}</h5>
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="number" name="month" min="1" max="12" value="{{ $month }}" class="form-control form-control-sm" style="width: 4.5rem;">
            <input type="number" name="year" min="2024" max="2035" value="{{ $year }}" class="form-control form-control-sm" style="width: 5.5rem;">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Tampilkan</button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calendar['days'] as $day)
                        @php
                            $date = $day['date'];
                            $closure = $closures->first(fn ($row) => $row->closure_date->toDateString() === $date);
                        @endphp
                        <tr class="{{ $day['bookable'] ? '' : 'table-danger-subtle' }}">
                            <td>{{ \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d M Y (D)') }}</td>
                            <td>
                                @if($day['bookable'])
                                    <span class="badge bg-label-success">Hari kerja</span>
                                @else
                                    <span class="badge bg-label-danger">Tidak tersedia</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $day['closure_reason'] ?? '—' }}</td>
                            <td class="text-center">
                                @if($closure)
                                    <form method="POST" action="{{ route('admin.mcu-work-calendar.closures.destroy', $closure) }}" class="d-inline" onsubmit="return confirm('Hapus libur tanggal ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
