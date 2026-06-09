@extends('layouts.sneat.auth')

@section('title', 'Pendaftaran MCU')
@section('auth-max-width', '960px')
@section('heading', 'Pendaftaran Medical Check Up')
@section('subheading', 'PPKP DKI Jakarta — lengkapi data akun dan data pribadi')

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf

    <h6 class="text-primary mb-3"><i class="bx bx-user-circle me-1"></i> Informasi Akun</h6>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required />
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required />
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3 form-password-toggle">
            <label for="password" class="form-label">Password</label>
            <div class="input-group input-group-merge">
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
            </div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3 form-password-toggle">
            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
            <div class="input-group input-group-merge">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
            </div>
        </div>
    </div>

    <hr class="my-4">
    <h6 class="text-success mb-3"><i class="bx bx-id-card me-1"></i> Informasi Pribadi</h6>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nik_ktp" class="form-label">NIK KTP</label>
            <input type="text" class="form-control @error('nik_ktp') is-invalid @enderror" id="nik_ktp" name="nik_ktp" value="{{ old('nik_ktp') }}" maxlength="16" placeholder="16 digit" />
            @error('nik_ktp')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label for="nrk_pegawai" class="form-label">NRK Pegawai</label>
            <input type="text" class="form-control @error('nrk_pegawai') is-invalid @enderror" id="nrk_pegawai" name="nrk_pegawai" value="{{ old('nrk_pegawai') }}" />
            @error('nrk_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
            <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir') }}" />
        </div>
        <div class="col-md-6 mb-3">
            <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
            <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" />
        </div>
        <div class="col-md-6 mb-3">
            <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
            <select class="form-select" id="jenis_kelamin" name="jenis_kelamin">
                <option value="">Pilih</option>
                <option value="L" {{ old('jenis_kelamin') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                <option value="P" {{ old('jenis_kelamin') == 'P' ? 'selected' : '' }}>Perempuan</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="status_pegawai" class="form-label">Status Pegawai <span class="text-danger">*</span></label>
            <select class="form-select @error('status_pegawai') is-invalid @enderror" id="status_pegawai" name="status_pegawai" required>
                <option value="">Pilih Status</option>
                <option value="CPNS" {{ old('status_pegawai') == 'CPNS' ? 'selected' : '' }}>CPNS</option>
                <option value="PNS" {{ old('status_pegawai') == 'PNS' ? 'selected' : '' }}>PNS</option>
                <option value="PPPK" {{ old('status_pegawai') == 'PPPK' ? 'selected' : '' }}>PPPK</option>
            </select>
            @error('status_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <x-instansi-pemprov-select
                :selected="old('skpd')"
                :hasError="$errors->has('skpd')"
            />
        </div>
        <div class="col-md-6 mb-3">
            <label for="ukpd" class="form-label">UKPD</label>
            <input type="text" class="form-control" id="ukpd" name="ukpd" value="{{ old('ukpd') }}" />
        </div>
        <div class="col-md-6 mb-3">
            <label for="pendidikan_terakhir" class="form-label">Pendidikan Terakhir <span class="text-danger">*</span></label>
            <x-participant.education-select
                :value="old('pendidikan_terakhir')"
            />
            @error('pendidikan_terakhir')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label for="no_telp" class="form-label">No. Telepon</label>
            <input type="tel" class="form-control" id="no_telp" name="no_telp" value="{{ old('no_telp') }}" placeholder="08xxxxxxxxxx" />
        </div>
        <div class="col-md-6 mb-3">
            <label for="email_personal" class="form-label">Email Pribadi</label>
            <input type="email" class="form-control" id="email_personal" name="email_personal" value="{{ old('email_personal') }}" />
        </div>
    </div>

    <div class="alert alert-info mb-4">
        <small>Pendaftaran MCU untuk pegawai CPNS/PNS/PPPK. MCU hanya dapat dilakukan setiap {{ config('mcu.interval_years', 3) }} tahun sekali.</small>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <a href="{{ route('login') }}" class="btn btn-outline-secondary">Kembali ke Login</a>
        <button type="submit" class="btn btn-success">Daftar MCU</button>
    </div>
</form>
@endsection

@section('footer-links')
<p class="text-center mb-0">
    Sudah punya akun? <a href="{{ route('login') }}">Login di sini</a>
</p>
@endsection
