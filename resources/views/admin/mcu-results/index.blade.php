@extends('layouts.sneat.app')

@section('title', 'Hasil MCU')
@section('pageTitle', 'Hasil MCU')

@section('content')
<x-common.component-card title="Daftar Hasil MCU">
    @if($usingDefaultPeriod ?? false)
        <div class="alert alert-info py-2 mb-3">
            Menampilkan peserta MCU selesai pada <strong>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $periodValueFilter)->translatedFormat('F Y') }}</strong>.
            Gunakan filter periodik untuk melihat bulan/tahun lain, atau pilih <strong>Semua</strong> untuk seluruh data.
        </div>
    @endif
    <div class="page-toolbar mb-4">
        <div class="filter-toolbar">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6 col-lg-3">
                    <label class="form-label mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / NIK peserta..." class="form-control form-control-sm">
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label mb-1">Status hasil</label>
                    <select name="status_hasil" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="uploaded" @selected(request('status_hasil') === 'uploaded')>Sudah di Upload</option>
                        <option value="not_uploaded" @selected(request('status_hasil') === 'not_uploaded')>Belum di upload</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label mb-1">Publikasi</label>
                    <select name="publikasi" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="ya" @selected(request('publikasi') === 'ya')>Ya</option>
                        <option value="tidak" @selected(request('publikasi') === 'tidak')>Tidak</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <label class="form-label mb-1">Periodik</label>
                    <select name="period" id="mcu-results-period" class="form-select form-select-sm">
                        <option value="" @selected(($periodFilter ?? request('period')) === null || ($periodFilter ?? request('period')) === '')>Semua</option>
                        <option value="hari" @selected(($periodFilter ?? request('period')) === 'hari')>Hari</option>
                        <option value="bulan" @selected(($periodFilter ?? request('period')) === 'bulan')>Bulan</option>
                        <option value="tahun" @selected(($periodFilter ?? request('period')) === 'tahun')>Tahun</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg-2" id="mcu-results-period-value-wrap">
                    <label class="form-label mb-1" for="mcu-results-period-value">Nilai periode</label>
                    <input
                        type="text"
                        name="period_value"
                        id="mcu-results-period-value"
                        value="{{ $periodValueFilter ?? request('period_value') }}"
                        class="form-control form-control-sm"
                        @disabled(empty($periodFilter ?? request('period')))
                    >
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search me-1"></i> Cari</button>
                </div>
                @if(request()->hasAny(['search', 'status_hasil', 'publikasi', 'period', 'period_value']) && ! ($usingDefaultPeriod ?? false))
                    <div class="col-md-auto">
                        <a href="{{ route('admin.mcu-results.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                @endif
            </form>
        </div>
        <div class="page-toolbar-actions">
            <a href="{{ route('admin.mcu-results.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i> Tambah Hasil MCU</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Peserta</th>
                    <th>Publikasi</th>
                    <th>Status hasil</th>
                    <th class="text-center">Download</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $participant)
                    @php
                        $examDate = $participant->tanggal_mcu_terakhir
                            ?? $participant->schedules->firstWhere('status', 'Selesai')?->tanggal_pemeriksaan
                            ?? $participant->schedules->first()?->tanggal_pemeriksaan
                            ?? $participant->mcuResults->first()?->tanggal_pemeriksaan;
                        $dateKey = $examDate?->format('Y-m-d');
                        $r = $dateKey
                            ? $participant->mcuResults->first(
                                fn ($result) => $result->tanggal_pemeriksaan?->format('Y-m-d') === $dateKey
                            )
                            : $participant->mcuResults->first();
                        $r ??= $participant->mcuResults->first();
                        $createUrl = route('admin.mcu-results.create') . '?' . http_build_query(array_filter([
                            'participant_id' => $participant->id,
                            'tanggal_pemeriksaan' => $dateKey,
                        ]));
                    @endphp
                    <tr>
                        <td>{{ $examDate?->format('d/m/Y') ?? '—' }}</td>
                        <td class="fw-medium">{{ $participant->nama_lengkap }}</td>
                        <td>
                            @if($r)
                                <span class="badge {{ $r->is_published ? 'bg-label-success' : 'bg-label-warning' }}">{{ $r->is_published ? 'Ya' : 'Tidak' }}</span>
                            @else
                                <span class="badge bg-label-secondary">—</span>
                            @endif
                        </td>
                        <td>
                            @if($r && $r->hasFile())
                                <span class="badge bg-label-success">Sudah di Upload</span>
                            @else
                                <span class="badge bg-label-warning">Belum di upload</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($r && $r->hasFile())
                                <a href="{{ route('admin.mcu-results.downloadAll', $r) }}" class="btn btn-sm btn-primary" title="Download hasil MCU">
                                    <i class="bx bx-download"></i>
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="table-action-group">
                                @if($r)
                                    @if($participant->email && ! \App\Models\Participant::isPlaceholderEmail($participant->email))
                                        <form method="POST" action="{{ route('admin.mcu-results.send-email', $r) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Kirim Email"><i class="bx bx-envelope"></i></button>
                                        </form>
                                    @endif

                                    @if(($whatsappSendEnabled ?? false) && $participant->no_telp && $participant->no_telp !== '-')
                                        <form method="POST" action="{{ route('admin.mcu-results.send-whatsapp', $r) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Kirim WhatsApp"><i class="bx bxl-whatsapp"></i></button>
                                        </form>
                                    @endif

                                    <x-admin.action-badge type="edit" :href="route('admin.mcu-results.edit', $r)" />
                                    <x-admin.action-badge type="delete" :href="route('admin.mcu-results.destroy', $r)" confirm="Yakin hapus hasil MCU ini?" />
                                @else
                                    <a href="{{ $createUrl }}" class="btn btn-sm btn-icon btn-outline-primary" title="Upload Hasil MCU">
                                        <i class="bx bx-upload"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada peserta dengan status Sudah MCU yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $participants->links() }}</div>
