@extends('layouts.sneat.app')

@section('title', 'Jadwal MCU')
@section('breadcrumb', 'Portal Peserta')
@section('pageTitle', 'Jadwal MCU')

@section('content')
<x-common.component-card title="Jadwal MCU Saya">
    <div class="page-toolbar mb-3">
        <div class="page-toolbar-actions ms-auto">
            <a href="{{ route('client.schedule.request') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus me-1"></i> Daftar Ulang MCU
            </a>
        </div>
    </div>

    @if($schedules->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Lokasi</th>
                        <th>Antrian</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $index => $schedule)
                        <tr>
                            <td>{{ $schedules->firstItem() ? $schedules->firstItem() + $index : $index + 1 }}</td>
                            <td>{{ $schedule->tanggal_pemeriksaan_formatted }}</td>
                            <td>{{ $schedule->jam_pemeriksaan_formatted }}</td>
                            <td>{{ $schedule->lokasi_pemeriksaan }}</td>
                            <td>
                                @if($schedule->queue_number)
                                    <span class="badge bg-label-primary">{{ $schedule->queue_number }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-label-{{ $schedule->status_color }}">{{ $schedule->status }}</span>
                                @if($schedule->participant_confirmed)
                                    <span class="badge bg-label-success ms-1">Hadir</span>
                                @endif
                                @if($schedule->reschedule_requested)
                                    <span class="badge bg-label-warning ms-1">Reschedule</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($schedule->status === 'Terjadwal' && !$schedule->participant_confirmed)
                                    <div class="d-flex flex-column gap-2 align-items-stretch">
                                        <form method="POST" action="{{ route('client.schedule.confirm', $schedule->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success w-100">Konfirmasi Hadir</button>
                                        </form>
                                        <button class="btn btn-sm btn-outline-warning" type="button" data-bs-toggle="collapse" data-bs-target="#resched-{{ $schedule->id }}">Reschedule</button>
                                        <div class="collapse text-start" id="resched-{{ $schedule->id }}">
                                            <form method="POST" action="{{ route('client.schedule.reschedule', $schedule->id) }}" class="row g-2 mt-1">
                                                @csrf
                                                <div class="col-12"><input type="date" name="new_date" class="form-control form-control-sm" min="{{ now()->toDateString() }}" required></div>
                                                <div class="col-12"><input type="time" name="new_time" class="form-control form-control-sm" required></div>
                                                <div class="col-12"><input type="text" name="reason" class="form-control form-control-sm" placeholder="Alasan reschedule" required></div>
                                                <div class="col-12"><button type="submit" class="btn btn-sm btn-primary w-100">Kirim</button></div>
                                            </form>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#cancel-{{ $schedule->id }}">Batalkan</button>
                                        <div class="collapse text-start" id="cancel-{{ $schedule->id }}">
                                            <form method="POST" action="{{ route('client.schedule.cancel', $schedule->id) }}" class="mt-1">
                                                @csrf
                                                <input type="text" name="cancel_reason" class="form-control form-control-sm mb-2" placeholder="Alasan pembatalan" required>
                                                <button type="submit" class="btn btn-sm btn-danger w-100">Kirim Pembatalan</button>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($schedules->hasPages())
            <div class="mt-3">{{ $schedules->links() }}</div>
        @endif
    @else
        <div class="empty-state text-center py-5">
            <i class="bx bx-calendar-x d-block mb-2 bx-lg text-muted"></i>
            <p class="text-muted mb-3">Belum ada jadwal MCU.</p>
            <a href="{{ route('client.schedule.request') }}" class="btn btn-sm btn-primary">Ajukan Jadwal MCU</a>
        </div>
    @endif
</x-common.component-card>
@endsection
