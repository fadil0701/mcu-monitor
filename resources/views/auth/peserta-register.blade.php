@extends('layouts.sneat.auth')

@section('title', 'Buat Akun Peserta')
@section('heading', 'Buat Akun Login Peserta')
@section('subheading', 'Lengkapi data berikut untuk membuat akun')

@section('content')
<div class="mb-3">
    <label class="form-label text-muted">Nama Lengkap</label>
    <input type="text" class="form-control" value="{{ $participant->nama_lengkap }}" disabled />
</div>
<div class="mb-3">
    <label class="form-label text-muted">NIK</label>
    <input type="text" class="form-control" value="{{ $participant->nik_ktp }}" disabled />
</div>

<form method="POST" action="{{ url('/peserta/aktivasi-akun/register') }}">
    @csrf
    <div class="mb-3">
        <label for="nrk_pegawai" class="form-label">NRK Pegawai *</label>
        <input type="text" class="form-control @error('nrk_pegawai') is-invalid @enderror" id="nrk_pegawai" name="nrk_pegawai" value="{{ old('nrk_pegawai', $participant->nrk_pegawai) }}" required />
        @error('nrk_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if(str_starts_with($participant->nrk_pegawai, 'NRK-'))
            <small class="text-muted">NRK sementara dari sistem — silakan isi NRK resmi Anda.</small>
        @endif
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="Email aktif" />
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3 form-password-toggle">
        <label for="password" class="form-label">Password</label>
        <div class="input-group input-group-merge">
            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Minimal 8 karakter" />
            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3 form-password-toggle">
        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
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
