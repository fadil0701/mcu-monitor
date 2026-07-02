@extends('layouts.sneat.app')

@section('title', 'Daftar Ulang MCU')
@section('breadcrumb', 'Portal Peserta')
@section('pageTitle', 'Pendaftaran Ulang MCU')

@section('content')
<div class="row">
    <div class="col-lg-8 mb-4">
        <x-common.component-card title="Form Permintaan Jadwal">
            @unless($eligible)
                <div class="alert alert-warning">
                    <i class="bx bx-error me-1"></i>{{ $reason }}
                </div>
            @endunless

            @if($eligible && ! empty($infoNotes))
                @foreach($infoNotes as $note)
                    <div class="alert alert-info py-2">
                        <i class="bx bx-info-circle me-1"></i>{{ $note }}
                    </div>
                @endforeach
            @endif

            @if($eligible && $dailyQuota > 0)
                <div class="alert alert-info py-2">
                    <i class="bx bx-info-circle me-1"></i>
                    Kuota pemeriksaan MCU: maksimal <strong>{{ number_format($dailyQuota, 0, ',', '.') }}</strong> peserta per hari di {{ config('mcu.default_location') }}.
                </div>
            @endif

            <form method="POST" action="{{ route('client.schedule.request.store') }}" id="schedule-request-form">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Pemeriksaan *</label>
                        <input
                            type="date"
                            name="tanggal_pemeriksaan"
                            id="tanggal_pemeriksaan"
                            class="form-control @error('tanggal_pemeriksaan') is-invalid @enderror"
                            value="{{ old('tanggal_pemeriksaan') }}"
                            min="{{ now()->format('Y-m-d') }}"
                            {{ $eligible ? '' : 'disabled' }}
                            required
                        >
                        <div id="quota-info" class="form-text mt-1"></div>
                        @error('tanggal_pemeriksaan')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jam Pemeriksaan *</label>
                        <input
                            type="time"
                            name="jam_pemeriksaan"
                            class="form-control @error('jam_pemeriksaan') is-invalid @enderror"
                            value="{{ old('jam_pemeriksaan', config('mcu.examination_hours.start', '07:30')) }}"
                            min="{{ config('mcu.examination_hours.start', '07:30') }}"
                            max="{{ config('mcu.examination_hours.end', '10:00') }}"
                            step="60"
                            {{ $eligible ? '' : 'disabled' }}
                            required
                        >
                        <div class="form-text">Jam pendaftaran: {{ config('mcu.examination_hours.start', '07:30') }} – {{ config('mcu.examination_hours.end', '10:00') }} WIB.</div>
                        @error('jam_pemeriksaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Lokasi Pemeriksaan *</label>
                        <input
                            type="text"
                            class="form-control bg-light"
                            value="{{ config('mcu.default_location') }}"
                            readonly
                            tabindex="-1"
                            aria-readonly="true"
                        >
                        <div class="form-text">Lokasi pemeriksaan ditetapkan sistem dan tidak dapat diubah.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan (opsional)</label>
                        <textarea name="catatan" class="form-control" rows="3" {{ $eligible ? '' : 'disabled' }}>{{ old('catatan') }}</textarea>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" id="schedule-submit-btn" {{ $eligible ? '' : 'disabled' }}>
                        <i class="bx bx-send me-1"></i> Ajukan Jadwal
                    </button>
                    <a href="{{ route('client.schedules') }}" class="btn btn-outline-secondary ms-2">Kembali</a>
                </div>
            </form>
        </x-common.component-card>
    </div>

    <div class="col-lg-4 mb-4">
        <x-common.component-card title="Status Kelayakan">
            <dl class="row mb-0">
                <dt class="col-sm-5">Nama</dt>
                <dd class="col-sm-7">{{ $participant->nama_lengkap }}</dd>
                <dt class="col-sm-5">NIK</dt>
                <dd class="col-sm-7">{{ $participant->nik_ktp }}</dd>
                <dt class="col-sm-5">SKPD</dt>
                <dd class="col-sm-7">{{ $participant->skpd }}</dd>
                <dt class="col-sm-5">Skrining CKG</dt>
                <dd class="col-sm-7">
                    <span class="badge bg-label-{{ $hasCkgScreening ? 'success' : 'danger' }}">
                        {{ $hasCkgScreening ? 'Sudah' : 'Belum' }}
                    </span>
                </dd>
                <dt class="col-sm-5">MCU Terakhir</dt>
                <dd class="col-sm-7">{{ $participant->tanggal_mcu_terakhir_formatted }}</dd>
                @if($participant->isWithinMcuInterval())
                    <dt class="col-sm-5">Pengajuan ulang</dt>
                    <dd class="col-sm-7">
                        <span class="text-muted">Mulai {{ $participant->mcuEligibleFrom()?->format('d/m/Y') ?? '-' }}</span>
                    </dd>
                @endif
                @if($dailyQuota > 0)
                    <dt class="col-sm-5">Kuota / hari</dt>
                    <dd class="col-sm-7">Maks. {{ number_format($dailyQuota, 0, ',', '.') }} peserta</dd>
                @endif
                <dt class="col-sm-5">Kelayakan</dt>
                <dd class="col-sm-7">
                    <span class="badge bg-label-{{ $eligible ? 'success' : 'warning' }}">
                        {{ $eligible ? 'Memenuhi syarat' : 'Belum memenuhi' }}
                    </span>
                </dd>
            </dl>
        </x-common.component-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const dateInput = document.getElementById('tanggal_pemeriksaan');
    const quotaInfo = document.getElementById('quota-info');
    const submitBtn = document.getElementById('schedule-submit-btn');
    const quotaUrl = @json(route('client.schedule.quota'));
    const dailyQuota = @json((int) $dailyQuota);
    const formEnabled = @json($eligible);

    if (!dateInput || !quotaInfo || !formEnabled) {
        return;
    }

    async function refreshQuota() {
        const date = dateInput.value;
        if (!date) {
            quotaInfo.textContent = dailyQuota > 0
                ? `Pilih tanggal untuk melihat sisa kuota (maks. ${dailyQuota} peserta/hari).`
                : '';
            if (submitBtn) {
                submitBtn.disabled = false;
            }
            return;
        }

        quotaInfo.textContent = 'Memuat informasi kuota...';

        try {
            const response = await fetch(`${quotaUrl}?date=${encodeURIComponent(date)}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Gagal memuat kuota');
            }

            const data = await response.json();

            if (data.unlimited) {
                quotaInfo.textContent = 'Kuota harian tidak dibatasi.';
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
                return;
            }

            if (data.available) {
                quotaInfo.innerHTML = `Sisa kuota tanggal ini: <strong>${data.remaining}</strong> dari <strong>${data.limit}</strong> peserta (${data.booked} terisi).`;
                quotaInfo.classList.remove('text-danger');
                quotaInfo.classList.add('text-success');
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            } else {
                quotaInfo.innerHTML = `Kuota tanggal ini sudah penuh (<strong>${data.booked}/${data.limit}</strong>). Pilih tanggal lain.`;
                quotaInfo.classList.remove('text-success');
                quotaInfo.classList.add('text-danger');
                if (submitBtn) {
                    submitBtn.disabled = true;
                }
            }
        } catch (error) {
            quotaInfo.textContent = 'Tidak dapat memuat informasi kuota. Coba lagi.';
            quotaInfo.classList.remove('text-success');
            quotaInfo.classList.add('text-danger');
        }
    }

    dateInput.addEventListener('change', refreshQuota);
    refreshQuota();
})();
</script>
@endpush
