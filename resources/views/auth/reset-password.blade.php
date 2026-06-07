@extends('layouts.sneat.auth')

@section('title', 'Reset Password')
@section('heading', 'Reset Password 🔑')
@section('subheading', 'Masukkan password baru untuk akun Anda')

@section('content')
<form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" />
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3 form-password-toggle">
        <label class="form-label" for="password">Password Baru</label>
        <div class="input-group input-group-merge">
            <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" />
            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3 form-password-toggle">
        <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
        <div class="input-group input-group-merge">
            <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" required autocomplete="new-password" />
            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
        </div>
    </div>

    <button type="submit" class="btn btn-primary d-grid w-100">Reset Password</button>
</form>
@endsection

@section('footer-links')
<div class="text-center">
    <a href="{{ route('login') }}"><i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i> Kembali ke login</a>
</div>
@endsection
