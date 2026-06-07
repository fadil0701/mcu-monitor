@extends('layouts.sneat.app')

@section('title', 'Profile')
@section('pageTitle', 'Profile')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Informasi Profile</h5></div>
            <div class="card-body">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Ubah Password</h5></div>
            <div class="card-body">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0 text-danger">Hapus Akun</h5></div>
            <div class="card-body">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
