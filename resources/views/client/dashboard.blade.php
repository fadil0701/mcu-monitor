@extends('layouts.sneat.app')

@section('title', 'Dashboard Peserta')
@section('breadcrumb', 'Portal Peserta')
@section('pageTitle', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <p class="text-muted mb-0">Selamat datang, <span class="fw-semibold text-body">{{ Auth::user()->name }}</span></p>
    <small class="text-muted">Terakhir diperbarui: {{ now()->format('d/m/Y H:i') }}</small>
</div>

<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <span class="avatar-initial rounded bg-label-primary mb-2 d-inline-flex p-2"><i class="bx bx-user-check"></i></span>
                <span class="d-block mb-1">Status Pendaftaran</span>
                <h3 class="card-title mb-0">{{ $participant ? 'Terdaftar' : 'Belum' }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <span class="avatar-initial rounded bg-label-warning mb-2 d-inline-flex p-2"><i class="bx bx-calendar"></i></span>
                <span class="d-block mb-1">Jadwal MCU</span>
                <h3 class="card-title mb-0">{{ $schedules->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <span class="avatar-initial rounded bg-label-success mb-2 d-inline-flex p-2"><i class="bx bx-file"></i></span>
                <span class="d-block mb-1">Hasil MCU</span>
                <h3 class="card-title mb-0">{{ $mcuResults->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <span class="avatar-initial rounded bg-label-info mb-2 d-inline-flex p-2"><i class="bx bx-time-five"></i></span>
                <span class="d-block mb-1">Antrian Aktif Hari Ini</span>
                <h3 class="card-title mb-0">{{ $todayQueueTotal ?? 0 }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <x-common.component-card title="Status Profile">
            @if($participant)
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-lg me-3">
                        <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(substr($participant->nama_lengkap, 0, 1)) }}</span>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ $participant->nama_lengkap }}</h6>
                        <small class="text-muted">{{ $participant->skpd }}</small>
                        <div class="mt-1 d-flex flex-wrap gap-1">
                            <span class="badge bg-label-{{ $participant->status_mcu_color }}">{{ $participant->status_mcu }}</span>
                            @include('partials.participant-ckg-status-badge', ['participant' => $participant])
                        </div>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6"><small class="text-muted d-block">NIK</small><span class="fw-medium">{{ $participant->nik_ktp }}</span></div>
                    <div class="col-6"><small class="text-muted d-block">No. Telp</small><span class="fw-medium">{{ $participant->no_telp }}</span></div>
                    <div class="col-12">
                        <small class="text-muted d-block">Status CKG Terakhir</small>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                            @include('partials.participant-ckg-status-badge', ['participant' => $participant])
                            @if($participant->ckgStatusHint())
                                <small class="text-muted">{{ $participant->ckgStatusHint() }}</small>
                            @endif
                        </div>
                    </div>
                </div>
                <a href="{{ route('client.profile') }}" class="btn btn-primary btn-sm w-100">
                    <i class="bx bx-edit me-1"></i> Perbarui Profile
                </a>
            @else
                <div class="text-center py-4">
                    <i class="bx bx-user-x bx-lg text-muted mb-2"></i>
                    <p class="text-muted mb-3">Data peserta belum terhubung dengan akun Anda.</p>
                    <a href="{{ route('client.profile') }}" class="btn btn-primary btn-sm">Lihat Profile</a>
                </div>
            @endif
        </x-common.component-card>
    </div>

    <div class="col-lg-6 mb-4">
        <x-common.component-card title="Aksi Cepat">
            <div class="row g-2">
                <div class="col-6"><a href="{{ route('client.schedules') }}" class="btn btn-outline-primary w-100"><i class="bx bx-calendar me-1"></i> Jadwal MCU</a></div>
                <div class="col-6"><a href="{{ route('client.results') }}" class="btn btn-outline-success w-100"><i class="bx bx-file me-1"></i> Hasil MCU</a></div>
                <div class="col-6"><a href="{{ route('client.schedule.request') }}" class="btn btn-outline-warning w-100"><i class="bx bx-revision me-1"></i> Daftar Ulang</a></div>
                <div class="col-6"><a href="{{ route('client.profile') }}" class="btn btn-outline-info w-100"><i class="bx bx-user me-1"></i> Profile</a></div>
            </div>
        </x-common.component-card>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <x-common.component-card title="Jadwal MCU Terdekat">
            @forelse($schedules->take(3) as $schedule)
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <span class="avatar-initial rounded bg-label-primary me-3 p-2"><i class="bx bx-calendar-check"></i></span>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">{{ $schedule->tanggal_pemeriksaan_formatted }}</h6>
                        <small class="text-muted">{{ $schedule->jam_pemeriksaan_formatted }} — {{ $schedule->lokasi_pemeriksaan }}</small>
                        <div class="mt-1"><span class="badge bg-label-{{ $schedule->status_color }}">{{ $schedule->status }}</span></div>
                    </div>
                </div>
            @empty
                <p class="text-muted text-center py-3 mb-0">Belum ada jadwal MCU.</p>
            @endforelse
            @if($schedules->count())
                <a href="{{ route('client.schedules') }}" class="btn btn-sm btn-outline-primary w-100">Lihat Semua Jadwal</a>
            @endif
        </x-common.component-card>
    </div>

    <div class="col-lg-6 mb-4">
        <x-common.component-card title="Hasil MCU Terbaru">
            @forelse($mcuResults->take(3) as $result)
                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                    <span class="avatar-initial rounded bg-label-success me-3 p-2"><i class="bx bx-file"></i></span>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">{{ $result->tanggal_pemeriksaan_formatted }}</h6>
                        <small class="text-muted">{{ $result->hasFile() ? 'Dokumen tersedia' : 'Belum ada dokumen' }}</small>
                    </div>
                </div>
            @empty
                <p class="text-muted text-center py-3 mb-0">Belum ada hasil MCU.</p>
            @endforelse
            @if($mcuResults->count())
                <a href="{{ route('client.results') }}" class="btn btn-sm btn-outline-success w-100">Lihat Semua Hasil</a>
            @endif
        </x-common.component-card>
    </div>
</div>
@endsection
