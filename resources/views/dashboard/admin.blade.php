@extends('layouts.sneat.app')

@section('title', 'Dashboard MCU')
@section('pageTitle', 'Dashboard')

@section('content')
@php
    $intervalYears = $stats->interval_years ?? config('mcu.interval_years', 3);
    $intervalCutoff = isset($stats->interval_cutoff)
        ? \Carbon\Carbon::parse($stats->interval_cutoff)->format('d/m/Y')
        : now()->subYears($intervalYears)->format('d/m/Y');
    $mcuSudahInterval = (int) ($stats->mcu_sudah_interval ?? 0);
    $mcuBelumInterval = (int) ($stats->mcu_belum_interval ?? 0);
    $intervalTotal = max(1, $mcuSudahInterval + $mcuBelumInterval);
    $intervalPercentSudah = round(($mcuSudahInterval / $intervalTotal) * 100, 1);
    $canReschedule = Auth::user()->canManageReschedule();
    $todayLabel = now()->translatedFormat('l, d F Y');
    $quotaBooked = (int) ($quotaToday['booked'] ?? 0);
    $quotaLimit = (int) ($quotaToday['limit'] ?? 0);
    $quotaRemaining = $quotaToday['remaining'] ?? null;
    $quotaPercent = (!$quotaToday['unlimited'] && $quotaLimit > 0)
        ? min(100, round(($quotaBooked / $quotaLimit) * 100, 1))
        : 0;
@endphp

{{-- Ringkasan cepat --}}
<div class="card dashboard-hero mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h5 class="mb-1">Selamat datang, {{ Auth::user()->name }}</h5>
                <p class="text-muted mb-2">{{ $todayLabel }}</p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="dashboard-stat-pill">
                        <i class="bx bx-group me-1"></i>{{ number_format($stats->total_participants ?? 0) }} peserta terdaftar
                    </span>
                    <span class="dashboard-stat-pill dashboard-stat-pill--primary">
                        <i class="bx bx-calendar-event me-1"></i>{{ $todayOperational->total ?? 0 }} jadwal hari ini
                    </span>
                    <span class="dashboard-stat-pill dashboard-stat-pill--success">
                        <i class="bx bx-check-circle me-1"></i>{{ $todayOperational->selesai ?? 0 }} selesai hari ini
                    </span>
                    @if(!$quotaToday['unlimited'])
                        <span class="dashboard-stat-pill dashboard-stat-pill--warning">
                            <i class="bx bx-bar-chart-alt-2 me-1"></i>Kuota: {{ $quotaBooked }}/{{ $quotaLimit }}
                            @if($quotaRemaining !== null)
                                (sisa {{ $quotaRemaining }})
                            @endif
                        </span>
                    @endif
                </div>
            </div>
            <div class="text-md-end">
                <small class="text-muted d-block mb-2">Diperbarui {{ now()->format('H:i') }} WIB</small>
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <a href="{{ route('admin.participants.index') }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-group me-1"></i>Data Peserta</a>
                    <a href="{{ route('admin.schedules.index', ['date' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-calendar me-1"></i>Jadwal Hari Ini</a>
                    <a href="{{ route('admin.mcu-results.index') }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-file me-1"></i>Hasil MCU</a>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bx bx-download me-1"></i>Laporan</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- KPI utama --}}
