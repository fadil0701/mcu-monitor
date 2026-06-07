@extends('layouts.sneat.auth')

@section('title', 'Login')
@section('heading', 'Selamat Datang! 👋')
@section('subheading', 'Silakan login untuk mengakses sistem monitoring MCU')

@section('content')
<form method="POST" action="{{ route('login') }}" class="mb-3">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus autocomplete="username" />
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3 form-password-toggle">
        <div class="d-flex justify-content-between">
            <label class="form-label" for="password">Password</label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"><small>Lupa password?</small></a>
            @endif
        </div>
        <div class="input-group input-group-merge">
            <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required autocomplete="current-password" aria-describedby="password" />
            <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
        </div>
        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }} />
            <label class="form-check-label" for="remember">Ingat saya</label>
        </div>
    </div>

    <button type="submit" class="btn btn-primary d-grid w-100 mb-3">Login</button>
</form>

<div class="d-grid gap-2">
    <a href="{{ route('peserta.aktivasi') }}" class="btn btn-outline-primary">Aktivasi Akun Peserta</a>
    <a href="{{ route('register') }}" class="btn btn-outline-success">Daftar MCU Baru</a>
</div>
@endsection

@section('footer-links')
<p class="text-center mb-0">
    <a href="{{ route('home') }}"><i class="bx bx-chevron-left scaleX-n1-rtl"></i> Kembali ke beranda</a>
</p>
@endsection
