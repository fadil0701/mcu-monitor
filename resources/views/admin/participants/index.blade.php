@extends('layouts.sneat.app')

@section('title', 'Data Peserta')
@section('pageTitle', 'Data Peserta')

@section('content')
<x-common.component-card title="Daftar Peserta">
    <div class="page-toolbar mb-4">
        <div class="filter-toolbar">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5 col-lg-4">
                    <label class="form-label mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama, NIK, NRK, SKPD..." class="form-control form-control-sm">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label mb-1">Status MCU</label>
                    <select name="status_mcu" class="form-select form-select-sm">
                        <option value="">Semua Status MCU</option>
                        <option value="Belum MCU" {{ request('status_mcu') === 'Belum MCU' ? 'selected' : '' }}>Belum MCU</option>
                        <option value="Sudah MCU" {{ request('status_mcu') === 'Sudah MCU' ? 'selected' : '' }}>Sudah MCU</option>
                        <option value="Ditolak" {{ request('status_mcu') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search me-1"></i> Cari</button>
                </div>
            </form>
        </div>

        <div class="page-toolbar-actions">
            <a href="{{ route('admin.participants.template') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-download me-1"></i> Download Template
            </a>
            <span class="text-muted small align-self-center d-none d-xl-inline" title="Kolom wajib saat import">
                Wajib: NIK, Nama
            </span>
            <form id="participants-import-form" action="{{ route('admin.participants.import') }}" method="POST" enctype="multipart/form-data" class="page-toolbar-import">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="form-control form-control-sm">
                <button type="submit" class="btn btn-success btn-sm"><i class="bx bx-import me-1"></i> Import</button>
            </form>
            <a href="{{ route('admin.participants.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus me-1"></i> Tambah Peserta
            </a>
            <button type="button" id="bulk-delete-btn" class="btn btn-outline-danger btn-sm" disabled>
                <i class="bx bx-trash me-1"></i> Bulk Hapus (<span id="selected-count">0</span>)
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="select-all" class="form-check-input" title="Pilih semua">
                    </th>
                    <th>NIK</th>
                    <th>NRK Pegawai</th>
                    <th>Nama Lengkap</th>
                    <th>SKPD</th>
                    <th>Status</th>
                    <th>Status MCU</th>
                    <th class="text-center" style="width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $p)
                    <tr>
                        <td>
                            <input type="checkbox" value="{{ $p->id }}" class="form-check-input participant-checkbox">
                        </td>
                            <td>{{ $p->nik_ktp }}</td>
                            <td>{{ $p->nrk_pegawai }}</td>
                            <td class="fw-medium">{{ $p->nama_lengkap }}</td>
                            <td>{{ $p->skpd }}</td>
                            <td>{{ $p->status_pegawai }}</td>
                            <td>
                                @php
                                    $badge = match($p->status_mcu) {
                                        'Sudah MCU' => 'bg-label-success',
                                        'Ditolak' => 'bg-label-danger',
                                        default => 'bg-label-warning',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ $p->status_mcu }}</span>
                            </td>
                            <td class="text-center">
                                <div class="table-action-group">
                                    <x-admin.action-badge type="view" :href="route('admin.participants.show', $p)" />
                                    <x-admin.action-badge type="edit" :href="route('admin.participants.edit', $p)" />
                                    <x-admin.action-badge type="delete" :href="route('admin.participants.destroy', $p)" confirm="Yakin hapus peserta ini?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-state">
                                <i class="bx bx-user-x d-block mb-2"></i>
                                Belum ada data peserta.
                                <div class="mt-2">
                                    <a href="{{ route('admin.participants.create') }}" class="btn btn-sm btn-primary">Tambah Peserta Pertama</a>
                                </div>
                            </td>
                        </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($participants->hasPages())
        <div class="mt-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <form method="GET" action="{{ route('admin.participants.index') }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="status_mcu" value="{{ request('status_mcu') }}">
                <label class="form-label mb-0 small">Tampilkan</label>
                <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="15" @selected((int) request('per_page', 15) === 15)>15</option>
                    <option value="50" @selected((int) request('per_page', 15) === 50)>50</option>
                    <option value="100" @selected((int) request('per_page', 15) === 100)>100</option>
                </select>
            </form>

            <div class="ms-auto">
                {{ $participants->links() }}
            </div>
        </div>
    @endif

    <form id="bulk-delete-form" method="POST" action="{{ route('admin.participants.bulk-destroy') }}" class="d-none">
        @csrf
        <input type="hidden" name="search" value="{{ request('search') }}">
        <input type="hidden" name="status_mcu" value="{{ request('status_mcu') }}">
    </form>

    <div class="modal fade" id="participants-importing-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sedang memproses import...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup" disabled></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-2">
                        <div class="spinner-border text-primary" role="status" aria-label="Loading"></div>
                        <div>
                            Mohon tunggu sampai proses selesai.
                            <div class="small text-muted mt-1">Jangan tutup halaman atau refresh.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-common.component-card>
@endsection

@push('scripts')
<script>
(function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.participant-checkbox');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const selectedCount = document.getElementById('selected-count');
    const bulkDeleteForm = document.getElementById('bulk-delete-form');

    function updateUI() {
        const checked = document.querySelectorAll('.participant-checkbox:checked');
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

    const importForm = document.getElementById('participants-import-form');
    importForm?.addEventListener('submit', function() {
        const modalEl = document.getElementById('participants-importing-modal');
        if (modalEl && window.bootstrap && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
            modal.show();
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Memproses...';
        }
    });

    bulkDeleteBtn?.addEventListener('click', function() {
        if (this.disabled) return;
        if (!confirm('Yakin hapus peserta yang dipilih?')) return;
        bulkDeleteForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
        document.querySelectorAll('.participant-checkbox:checked').forEach(function(cb) {
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
