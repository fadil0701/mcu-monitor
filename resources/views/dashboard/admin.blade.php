@extends('layouts.sneat.app')

@section('title', 'Dashboard MCU')
@section('pageTitle', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <p class="text-muted mb-0">Selamat datang, <span class="fw-semibold text-body">{{ Auth::user()->name }}</span></p>
    <small class="text-muted">Terakhir diperbarui: {{ now()->format('d/m/Y H:i') }}</small>
</div>

{{-- KPI Cards --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="avatar flex-shrink-0">
                        <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-group bx-sm"></i></span>
                    </div>
                </div>
                <span class="d-block mb-1">Total Peserta</span>
                <h3 class="card-title mb-0">{{ $stats->total_participants ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-warning"><i class="bx bx-calendar bx-sm"></i></span>
                </div>
                <span class="d-block mb-1">Peserta Terjadwal</span>
                <h3 class="card-title mb-0">{{ $stats->scheduled_participants ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-success"><i class="bx bx-check-circle bx-sm"></i></span>
                </div>
                <span class="d-block mb-1">MCU Selesai</span>
                <h3 class="card-title mb-0">{{ $stats->completed_mcu ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="avatar flex-shrink-0 mb-2">
                    <span class="avatar-initial rounded bg-label-info"><i class="bx bx-time-five bx-sm"></i></span>
                </div>
                <span class="d-block mb-1">MCU Pending</span>
                <h3 class="card-title mb-0">{{ $stats->pending_mcu ?? 0 }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- Statistik Hari Ini --}}
@php $canReschedule = Auth::user()->hasRole('super_admin'); @endphp
<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <a href="#antrian-hari-ini" class="card h-100 text-body text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar"><span class="avatar-initial rounded bg-label-success"><i class="bx bx-user-check"></i></span></span>
                <div>
                    <h4 class="mb-0">{{ $confirmedTodayCount ?? 0 }}</h4>
                    <small class="text-muted">Konfirmasi Hadir (Hari Ini)</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 mb-4">
        <a href="{{ $canReschedule ? route('admin.reschedule-center.index') : '#' }}" class="card h-100 text-body text-decoration-none {{ $canReschedule ? '' : 'pe-none opacity-75' }}">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="avatar"><span class="avatar-initial rounded bg-label-warning"><i class="bx bx-calendar-edit"></i></span></span>
                <div>
                    <h4 class="mb-0">{{ $pendingRescheduleToday ?? 0 }}</h4>
                    <small class="text-muted">{{ $canReschedule ? 'Permintaan Reschedule →' : 'Permintaan Reschedule (Hari Ini)' }}</small>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- Charts --}}
<div class="row mb-4">
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Statistik MCU (6 Bulan)</h5></div>
            <div class="card-body">
                <div style="height: 320px;"><canvas id="mcuChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Status Kesehatan</h5></div>
            <div class="card-body">
                <div style="height: 320px;"><canvas id="healthChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Grafik Antrian Hari Ini (per Jam)</h5></div>
    <div class="card-body">
        <div style="height: 260px;"><canvas id="dailyQueueChart"></canvas></div>
    </div>
</div>

{{-- Top SKPD --}}
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Statistik per SKPD (Top 5)</h5></div>
    <div class="card-body">
        <div class="row">
            @forelse($topSkpds as $skpd)
                <div class="col-sm-6 col-lg-4 col-xl mb-3">
                    <div class="border rounded p-3 h-100">
                        <p class="fw-medium mb-1 text-truncate" title="{{ $skpd->skpd }}">{{ $skpd->skpd }}</p>
                        <h4 class="text-primary mb-1">{{ $skpd->total_participants }}</h4>
                        <small class="text-muted">Terjadwal: {{ $skpd->scheduled_count }} | Selesai: {{ $skpd->completed_count }}</small>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted mb-0">Belum ada data SKPD.</p></div>
            @endforelse
        </div>
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
                                <td>{{ $s->jam_pemeriksaan ? \Carbon\Carbon::parse($s->jam_pemeriksaan)->format('H:i') : '-' }}</td>
                                <td class="text-truncate" style="max-width: 180px;" title="{{ $s->lokasi_pemeriksaan }}">{{ Str::limit($s->lokasi_pemeriksaan, 25) }}</td>
                                <td>{{ $s->queue_number ?? '-' }}</td>
                                <td>
                                    @php
                                        $badge = match($s->status) {
                                            'Terjadwal' => 'bg-label-warning',
                                            'Selesai' => 'bg-label-success',
                                            'Batal' => 'bg-label-danger',
                                            'Ditolak' => 'bg-label-secondary',
                                            default => 'bg-label-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $s->status }}</span>
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
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Konfirmasi Hadir - Siap Diselesaikan (Hari Ini)</h5></div>
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
            scales: { y: { beginAtZero: true } }
        }
    });

    const healthStats = @json($healthStats);
    const healthLabels = healthStats.map(s => s.status_kesehatan || 'Lainnya');
    const healthCounts = healthStats.map(s => s.count);
    const healthColors = ['#71dd37', '#ffab00', '#ff3e1d', '#03c3ec'];

    new Chart(document.getElementById('healthChart'), {
        type: 'doughnut',
        data: {
            labels: healthLabels,
            datasets: [{ data: healthCounts, backgroundColor: healthColors.slice(0, healthLabels.length) }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

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
                scales: { y: { beginAtZero: true } }
            }
        });
    }
})();
</script>
@endpush