<div class="row dashboard-kpi mb-4">
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100 dashboard-kpi-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-primary"><i class="bx bx-group"></i></span></span>
                    <span class="badge bg-label-primary">+{{ $monthStats->new_participants ?? 0 }} bulan ini</span>
                </div>
                <span class="dashboard-kpi-label">Total Peserta</span>
                <h3 class="dashboard-kpi-value mb-2">{{ number_format($stats->total_participants ?? 0) }}</h3>
                <small class="text-muted">Seluruh data peserta aktif di sistem</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100 dashboard-kpi-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-warning"><i class="bx bx-calendar"></i></span></span>
                    <span class="badge bg-label-warning">{{ $todayOperational->upcoming_week ?? 0 }} 7 hari</span>
                </div>
                <span class="dashboard-kpi-label">Peserta Terjadwal</span>
                <h3 class="dashboard-kpi-value mb-2">{{ number_format($stats->scheduled_participants ?? 0) }}</h3>
                <small class="text-muted">Jadwal aktif mulai hari ini ke depan</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100 dashboard-kpi-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-success"><i class="bx bx-check-circle"></i></span></span>
                    <span class="badge bg-label-success">{{ $percentages->sudah_mcu }}%</span>
                </div>
                <span class="dashboard-kpi-label">Sudah MCU</span>
                <h3 class="dashboard-kpi-value mb-1">{{ number_format($stats->sudah_mcu_status ?? 0) }}</h3>
                <div class="progress dashboard-progress-mini mb-1">
                    <div class="progress-bar bg-success" style="width: {{ $percentages->sudah_mcu }}%"></div>
                </div>
                <small class="text-muted">{{ $monthStats->mcu_results ?? 0 }} hasil diupload bulan ini</small>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100 dashboard-kpi-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="avatar avatar-sm"><span class="avatar-initial rounded bg-label-info"><i class="bx bx-time-five"></i></span></span>
                    <span class="badge bg-label-info">{{ $percentages->belum_mcu }}%</span>
                </div>
                <span class="dashboard-kpi-label">Belum MCU</span>
                <h3 class="dashboard-kpi-value mb-1">{{ number_format($stats->belum_mcu_status ?? 0) }}</h3>
                <div class="progress dashboard-progress-mini mb-1">
                    <div class="progress-bar bg-info" style="width: {{ $percentages->belum_mcu }}%"></div>
                </div>
                <small class="text-muted">{{ $stats->ditolak_mcu_status ?? 0 }} ditolak · {{ $mcuResultSummary->belum_upload ?? 0 }} belum upload hasil</small>
            </div>
        </div>
    </div>
</div>

{{-- Operasional hari ini --}}
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0"><i class="bx bx-pulse me-1"></i>Operasional Hari Ini</h5>
        <a href="{{ route('admin.schedules.index', ['date' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-primary">Kelola jadwal</a>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4 col-lg-3">
                <div class="dashboard-mini-stat h-100">
                    <span class="dashboard-mini-stat__label">Kuota MCU</span>
                    @if($quotaToday['unlimited'])
                        <h4 class="mb-1">Tidak terbatas</h4>
                        <small class="text-muted">{{ $quotaBooked }} terjadwal/selesai hari ini</small>
                    @else
                        <h4 class="mb-1">{{ $quotaBooked }} <span class="fs-6 text-muted">/ {{ $quotaLimit }}</span></h4>
                        <div class="progress dashboard-progress-mini mb-1">
                            <div class="progress-bar {{ $quotaPercent >= 90 ? 'bg-danger' : ($quotaPercent >= 70 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ $quotaPercent }}%"></div>
                        </div>
                        <small class="text-muted">Sisa {{ $quotaRemaining ?? 0 }} slot · {{ $quotaPercent }}% terpakai</small>
                    @endif
                </div>
            </div>
            <div class="col-md-8 col-lg-9">
                <div class="row g-2">
                    @foreach([
                        ['label' => 'Terjadwal', 'value' => $todayOperational->terjadwal ?? 0, 'class' => 'warning', 'icon' => 'bx-time'],
                        ['label' => 'Selesai', 'value' => $todayOperational->selesai ?? 0, 'class' => 'success', 'icon' => 'bx-check'],
                        ['label' => 'Konfirmasi Hadir', 'value' => $todayOperational->confirmed ?? 0, 'class' => 'primary', 'icon' => 'bx-user-check'],
                        ['label' => 'Belum Konfirmasi', 'value' => $todayOperational->belum_konfirmasi ?? 0, 'class' => 'secondary', 'icon' => 'bx-user-x'],
                        ['label' => 'Batal', 'value' => $todayOperational->batal ?? 0, 'class' => 'danger', 'icon' => 'bx-x'],
                        ['label' => 'Ditolak', 'value' => $todayOperational->ditolak ?? 0, 'class' => 'dark', 'icon' => 'bx-block'],
                    ] as $item)
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="dashboard-status-chip dashboard-status-chip--{{ $item['class'] }}">
                                <i class="bx {{ $item['icon'] }}"></i>
                                <div>
                                    <strong>{{ $item['value'] }}</strong>
                                    <span>{{ $item['label'] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="#antrian-hari-ini" class="dashboard-link-card text-body text-decoration-none">
                    <i class="bx bx-list-ul text-primary"></i>
                    <div>
                        <strong>{{ $todayQueue->count() }}</strong>
                        <span>Antrian ditampilkan</span>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="#konfirmasi-hadir" class="dashboard-link-card text-body text-decoration-none">
                    <i class="bx bx-user-check text-success"></i>
                    <div>
                        <strong>{{ $confirmedTodayCount ?? 0 }}</strong>
                        <span>Siap diselesaikan</span>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ $canReschedule ? route('admin.reschedule-center.index') : '#' }}" class="dashboard-link-card text-body text-decoration-none {{ $canReschedule ? '' : 'pe-none opacity-75' }}">
                    <i class="bx bx-calendar-edit text-warning"></i>
                    <div>
                        <strong>{{ $todayOperational->reschedule_pending ?? $pendingRescheduleToday ?? 0 }}</strong>
                        <span>Permintaan reschedule</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Hasil MCU, CKG, interval --}}
