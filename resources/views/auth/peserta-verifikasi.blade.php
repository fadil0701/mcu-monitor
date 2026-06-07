@extends('layouts.sneat.auth')

@section('title', 'Aktivasi Akun Peserta')
@section('heading', 'Aktivasi Akun Peserta')
@section('subheading', 'Masukkan NIK, NRK, atau nomor telepon Anda')

@section('content')
<form method="POST" action="{{ url('/peserta/aktivasi-akun') }}">
    @csrf
    <div class="mb-3">
        <label for="identifier" class="form-label">NIK / NRK / No Telp</label>
        <input type="text" class="form-control @error('identifier') is-invalid @enderror" id="identifier" name="identifier" value="{{ old('identifier') }}" required autofocus placeholder="Masukkan NIK, NRK, atau No Telp" />
        @error('identifier')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary d-grid w-100">Verifikasi Peserta</button>
</form>
@endsection

@section('footer-links')
<div class="text-center">
    <a href="{{ route('login') }}"><i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i> Kembali ke login</a>
</div>
@endsection
