@extends('layouts.sneat.auth')

@section('title', 'Buat Akun Peserta')
@section('auth-inner-class', 'auth-form-wide')
@section('auth-max-width', '960px')
@section('heading', 'Buat Akun Login Peserta')
@section('subheading', 'Lengkapi data peserta dan buat akun login')

@section('content')
@php
    $nrkValue = old('nrk_pegawai', $participant->nrk_pegawai);
    if ($nrkValue === '-' || str_starts_with((string) $nrkValue, 'NRK-')) {
        $nrkValue = '';
    }
@endphp
<form method="POST" action="{{ url('/peserta/aktivasi-akun/register') }}">
    @csrf

    <p class="text-muted small mb-3">Periksa dan lengkapi data peserta MCU Anda. NIK tidak dapat diubah.</p>

    <div class="mb-3">
        <label class="form-label text-muted">NIK KTP</label>
        <input type="text" class="form-control" value="{{ $participant->nik_ktp }}" disabled />
    </div>

    <div class="mb-3">
        <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
        <input type="text" class="form-control @error('nama_lengkap') is-invalid @enderror" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $participant->nama_lengkap) }}" required />
        @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="tempat_lahir" class="form-label">Tempat Lahir *</label>
            <input type="text" class="form-control @error('tempat_lahir') is-invalid @enderror" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $participant->tempat_lahir === '-' ? '' : $participant->tempat_lahir) }}" required />
            @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="tanggal_lahir" class="form-label">Tanggal Lahir *</label>
            <input type="date" class="form-control @error('tanggal_lahir') is-invalid @enderror" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir', $participant->tanggal_lahir?->format('Y-m-d')) }}" required />
            @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="jenis_kelamin" class="form-label">Jenis Kelamin *</label>
            <select id="jenis_kelamin" name="jenis_kelamin" required class="form-select @error('jenis_kelamin') is-invalid @enderror">
                <option value="L" {{ old('jenis_kelamin', $participant->jenis_kelamin) === 'L' ? 'selected' : '' }}>Laki-laki</option>
                <option value="P" {{ old('jenis_kelamin', $participant->jenis_kelamin) === 'P' ? 'selected' : '' }}>Perempuan</option>
            </select>
            @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="status_pegawai" class="form-label">Status Pegawai *</label>
            <select id="status_pegawai" name="status_pegawai" required class="form-select @error('status_pegawai') is-invalid @enderror">
                @foreach(['PNS', 'CPNS', 'PPPK'] as $status)
                    <option value="{{ $status }}" {{ old('status_pegawai', $participant->status_pegawai) === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
            @error('status_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="mb-3">
        <label for="nrk_pegawai" class="form-label">NRK Pegawai <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('nrk_pegawai') is-invalid @enderror" id="nrk_pegawai" name="nrk_pegawai" value="{{ $nrkValue }}" required />
        @error('nrk_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="row g-3 mb-3 auth-form-field-row">
        <div class="col-md-6 d-flex">
            <x-instansi-pemprov-select
                class="flex-fill"
                :selected="old('skpd', $participant->skpd === '-' ? '' : $participant->skpd)"
                :required="true"
                :hasError="$errors->has('skpd')"
            />
        </div>
        <div class="col-md-6 d-flex">
            <div class="form-field-stack flex-fill">
                <label for="ukpd" class="form-label">UKPD <span class="text-danger">*</span></label>
                <div class="form-field-control">
                    <input type="text" class="form-control @error('ukpd') is-invalid @enderror" id="ukpd" name="ukpd" value="{{ old('ukpd', $participant->ukpd === '-' ? '' : $participant->ukpd) }}" required />
                    @error('ukpd')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label for="pendidikan_terakhir" class="form-label">Pendidikan Terakhir *</label>
            <x-participant.education-select
                :value="old('pendidikan_terakhir', $participant->pendidikan_terakhir)"
            />
            @error('pendidikan_terakhir')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="no_telp" class="form-label">No. Telepon *</label>
            <input type="text" class="form-control @error('no_telp') is-invalid @enderror" id="no_telp" name="no_telp" value="{{ old('no_telp', $participant->no_telp) }}" required />
            @error('no_telp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <hr class="my-4">

    <p class="text-muted small mb-3">Data akun login untuk portal peserta.</p>

    <div class="mb-3">
        <label for="email" class="form-label">Email Login *</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $participant->emailForForm()) }}" required autofocus placeholder="Email aktif" />
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3 form-password-toggle">
        <label for="password" class="form-label">Password *</label>
        <div class="input-group input-group-merge">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Minimal 8 karakter" />
            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3 form-password-toggle">
        <label for="password_confirmation" class="form-label">Konfirmasi Password *</label>
        <div class="input-group input-group-merge">
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required />
            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
        </div>
    </div>

    <button type="submit" class="btn btn-success d-grid w-100">Buat Akun Login</button>
</form>
@endsection

@section('footer-links')
<div class="text-center">
    <a href="{{ route('peserta.aktivasi') }}"><i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i> Kembali ke verifikasi</a>
</div>
@endsection