<div class="row mb-4">
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Hasil MCU</h5></div>
            <div class="card-body">
                <div class="dashboard-metric-row">
                    <span>Total diupload</span>
                    <strong>{{ number_format($mcuResultSummary->total_results ?? 0) }}</strong>
                </div>
                <div class="dashboard-metric-row">
                    <span>Sudah dipublikasi</span>
                    <strong class="text-success">{{ number_format($mcuResultSummary->published_count ?? 0) }}</strong>
                </div>
                <div class="dashboard-metric-row">
                    <span>Belum dipublikasi</span>
                    <strong class="text-warning">{{ number_format($mcuResultSummary->unpublished_count ?? 0) }}</strong>
                </div>
                <div class="dashboard-metric-row border-0 pb-0">
                    <span>Belum upload (Sudah MCU)</span>
                    <strong class="text-danger">{{ number_format($mcuResultSummary->belum_upload ?? 0) }}</strong>
                </div>
                <a href="{{ route('admin.mcu-results.index') }}" class="btn btn-sm btn-outline-primary w-100 mt-3">Kelola hasil MCU</a>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Skrining CKG</h5></div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width: 120px; height: 120px; flex-shrink: 0;"><canvas id="ckgChart"></canvas></div>
                    <div>
                        <h4 class="mb-1">{{ $percentages->ckg }}%</h4>
                        <p class="text-muted mb-2 small">Peserta sudah skrining CKG di PPKP</p>
                        <div class="dashboard-metric-row py-1 border-0">
                            <span>Sudah</span><strong class="text-success">{{ number_format($ckgSummary->completed ?? 0) }}</strong>
                        </div>
                        <div class="dashboard-metric-row py-1 border-0">
                            <span>Belum</span><strong class="text-warning">{{ number_format($ckgSummary->belum ?? 0) }}</strong>
                        </div>
                    </div>
                </div>
                <small class="text-muted">Peserta dengan CKG selesai (tersinkron dari portal CKG) dapat mengajukan MCU jika belum MCU dalam {{ config('mcu.interval_years', 3) }} tahun.</small>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">MCU {{ $intervalYears }} Tahun</h5>
                <small class="text-muted">sejak {{ $intervalCutoff }}</small>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-5">
                        <div style="height: 140px;"><canvas id="mcuIntervalChart"></canvas></div>
                    </div>
                    <div class="col-7">
                        <div class="dashboard-metric-row">
                            <span>Sudah MCU</span>
                            <strong class="text-success">{{ number_format($mcuSudahInterval) }}</strong>
                        </div>
                        <div class="dashboard-metric-row">
                            <span>Perlu MCU</span>
                            <strong class="text-warning">{{ number_format($mcuBelumInterval) }}</strong>
                        </div>
                        <div class="dashboard-metric-row border-0 pb-0">
                            <span>Persentase patuh</span>
                            <strong>{{ $intervalPercentSudah }}%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Grafik tren --}}
