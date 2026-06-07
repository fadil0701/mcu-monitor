@extends('layouts.sneat.app')

@section('title', 'Daftar Ulang MCU')
@section('breadcrumb', 'Portal Peserta')
@section('pageTitle', 'Pendaftaran Ulang MCU')

@section('content')
<div class="row">
    <div class="col-lg-8 mb-4">
        <x-common.component-card title="Form Permintaan Jadwal">
            @unless($eligible)
                <div class="alert alert-warning">
                    <i class="bx bx-error me-1"></i>{{ $reason }}
                </div>
            @endunless

            <form method="POST" action="{{ route('client.schedule.request.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Pemeriksaan *</label>
                        <input type="date" name="tanggal_pemeriksaan" class="form-control @error('tanggal_pemeriksaan') is-invalid @enderror" value="{{ old('tanggal_pemeriksaan') }}" {{ $eligible ? '' : 'disabled' }} required>
                        @error('tanggal_pemeriksaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jam Pemeriksaan *</label>
                        <input type="time" name="jam_pemeriksaan" class="form-control @error('jam_pemeriksaan') is-invalid @enderror" value="{{ old('jam_pemeriksaan') }}" {{ $eligible ? '' : 'disabled' }} required>
                        @error('jam_pemeriksaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Lokasi Pemeriksaan *</label>
                        <input type="text" name="lokasi_pemeriksaan" class="form-control @error('lokasi_pemeriksaan') is-invalid @enderror" value="{{ old('lokasi_pemeriksaan', config('mcu.default_location')) }}" {{ $eligible ? '' : 'disabled' }} required>
                        @error('lokasi_pemeriksaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="catatan" class="form-control" rows="3" {{ $eligible ? '' : 'disabled' }}>{{ old('catatan') }}</textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" {{ $eligible ? '' : 'disabled' }}>
                        <i class="bx bx-send me-1"></i> Ajukan Jadwal
                    </button>
                    <a href="{{ route('client.schedules') }}" class="btn btn-outline-secondary ms-2">Kembali</a>
                </div>
            </form>
        </x-common.component-card>
    </div>

    <div class="col-lg-4 mb-4">
        <x-common.component-card title="Status Kelayakan">
            <dl class="row mb-0">
                <dt class="col-sm-5">Nama</dt>
                <dd class="col-sm-7">{{ $participant->nama_lengkap }}</dd>
                <dt class="col-sm-5">NIK</dt>
                <dd class="col-sm-7">{{ $participant->nik_ktp }}</dd>
                <dt class="col-sm-5">SKPD</dt>
                <dd class="col-sm-7">{{ $participant->skpd }}</dd>
                <dt class="col-sm-5">MCU Terakhir</dt>
                <dd class="col-sm-7">{{ $participant->tanggal_mcu_terakhir_formatted }}</dd>
                <dt class="col-sm-5">Kelayakan</dt>
                <dd class="col-sm-7">
                    <span class="badge bg-label-{{ $eligible ? 'success' : 'warning' }}">
                        {{ $eligible ? 'Memenuhi syarat' : 'Belum memenuhi' }}
                    </span>
                </dd>
            </dl>
        </x-common.component-card>
    </div>
</div>
@endsection
