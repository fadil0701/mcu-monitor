@extends('layouts.sneat.app')

@section('title', 'Tambah Jadwal MCU')

@section('pageTitle', 'Tambah Jadwal MCU')

@section('content')

<x-common.component-card title="Form Jadwal MCU">
    <form method="POST" action="{{ route('admin.schedules.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-12">
                <x-form.searchable-select
                    name="participant_id"
                    label="Peserta *"
                    :options="$participants"
                    value-key="id"
                    label-key="nama_lengkap"
                    sublabel-key="nik_ktp"
                    placeholder="-- Pilih Peserta --"
                    :value="old('participant_id', $participantId ?? '')"
                    :required="true"
                />
            </div>
            <div class="col-md-6">
                <label for="tanggal_pemeriksaan" class="form-label">Tanggal Pemeriksaan *</label>
                <input type="date" id="tanggal_pemeriksaan" name="tanggal_pemeriksaan" value="{{ old('tanggal_pemeriksaan') }}" min="{{ now()->toDateString() }}" onclick="this.showPicker?.()" required class="form-control @error('tanggal_pemeriksaan') is-invalid @enderror">
                @error('tanggal_pemeriksaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="jam_pemeriksaan" class="form-label">Jam Pemeriksaan *</label>
                <input type="time" id="jam_pemeriksaan" name="jam_pemeriksaan" value="{{ old('jam_pemeriksaan') }}" required class="form-control @error('jam_pemeriksaan') is-invalid @enderror">
                @error('jam_pemeriksaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="lokasi_pemeriksaan" class="form-label">Lokasi Pemeriksaan *</label>
                <input type="text" id="lokasi_pemeriksaan" name="lokasi_pemeriksaan" value="{{ old('lokasi_pemeriksaan', config('mcu.default_location')) }}" required class="form-control @error('lokasi_pemeriksaan') is-invalid @enderror" placeholder="Klinik Utama Balaikota">
                @error('lokasi_pemeriksaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="catatan" class="form-label">Catatan</label>
                <textarea id="catatan" name="catatan" rows="2" class="form-control @error('catatan') is-invalid @enderror">{{ old('catatan') }}</textarea>
                @error('catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <x-admin.form-actions :cancelUrl="route('admin.schedules.index')" />
    </form>
</x-common.component-card>
@endsection
