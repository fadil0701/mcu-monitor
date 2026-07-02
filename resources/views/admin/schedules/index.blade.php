@extends('layouts.sneat.app')

@section('title', 'Jadwal MCU')
@section('pageTitle', 'Jadwal MCU')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Daftar Jadwal MCU</h5>
        <a href="{{ route('admin.schedules.create') }}" class="btn btn-primary btn-sm">
            <i class="bx bx-plus me-1"></i> Tambah Jadwal
        </a>
    </div>
    <div class="card-body">
        @if($errors->has('send'))
            <div class="alert alert-danger">{{ $errors->first('send') }}</div>
        @endif

        <div class="filter-toolbar">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, NIK, lokasi..." class="form-control form-control-sm">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="Menunggu Konfirmasi" {{ request('status') === 'Menunggu Konfirmasi' ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                        <option value="Terjadwal" {{ request('status') === 'Terjadwal' ? 'selected' : '' }}>Terjadwal</option>
                        <option value="Selesai" {{ request('status') === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                        <option value="Batal" {{ request('status') === 'Batal' ? 'selected' : '' }}>Batal</option>
                        <option value="Ditolak" {{ request('status') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search me-1"></i> Cari</button>
                </div>
                <div class="col-md-auto ms-md-auto">
                    <button type="button" id="bulk-delete-btn" class="btn btn-outline-danger btn-sm" disabled>
                        <i class="bx bx-trash me-1"></i> Bulk Hapus (<span id="selected-count">0</span>)
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 42px;">
                                <input type="checkbox" id="select-all" class="form-check-input" title="Pilih semua">
                            </th>
                            <th>Tanggal</th>
                            <th>Peserta</th>
                            <th>Status CKG</th>
                            <th>Lokasi</th>
                            <th>No. Antrian</th>
                            <th>Status</th>
                            <th class="text-center" style="min-width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schedules as $s)
                            <tr>
                                <td>
                                    <input type="checkbox" value="{{ $s->id }}" class="form-check-input schedule-checkbox">
                                </td>
                                <td>{{ $s->tanggal_pemeriksaan?->format('d/m/Y') }}</td>
                                <td class="fw-medium">{{ $s->nama_lengkap ?? $s->participant?->nama_lengkap }}</td>
                                <td>@include('partials.participant-ckg-status-badge', ['participant' => $s->participant])</td>
                                <td class="text-truncate" style="max-width: 200px;" title="{{ $s->lokasi_pemeriksaan }}">{{ $s->lokasi_pemeriksaan }}</td>
                                <td>{{ $s->queue_number ?? '-' }}</td>
                                <td>
                                    @php
                                        $badge = match($s->status) {
                                            'Selesai' => 'bg-label-success',
                                            'Menunggu Konfirmasi' => 'bg-label-info',
                                            'Batal', 'Ditolak' => 'bg-label-danger',
                                            default => 'bg-label-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $s->status }}</span>
                                </td>
                                <td>
                                    <div class="table-action-group">
                                        @if($s->status === 'Menunggu Konfirmasi')
                                            <form method="POST" action="{{ route('admin.schedules.quick-status', $s) }}" class="d-inline" onsubmit="return confirm('Konfirmasi jadwal ini?');">
                                                @csrf
                                                <input type="hidden" name="status" value="Terjadwal">
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Konfirmasi"><i class="bx bx-check"></i></button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.schedules.quick-status', $s) }}" class="d-inline" onsubmit="return confirm('Tolak pengajuan ini?');">
                                                @csrf
                                                <input type="hidden" name="status" value="Ditolak">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Tolak"><i class="bx bx-block"></i></button>
                                            </form>
                                        @endif
                                        @if($whatsappSendEnabled ?? false)
                                        <form method="POST" action="{{ route('admin.schedules.send-whatsapp', $s) }}" class="d-inline" onsubmit="return confirm('Kirim jadwal MCU via WhatsApp ke {{ addslashes($s->nama_lengkap) }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Kirim WA"><i class="bx bxl-whatsapp"></i></button>
                                        </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.schedules.send-email', $s) }}" class="d-inline" onsubmit="return confirm('Kirim jadwal MCU via Email ke {{ addslashes($s->email ?? $s->nama_lengkap) }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Kirim Email"><i class="bx bx-envelope"></i></button>
                                        </form>
                                        <x-admin.action-badge type="edit" :href="route('admin.schedules.edit', $s)" label="" />
                                        <x-admin.action-badge type="delete" :href="route('admin.schedules.destroy', $s)" confirm="Yakin hapus jadwal ini?" label="" />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="empty-state">Belum ada jadwal.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @if($schedules->hasPages())
            <div class="mt-3">{{ $schedules->links() }}</div>
        @endif

        <form id="bulk-delete-form" method="POST" action="{{ route('admin.schedules.bulk-destroy') }}" class="d-none">
            @csrf
            <input type="hidden" name="search" value="{{ request('search') }}">
            <input type="hidden" name="date" value="{{ request('date') }}">
            <input type="hidden" name="status" value="{{ request('status') }}">
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.schedule-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const selectedCount = document.getElementById('selected-count');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');

    function updateUI() {
        const checked = document.querySelectorAll('.schedule-checkbox:checked');
        const count = checked.length;
        selectedCount.textContent = count;
        bulkDeleteBtn.disabled = count === 0;
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
    }

    selectAll?.addEventListener('change', function() {
        checkboxes.forEach(cb => { cb.checked = this.checked; });
        updateUI();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateUI));

    bulkDeleteBtn?.addEventListener('click', function() {
        if (this.disabled) return;
        if (!confirm('Yakin hapus jadwal yang dipilih?')) return;
        bulkDeleteForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
        document.querySelectorAll('.schedule-checkbox:checked').forEach(function(cb) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            bulkDeleteForm.appendChild(input);
        });
        bulkDeleteForm.submit();
    });
    updateUI();
})();
</script>
@endpush
