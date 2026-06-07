@extends('layouts.sneat.auth')

@section('title', 'Lupa Password')
@section('heading', 'Lupa Password? 🔒')
@section('subheading', 'Masukkan email Anda, kami akan mengirim link reset password')

@section('content')
<form method="POST" action="{{ route('password.email') }}" class="mb-3">
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus />
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary d-grid w-100">Kirim Link Reset</button>
</form>
@endsection

@section('footer-links')
<div class="text-center">
    <a href="{{ route('login') }}" class="d-flex align-items-center justify-content-center">
        <i class="bx bx-chevron-left scaleX-n1-rtl bx-sm"></i>
        Kembali ke login
    </a>
</div>
@endsection
