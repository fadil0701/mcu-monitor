@extends('layouts.sneat.app')

@section('title', 'Edit Peserta')

@section('pageTitle', 'Edit Peserta')

@section('content')

<x-common.component-card title="Form Peserta">
    <form method="POST" action="{{ route('admin.participants.update', $participant) }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nik_ktp" class="form-label">NIK KTP *</label>
                <input type="text" id="nik_ktp" name="nik_ktp" value="{{ old('nik_ktp', $participant->nik_ktp) }}" maxlength="16" required class="form-control @error('nik_ktp') is-invalid @enderror">
                @error('nik_ktp')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="nrk_pegawai" class="form-label">NRK Pegawai *</label>
                <input type="text" id="nrk_pegawai" name="nrk_pegawai" value="{{ old('nrk_pegawai', $participant->nrk_pegawai) }}" required class="form-control @error('nrk_pegawai') is-invalid @enderror">
                @error('nrk_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $participant->nama_lengkap) }}" required class="form-control @error('nama_lengkap') is-invalid @enderror">
                @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="tempat_lahir" class="form-label">Tempat Lahir *</label>
                <input type="text" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $participant->tempat_lahir) }}" required class="form-control @error('tempat_lahir') is-invalid @enderror">
                @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="tanggal_lahir" class="form-label">Tanggal Lahir *</label>
                <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir', $participant->tanggal_lahir?->format('Y-m-d')) }}" required class="form-control @error('tanggal_lahir') is-invalid @enderror">
                @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="jenis_kelamin" class="form-label">Jenis Kelamin *</label>
                <select id="jenis_kelamin" name="jenis_kelamin" required class="form-select @error('jenis_kelamin') is-invalid @enderror">
                    <option value="L" {{ old('jenis_kelamin', $participant->jenis_kelamin) === 'L' ? 'selected' : '' }}>Laki-laki</option>
                    <option value="P" {{ old('jenis_kelamin', $participant->jenis_kelamin) === 'P' ? 'selected' : '' }}>Perempuan</option>
                </select>
                @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="skpd" class="form-label">SKPD *</label>
                <input type="text" id="skpd" name="skpd" value="{{ old('skpd', $participant->skpd) }}" required class="form-control @error('skpd') is-invalid @enderror">
                @error('skpd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="ukpd" class="form-label">UKPD *</label>
                <input type="text" id="ukpd" name="ukpd" value="{{ old('ukpd', $participant->ukpd) }}" required class="form-control @error('ukpd') is-invalid @enderror">
                @error('ukpd')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="status_pegawai" class="form-label">Status Pegawai *</label>
                <select id="status_pegawai" name="status_pegawai" required class="form-select @error('status_pegawai') is-invalid @enderror">
                    <option value="PNS" {{ old('status_pegawai', $participant->status_pegawai) === 'PNS' ? 'selected' : '' }}>PNS</option>
                    <option value="CPNS" {{ old('status_pegawai', $participant->status_pegawai) === 'CPNS' ? 'selected' : '' }}>CPNS</option>
                    <option value="PPPK" {{ old('status_pegawai', $participant->status_pegawai) === 'PPPK' ? 'selected' : '' }}>PPPK</option>
                </select>
                @error('status_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="no_telp" class="form-label">No. Telepon *</label>
                <input type="text" id="no_telp" name="no_telp" value="{{ old('no_telp', $participant->no_telp) }}" required class="form-control @error('no_telp') is-invalid @enderror">
                @error('no_telp')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $participant->email) }}" required class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="status_mcu" class="form-label">Status MCU</label>
                <select id="status_mcu" name="status_mcu" class="form-select @error('status_mcu') is-invalid @enderror">
                    <option value="Belum MCU" {{ old('status_mcu', $participant->status_mcu) === 'Belum MCU' ? 'selected' : '' }}>Belum MCU</option>
                    <option value="Sudah MCU" {{ old('status_mcu', $participant->status_mcu) === 'Sudah MCU' ? 'selected' : '' }}>Sudah MCU</option>
                    <option value="Ditolak" {{ old('status_mcu', $participant->status_mcu) === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
                @error('status_mcu')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="tanggal_mcu_terakhir" class="form-label">Tanggal MCU Terakhir</label>
                <input type="date" id="tanggal_mcu_terakhir" name="tanggal_mcu_terakhir" value="{{ old('tanggal_mcu_terakhir', $participant->tanggal_mcu_terakhir?->format('Y-m-d')) }}" class="form-control @error('tanggal_mcu_terakhir') is-invalid @enderror">
                @error('tanggal_mcu_terakhir')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="catatan" class="form-label">Catatan</label>
                <textarea id="catatan" name="catatan" rows="3" class="form-control @error('catatan') is-invalid @enderror">{{ old('catatan', $participant->catatan) }}</textarea>
                @error('catatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <x-admin.form-actions :cancelUrl="route('admin.participants.index')" />
    </form>
</x-common.component-card>
@endsection