</x-common.component-card>
@endsection

@push('scripts')
<script>
(function () {
    const periodSelect = document.getElementById('mcu-results-period');
    const valueInput = document.getElementById('mcu-results-period-value');
    const valueWrap = document.getElementById('mcu-results-period-value-wrap');

    if (!periodSelect || !valueInput) {
        return;
    }

    const defaults = {
        hari: @json(now()->format('Y-m-d')),
        bulan: @json(now()->format('Y-m')),
        tahun: @json(now()->format('Y')),
    };

    function syncPeriodInput() {
        const period = periodSelect.value;
        const current = valueInput.value;

        if (!period) {
            valueInput.disabled = true;
            valueInput.type = 'text';
            valueInput.removeAttribute('min');
            valueInput.removeAttribute('max');
            valueInput.placeholder = '';
            valueWrap.classList.add('opacity-50');
            return;
        }

        valueInput.disabled = false;
        valueWrap.classList.remove('opacity-50');

        if (period === 'hari') {
            valueInput.type = 'date';
            valueInput.removeAttribute('min');
            valueInput.removeAttribute('max');
            valueInput.placeholder = '';
            if (!current || !/^\d{4}-\d{2}-\d{2}$/.test(current)) {
                valueInput.value = defaults.hari;
            }
            return;
        }

        if (period === 'bulan') {
            valueInput.type = 'month';
            valueInput.removeAttribute('min');
            valueInput.removeAttribute('max');
            valueInput.placeholder = '';
            if (!current || !/^\d{4}-\d{2}$/.test(current)) {
                valueInput.value = defaults.bulan;
            }
            return;
        }

        valueInput.type = 'number';
        valueInput.min = '2000';
        valueInput.max = '2100';
        valueInput.placeholder = 'Contoh: 2026';
        if (!current || !/^\d{4}$/.test(current)) {
            valueInput.value = defaults.tahun;
        }
    }

    periodSelect.addEventListener('change', syncPeriodInput);
    syncPeriodInput();
})();
</script>
@endpush
