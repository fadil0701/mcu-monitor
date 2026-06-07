@extends('layouts.sneat.app')

@section('title', 'Dashboard MCU - PPKP DKI Jakarta')
@section('pageTitle', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h5 class="mb-1">Selamat Datang, {{ Auth::user()->name }}!</h5>
        <p class="text-muted mb-0">Dashboard monitoring MCU PPKP DKI Jakarta</p>
    </div>
    <small class="text-muted">Terakhir diperbarui: {{ now()->format('d/m/Y H:i') }}</small>
</div>

@php
    $myTodayQueues = $schedules->filter(fn($s) => $s->tanggal_pemeriksaan && $s->tanggal_pemeriksaan->isToday() && $s->status === 'Terjadwal' && !is_null($s->queue_number))->sortBy('queue_number');
    $myQueueNumber = optional($myTodayQueues->first())->queue_number;
@endphp

<div class="row mb-4">
    <div class="col-6 col-md-4 col-xl mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-user-check"></i></span>
                </div>
                <span class="d-block mb-1 text-muted">Status Pendaftaran</span>
                <h4 class="mb-0">{{ $participant ? '1' : '0' }}</h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-calendar"></i></span>
                </div>
                <span class="d-block mb-1 text-muted">Jadwal MCU</span>
                <h4 class="mb-0">{{ $schedules->count() }}</h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-success"><i class="bx bx-file"></i></span>
                </div>
                <span class="d-block mb-1 text-muted">Hasil MCU</span>
                <h4 class="mb-0">{{ $mcuResults->count() }}</h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-info"><i class="bx bx-time-five"></i></span>
                </div>
                <span class="d-block mb-1 text-muted">MCU Terakhir</span>
                <h6 class="mb-0">
                    @if($participant && $participant->tanggal_mcu_terakhir)
                        {{ \Carbon\Carbon::parse($participant->tanggal_mcu_terakhir)->diffForHumans() }}
                    @else
                        Belum MCU
                    @endif
                </h6>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-secondary"><i class="bx bx-group"></i></span>
                </div>
                <span class="d-block mb-1 text-muted">Antrian Hari Ini</span>
                <h4 class="mb-0">{{ $todayQueueTotal ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-danger"><i class="bx bx-block"></i></span>
                </div>
                <span class="d-block mb-1 text-muted">Jadwal Ditolak</span>
                <h4 class="mb-0">{{ $schedules->where('status', 'Ditolak')->count() }}</h4>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-hash"></i></span>
                </div>
                <span class="d-block mb-1 text-muted">No. Antrian Saya</span>
                <h4 class="mb-0">{{ $myQueueNumber ?? '-' }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="bx bx-user-circle me-2"></i>Status Profile</h5></div>
            <div class="card-body">
                @if($participant)
                    <div class="d-flex align-items-center mb-4">
                        <div class="avatar avatar-lg me-3">
                            <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(substr($participant->nama_lengkap, 0, 1)) }}</span>
                        </div>
                        <div>
                            <h6 class="mb-1">{{ $participant->nama_lengkap }}</h6>
                            <p class="text-muted mb-1">{{ $participant->skpd }}</p>
                            @php
                                $statusBadge = match($participant->status_mcu) {
                                    'Sudah MCU' => 'bg-label-success',
                                    'Ditolak' => 'bg-label-danger',
                                    default => 'bg-label-warning',
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ $participant->status_mcu }}</span>
                        </div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-6"><small class="text-muted d-block">NIK KTP</small><span class="fw-medium">{{ $participant->nik_ktp }}</span></div>
                        <div class="col-6"><small class="text-muted d-block">Status Pegawai</small><span class="fw-medium">{{ $participant->status_pegawai }}</span></div>
                        <div class="col-6"><small class="text-muted d-block">Umur</small><span class="fw-medium">{{ $participant->umur }} tahun</span></div>
                        <div class="col-6"><small class="text-muted d-block">Jenis Kelamin</small><span class="fw-medium">{{ $participant->jenis_kelamin_text }}</span></div>
                    </div>
                    <a href="{{ route('client.profile') }}" class="btn btn-primary w-100">Lihat Profile Lengkap</a>
                @else
                    <div class="text-center py-4">
                        <i class="bx bx-user-x bx-lg text-muted mb-3 d-block"></i>
                        <h6>Data Profile Belum Lengkap</h6>
                        <p class="text-muted mb-3">Silakan lengkapi data profile Anda untuk dapat mengakses fitur MCU.</p>
                        <a href="{{ route('client.profile') }}" class="btn btn-primary">Lengkapi Profile</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="bx bx-zap me-2"></i>Aksi Cepat</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-sm-4">
                        <a href="{{ route('client.schedules') }}" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="bx bx-calendar bx-md mb-2"></i><span class="small">Jadwal MCU</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="{{ route('client.results') }}" class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="bx bx-file bx-md mb-2"></i><span class="small">Hasil MCU</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="{{ route('client.schedule.request') }}" class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="bx bx-revision bx-md mb-2"></i><span class="small">Daftar Ulang MCU</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="{{ route('client.profile') }}" class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="bx bx-user bx-md mb-2"></i><span class="small">Profile</span>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="btn btn-outline-danger w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                            <i class="bx bx-log-out bx-md mb-2"></i><span class="small">Keluar</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="bx bx-calendar-event me-2"></i>Jadwal MCU Terdekat</h5></div>
            <div class="card-body">
                @if($schedules->count() > 0)
                    @foreach($schedules->take(3) as $schedule)
                        @php
                            $scheduleBadge = match($schedule->status ?? '') {
                                'Selesai' => 'bg-label-success',
                                'Batal', 'Ditolak' => 'bg-label-danger',
                                default => 'bg-label-warning',
                            };
                        @endphp
                        <div class="d-flex align-items-center gap-3 border rounded p-3 mb-3">
                            <span class="avatar"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-calendar"></i></span></span>
                            <div class="flex-grow-1 min-w-0">
                                <p class="fw-medium mb-1">{{ $schedule->tanggal_pemeriksaan_formatted ?? $schedule->tanggal_pemeriksaan?->format('d/m/Y') }}</p>
                                <p class="text-muted small mb-1">{{ $schedule->jam_pemeriksaan_formatted ?? $schedule->jam_pemeriksaan?->format('H:i') }} — {{ Str::limit($schedule->lokasi_pemeriksaan, 40) }}</p>
                                <span class="badge {{ $scheduleBadge }}">{{ $schedule->status }}</span>
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ route('client.schedules') }}" class="btn btn-outline-primary w-100">Lihat Semua Jadwal</a>
                @else
                    <div class="text-center py-4">
                        <i class="bx bx-calendar-x bx-lg text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">Belum ada jadwal MCU. Jadwal akan muncul setelah Anda didaftarkan oleh administrator.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0"><i class="bx bx-file-blank me-2"></i>Hasil MCU Terbaru</h5></div>
            <div class="card-body">
                @if($mcuResults->count() > 0)
                    @foreach($mcuResults->take(3) as $result)
                        @php $files = $result->file_hasil_files ?? ($result->file_hasil ? [$result->file_hasil] : []); $fileCount = count($files); @endphp
                        <div class="d-flex align-items-center gap-3 border rounded p-3 mb-3">
                            <span class="avatar"><span class="avatar-initial rounded bg-label-success"><i class="bx bx-file"></i></span></span>
                            <div class="flex-grow-1 min-w-0">
                                <p class="fw-medium mb-1">{{ $result->tanggal_pemeriksaan_formatted ?? $result->tanggal_pemeriksaan?->format('d/m/Y') }}</p>
                                <p class="text-muted small mb-1">{{ $result->hasFile() ? ($fileCount > 1 ? $fileCount . ' dokumen tersedia' : 'Dokumen tersedia') : 'Menunggu upload' }}</p>
                                @if($result->hasFile())
                                    <a href="{{ route('client.results.downloadAll', $result) }}" class="btn btn-sm btn-outline-success">Download</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ route('client.results') }}" class="btn btn-outline-success w-100">Lihat Semua Hasil</a>
                @else
                    <div class="text-center py-4">
                        <i class="bx bx-file bx-lg text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">Belum ada hasil MCU. Hasil akan muncul setelah pemeriksaan selesai dan diupload oleh administrator.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
