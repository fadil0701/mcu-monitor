@extends('layouts.sneat.app')

@section('title', 'Profile Saya')
@section('breadcrumb', 'Portal Peserta')
@section('pageTitle', 'Profile Saya')

@section('content')
<div class="row">
    <div class="col-lg-8 mb-4">
        @if($participant)
            <x-common.component-card title="Perbarui Data Peserta">
                <form method="POST" action="{{ route('client.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">NIK KTP</label>
                            <input type="text" class="form-control" value="{{ $participant->nik_ktp }}" disabled>
                            <small class="text-muted">NIK tidak dapat diubah. Hubungi admin jika ada kesalahan.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="nrk_pegawai" class="form-label">NRK Pegawai *</label>
                            <input type="text" id="nrk_pegawai" name="nrk_pegawai" value="{{ old('nrk_pegawai', $participant->nrk_pegawai) }}" required class="form-control @error('nrk_pegawai') is-invalid @enderror">
                            @error('nrk_pegawai')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if(str_starts_with($participant->nrk_pegawai, 'NRK-'))
                                <small class="text-muted">NRK sementara dari sistem — silakan ganti dengan NRK resmi Anda.</small>
                            @endif
                        </div>
                        <div class="col-12">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" value="{{ old('nama_lengkap', $participant->nama_lengkap) }}" required class="form-control @error('nama_lengkap') is-invalid @enderror">
                            @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tempat_lahir" class="form-label">Tempat Lahir *</label>
                            <input type="text" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $participant->tempat_lahir === '-' ? '' : $participant->tempat_lahir) }}" required class="form-control @error('tempat_lahir') is-invalid @enderror">
                            @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_lahir" class="form-label">Tanggal Lahir *</label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="{{ old('tanggal_lahir', $participant->tanggal_lahir?->format('Y-m-d')) }}" required class="form-control @error('tanggal_lahir') is-invalid @enderror">
                            @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
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
                        <div class="col-md-6">
                            <label for="pendidikan_terakhir" class="form-label">Pendidikan Terakhir *</label>
                            <x-participant.education-select
                                :value="old('pendidikan_terakhir', $participant->pendidikan_terakhir)"
                                class="@error('pendidikan_terakhir') is-invalid @enderror"
                            />
                            @error('pendidikan_terakhir')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="skpd" class="form-label">SKPD *</label>
                            <input type="text" id="skpd" name="skpd" value="{{ old('skpd', $participant->skpd === '-' ? '' : $participant->skpd) }}" required class="form-control @error('skpd') is-invalid @enderror">
                            @error('skpd')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="ukpd" class="form-label">UKPD *</label>
                            <input type="text" id="ukpd" name="ukpd" value="{{ old('ukpd', $participant->ukpd === '-' ? '' : $participant->ukpd) }}" required class="form-control @error('ukpd') is-invalid @enderror">
                            @error('ukpd')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="no_telp" class="form-label">No. Telepon *</label>
                            <input type="text" id="no_telp" name="no_telp" value="{{ old('no_telp', $participant->no_telp) }}" required class="form-control @error('no_telp') is-invalid @enderror">
                            @error('no_telp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $participant->emailForForm() ?: $user->email) }}" required class="form-control @error('email') is-invalid @enderror">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </x-common.component-card>

            <x-common.component-card title="Status MCU" class="mt-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center h-100">
                            <small class="text-muted d-block mb-1">Status MCU</small>
                            <span class="badge bg-label-{{ $participant->status_mcu_color }}">{{ $participant->status_mcu }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center h-100">
                            <small class="text-muted d-block mb-1">MCU Terakhir</small>
                            <span class="fw-medium">{{ $participant->tanggal_mcu_terakhir ? $participant->tanggal_mcu_terakhir_formatted : 'Belum pernah MCU' }}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 text-center h-100">
                            <small class="text-muted d-block mb-1">Kategori Umur</small>
                            <span class="fw-medium">{{ $participant->kategori_umur }}</span>
                            <small class="text-muted d-block">({{ $participant->umur }} tahun)</small>
                        </div>
                    </div>
                </div>
                @if($participant->catatan)
                    <div class="alert alert-info mt-3 mb-0">{{ $participant->catatan }}</div>
                @endif
            </x-common.component-card>
        @else
            <x-common.component-card title="Data Peserta">
                <div class="text-center py-5">
                    <i class="bx bx-user-x bx-lg text-muted mb-2"></i>
                    <h5>Data Peserta Tidak Ditemukan</h5>
                    <p class="text-muted mb-0">Data peserta MCU belum terdaftar. Silakan hubungi administrator.</p>
                </div>
            </x-common.component-card>
        @endif
    </div>

    <div class="col-lg-4 mb-4">
        <x-common.component-card title="Informasi Akun">
            <dl class="row mb-0">
                <dt class="col-sm-4">Nama</dt>
                <dd class="col-sm-8">{{ $user->name }}</dd>
                <dt class="col-sm-4">Email</dt>
                <dd class="col-sm-8">{{ $user->email }}</dd>
                <dt class="col-sm-4">Role</dt>
                <dd class="col-sm-8">{{ $user->role_label }}</dd>
                <dt class="col-sm-4">Status</dt>
                <dd class="col-sm-8">
                    <span class="badge bg-label-{{ $user->is_active ? 'success' : 'danger' }}">
                        {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </dd>
            </dl>
        </x-common.component-card>
    </div>
</div>
@endsection
