@extends('layouts.sneat.app')

@section('title', 'Hasil MCU')
@section('breadcrumb', 'Portal Peserta')
@section('pageTitle', 'Hasil MCU')

@section('content')
<x-common.component-card title="Hasil MCU Saya">
    @if($mcuResults->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Diagnosis</th>
                        <th>Status</th>
                        <th>File</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mcuResults as $index => $result)
                        <tr>
                            <td>{{ $mcuResults->firstItem() ? $mcuResults->firstItem() + $index : $index + 1 }}</td>
                            <td class="fw-medium">{{ $result->tanggal_pemeriksaan_formatted }}</td>
                            <td>{{ ($result->diagnosis_text && $result->diagnosis_text !== '-') ? $result->diagnosis_text : '-' }}</td>
                            <td><span class="badge bg-label-{{ $result->status_kesehatan_color }}">{{ $result->status_kesehatan }}</span></td>
                            <td>
                                @if($result->hasFile())
                                    <span class="badge bg-label-success">Tersedia</span>
                                @else
                                    <span class="badge bg-label-secondary">Tidak ada</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($result->hasFile())
                                    <a href="{{ route('client.results.downloadAll', $result->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bx bx-download"></i> Download
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-outline-secondary" disabled>-</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($mcuResults->hasPages())
            <div class="mt-3">{{ $mcuResults->links() }}</div>
        @endif
    @else
        <div class="empty-state text-center py-5">
            <i class="bx bx-file bx-lg text-muted d-block mb-2"></i>
            <p class="text-muted mb-0">Belum ada hasil MCU yang tersedia.</p>
        </div>
    @endif
</x-common.component-card>

@if($mcuResults->count() > 0)
    @php $latestResult = $mcuResults->first(); @endphp
    <x-common.component-card title="Detail Hasil Terbaru">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label text-muted small">Hasil Pemeriksaan</label>
                <div class="border rounded p-3 bg-light">{{ $latestResult->hasil_pemeriksaan ?: '-' }}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small">Rekomendasi</label>
                <div class="border rounded p-3 bg-light">{{ $latestResult->rekomendasi ?: 'Tidak ada rekomendasi khusus' }}</div>
            </div>
        </div>
    </x-common.component-card>
@endif
@endsection