<div class="row mb-4">
    <div class="col-xl-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Tren 6 Bulan Terakhir</h5>
                <small class="text-muted">Peserta baru vs hasil MCU diupload</small>
            </div>
            <div class="card-body">
                <div style="height: 300px;"><canvas id="mcuChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Status Kesehatan (Hasil MCU)</h5></div>
            <div class="card-body">
                @if(count($healthDistribution) > 0)
                    <div style="height: 220px;"><canvas id="healthChart"></canvas></div>
                    <ul class="list-unstyled mb-0 mt-3 small">
                        @foreach($healthDistribution as $row)
                            <li class="d-flex justify-content-between py-1 border-bottom">
                                <span>{{ $row->status_kesehatan ?: 'Tidak diisi' }}</span>
                                <span><strong>{{ $row->count }}</strong> <span class="text-muted">({{ $row->percentage }}%)</span></span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="empty-state py-4">
                        <i class="bx bx-health d-block mb-2"></i>
                        <p class="mb-0">Belum ada data status kesehatan dari hasil MCU.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Grafik Antrian Hari Ini (per Jam)</h5>
        <small class="text-muted">Distribusi jadwal berdasarkan jam pemeriksaan</small>
    </div>
    <div class="card-body">
        <div style="height: 260px;"><canvas id="dailyQueueChart"></canvas></div>
    </div>
</div>

{{-- Top SKPD --}}
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Statistik per SKPD (Top 5)</h5>
        <small class="text-muted">Berdasarkan jumlah peserta terdaftar</small>
    </div>
    <div class="card-body">
        <div class="row">
            @forelse($topSkpds as $skpd)
                @php
                    $rate = (float) ($skpd->completion_rate ?? 0);
                    $scheduled = (int) ($skpd->scheduled_count ?? 0);
                    $completed = (int) ($skpd->completed_count ?? 0);
                    $results = (int) ($skpd->mcu_results_count ?? 0);
                @endphp
                <div class="col-sm-6 col-lg-4 col-xl mb-3">
                    <div class="border rounded p-3 h-100 dashboard-skpd-card">
                        <p class="fw-medium mb-2 text-truncate" title="{{ $skpd->skpd }}">{{ $skpd->skpd }}</p>
                        <h4 class="text-primary mb-2">{{ number_format($skpd->total_participants) }}</h4>
                        <div class="progress dashboard-progress-mini mb-2">
                            <div class="progress-bar bg-success" style="width: {{ min(100, $rate) }}%"></div>
                        </div>
                        <small class="text-muted d-block mb-1">Tingkat selesai jadwal: {{ $rate }}%</small>
                        <small class="text-muted">Terjadwal: {{ $scheduled }} · Selesai: {{ $completed }} · Hasil: {{ $results }}</small>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted mb-0">Belum ada data SKPD.</p></div>
            @endforelse
        </div>
    </div>
</div>

{{-- Pengajuan Jadwal MCU Terbaru --}}
<div class="card mb-4" id="pengajuan-jadwal-mcu">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Pengajuan Jadwal MCU (30 Hari Terakhir)</h5>
        <a href="{{ route('admin.schedules.index', ['status' => \App\Support\ScheduleStatuses::PENDING_ADMIN]) }}" class="btn btn-sm btn-outline-primary">Semua pengajuan menunggu</a>
    </div>
    <div class="card-body">
        @if(($recentScheduleRequests ?? collect())->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Diajukan</th>
                            <th>Peserta</th>
                            <th>NIK</th>
                            <th>Status CKG {{ now()->year }}</th>
                            <th>Tanggal MCU</th>
                            <th>Jam</th>
                            <th>Lokasi</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentScheduleRequests as $s)
                            <tr>
                                <td>{{ $s->created_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $s->participant->nama_lengkap ?? $s->nama_lengkap }}</td>
                                <td>{{ $s->nik_ktp }}</td>
                                <td>@include('partials.participant-ckg-status-badge', ['participant' => $s->participant])</td>
                                <td>{{ $s->tanggal_pemeriksaan?->format('d/m/Y') }}</td>
                                <td>{{ $s->jam_pemeriksaan ? \Carbon\Carbon::parse($s->jam_pemeriksaan)->format('H:i') : '-' }}</td>
                                <td class="text-truncate" style="max-width: 180px;" title="{{ $s->lokasi_pemeriksaan }}">{{ Str::limit($s->lokasi_pemeriksaan, 25) }}</td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        <form method="POST" action="{{ route('admin.schedules.quick-status', $s) }}" class="d-inline" onsubmit="return confirm('Konfirmasi jadwal ini?');">
                                            @csrf
                                            <input type="hidden" name="status" value="Terjadwal">
                                            <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="Konfirmasi"><i class="bx bx-check"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.schedules.quick-status', $s) }}" class="d-inline" onsubmit="return confirm('Tolak pengajuan ini?');">
                                            @csrf
                                            <input type="hidden" name="status" value="Ditolak">
                                            <button type="submit" class="btn btn-sm btn-icon btn-outline-secondary" title="Tolak"><i class="bx bx-block"></i></button>
                                        </form>
                                        <a href="{{ route('admin.schedules.edit', $s) }}" class="btn btn-sm btn-outline-primary" title="Review"><i class="bx bx-edit"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Belum ada pengajuan jadwal MCU yang menunggu konfirmasi dalam 30 hari terakhir.</p>
        @endif
    </div>
