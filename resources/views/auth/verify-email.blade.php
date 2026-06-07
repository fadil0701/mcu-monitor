@extends('layouts.sneat.auth')

@section('title', 'Verifikasi Email')
@section('heading', 'Verifikasi Email ✉️')
@section('subheading', 'Silakan verifikasi alamat email Anda dengan mengklik link yang kami kirim')

@section('content')
@if (session('status') == 'verification-link-sent')
    <div class="alert alert-success mb-3">
        Link verifikasi baru telah dikirim ke email Anda.
    </div>
@endif

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary">Kirim Ulang Email Verifikasi</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary">Logout</button>
    </form>
</div>
@endsection
