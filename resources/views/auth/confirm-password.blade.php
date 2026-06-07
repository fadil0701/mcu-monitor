@extends('layouts.sneat.auth')

@section('title', 'Konfirmasi Password')
@section('heading', 'Konfirmasi Password 🔐')
@section('subheading', 'Area aman. Masukkan password untuk melanjutkan')

@section('content')
<form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    <div class="mb-3 form-password-toggle">
        <label class="form-label" for="password">Password</label>
        <div class="input-group input-group-merge">
            <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" />
            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary d-grid w-100">Konfirmasi</button>
</form>
@endsection