</div>

{{-- Antrian Hari Ini --}}
<div class="card mb-4" id="antrian-hari-ini">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Antrian MCU Hari Ini</h5>
        <a href="{{ route('admin.schedules.index', ['date' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-primary">Lihat semua jadwal</a>
    </div>
    <div class="card-body">
        @if($todayQueue->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            <th>NIK</th>
                            <th>Status CKG</th>
                            <th>Jam</th>
                            <th>Lokasi</th>
                            <th>No.</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todayQueue as $s)
                            <tr>
                                <td>{{ $s->participant->nama_lengkap ?? $s->nama_lengkap }}</td>
                                <td>{{ $s->nik_ktp }}</td>
                                <td>@include('partials.participant-ckg-status-badge', ['participant' => $s->participant])</td>
                                <td>{{ $s->jam_pemeriksaan ? \Carbon\Carbon::parse($s->jam_pemeriksaan)->format('H:i') : '-' }}</td>
                                <td class="text-truncate" style="max-width: 180px;" title="{{ $s->lokasi_pemeriksaan }}">{{ Str::limit($s->lokasi_pemeriksaan, 25) }}</td>
                                <td>{{ $s->queue_number ?? '-' }}</td>
                                <td>
                                    @php
                                        $badge = match($s->status) {
                                            'Terjadwal' => 'bg-label-warning',
                                            'Selesai' => 'bg-label-success',
                                            'Menunggu Konfirmasi' => 'bg-label-info',
                                            'Batal' => 'bg-label-danger',
                                            'Ditolak' => 'bg-label-secondary',
                                            default => 'bg-label-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $s->status }}</span>
                                    @if($s->participant_confirmed)
                                        <span class="badge bg-label-primary ms-1">Hadir</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        @if($s->status !== 'Selesai')
                                            <form method="POST" action="{{ route('admin.schedules.quick-status', $s) }}" class="d-inline" onsubmit="return confirm('Tandai selesai?');">
                                                @csrf
                                                <input type="hidden" name="status" value="Selesai">
                                                <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="Selesai"><i class="bx bx-check"></i></button>
                                            </form>
                                        @endif
                                        @if($s->status !== 'Ditolak')
                                            <form method="POST" action="{{ route('admin.schedules.quick-status', $s) }}" class="d-inline" onsubmit="return confirm('Tolak jadwal ini?');">
                                                @csrf
                                                <input type="hidden" name="status" value="Ditolak">
                                                <button type="submit" class="btn btn-sm btn-icon btn-outline-secondary" title="Tolak"><i class="bx bx-block"></i></button>
                                            </form>
                                        @endif
                                        @if($s->status !== 'Batal')
                                            <form method="POST" action="{{ route('admin.schedules.quick-status', $s) }}" class="d-inline" onsubmit="return confirm('Batalkan jadwal ini?');">
                                                @csrf
                                                <input type="hidden" name="status" value="Batal">
                                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Batal"><i class="bx bx-x"></i></button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.schedules.edit', $s) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Tidak ada antrian hari ini.</p>
        @endif
    </div>
</div>

{{-- Konfirmasi Hadir --}}
<div class="card mb-4" id="konfirmasi-hadir">
    <div class="card-header"><h5 class="mb-0">Konfirmasi Hadir — Siap Diselesaikan (Hari Ini)</h5></div>
    <div class="card-body">
        @if($confirmedToday->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Peserta</th>
                            <th>NIK</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Lokasi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($confirmedToday as $s)
                            <tr>
                                <td>{{ $s->participant->nama_lengkap ?? $s->nama_lengkap }}</td>
                                <td>{{ $s->nik_ktp }}</td>
                                <td>{{ $s->tanggal_pemeriksaan?->format('d/m/Y') }}</td>
                                <td>{{ $s->jam_pemeriksaan?->format('H:i') }}</td>
                                <td class="text-truncate" style="max-width: 200px;" title="{{ $s->lokasi_pemeriksaan }}">{{ Str::limit($s->lokasi_pemeriksaan, 35) }}</td>
                                <td>
                                    <a href="{{ route('admin.schedules.edit', $s) }}" class="btn btn-sm btn-outline-primary">Tandai Selesai</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Tidak ada peserta yang sudah konfirmasi hadir hari ini.</p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const chartLabels = @json($chartLabels);
    const participantsData = @json($participantsByMonth);
    const mcuResultsData = @json($mcuResultsByMonth);
    const mcuSudahInterval = {{ $mcuSudahInterval }};
    const mcuBelumInterval = {{ $mcuBelumInterval }};
    const intervalYears = {{ (int) $intervalYears }};
    const ckgCompleted = {{ (int) ($ckgSummary->completed ?? 0) }};
    const ckgBelum = {{ (int) ($ckgSummary->belum ?? 0) }};
    const healthData = @json($healthDistribution);

    const doughnutDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
    };

    new Chart(document.getElementById('mcuIntervalChart'), {
        type: 'doughnut',
        data: {
            labels: ['Sudah MCU (' + intervalYears + ' th)', 'Perlu MCU'],
            datasets: [{
                data: [mcuSudahInterval, mcuBelumInterval],
                backgroundColor: ['#71dd37', '#ffab00'],
                borderWidth: 0,
            }],
        },
        options: doughnutDefaults,
    });

    new Chart(document.getElementById('ckgChart'), {
        type: 'doughnut',
        data: {
            labels: ['Sudah CKG', 'Belum CKG'],
            datasets: [{
                data: [ckgCompleted, ckgBelum],
                backgroundColor: ['#696cff', '#ffab00'],
                borderWidth: 0,
            }],
        },
        options: { ...doughnutDefaults, plugins: { legend: { display: false } } },
    });

    new Chart(document.getElementById('mcuChart'), {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                { label: 'Peserta Baru', data: participantsData, borderColor: '#696cff', backgroundColor: 'rgba(105,108,255,0.15)', fill: true, tension: 0.35 },
                { label: 'Hasil MCU', data: mcuResultsData, borderColor: '#71dd37', backgroundColor: 'rgba(113,221,55,0.15)', fill: true, tension: 0.35 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    if (healthData.length) {
        const healthColors = ['#71dd37', '#ffab00', '#ff3e1d', '#696cff', '#8592a3', '#03c3ec'];
        new Chart(document.getElementById('healthChart'), {
            type: 'doughnut',
            data: {
                labels: healthData.map(r => r.status_kesehatan || 'Tidak diisi'),
                datasets: [{
                    data: healthData.map(r => r.count),
                    backgroundColor: healthData.map((_, i) => healthColors[i % healthColors.length]),
                    borderWidth: 0,
                }],
            },
            options: doughnutDefaults,
        });
    }

    @php
        $dailyQueueDataJs = $dailyQueueData ?? ['labels' => [], 'terjadwal' => [], 'selesai' => [], 'batal' => [], 'ditolak' => []];
    @endphp
    const dailyQueueData = @json($dailyQueueDataJs);
    if (dailyQueueData.labels && dailyQueueData.labels.length) {
        new Chart(document.getElementById('dailyQueueChart'), {
            type: 'line',
            data: {
                labels: dailyQueueData.labels,
                datasets: [
                    { label: 'Antrian (Aktif)', data: dailyQueueData.terjadwal || [], borderColor: '#ffab00', backgroundColor: 'rgba(255,171,0,0.15)', fill: true, tension: 0.35 },
                    { label: 'Selesai', data: dailyQueueData.selesai || [], borderColor: '#71dd37', backgroundColor: 'rgba(113,221,55,0.15)', fill: true, tension: 0.35 },
                    { label: 'Batal', data: dailyQueueData.batal || [], borderColor: '#ff3e1d', backgroundColor: 'rgba(255,62,29,0.15)', fill: true, tension: 0.35 },
                    { label: 'Ditolak', data: dailyQueueData.ditolak || [], borderColor: '#8592a3', backgroundColor: 'rgba(133,146,163,0.15)', fill: true, tension: 0.35 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
})();
</script>
@endpush
