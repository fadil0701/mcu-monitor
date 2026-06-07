@extends('layouts.sneat.app')

@section('title', 'Master Diagnosis')
@section('pageTitle', 'Master Diagnosis')

@section('content')
<x-common.component-card title="Daftar Diagnosis">
    <div class="page-toolbar mb-4">
        <div class="filter-toolbar">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5 col-lg-4">
                    <label class="form-label mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Kode / nama..." class="form-control form-control-sm">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label mb-1">Status</label>
                    <select name="is_active" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search me-1"></i> Cari</button>
                </div>
            </form>
        </div>
        <div class="page-toolbar-actions">
            <a href="{{ route('admin.diagnoses.template') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bx bx-download me-1"></i> Template Import
            </a>
            <form action="{{ route('admin.diagnoses.import') }}" method="POST" enctype="multipart/form-data" class="page-toolbar-import">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="form-control form-control-sm">
                <button type="submit" class="btn btn-success btn-sm"><i class="bx bx-import me-1"></i> Import</button>
            </form>
            <a href="{{ route('admin.diagnoses.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i> Tambah Diagnosis</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Deskripsi</th>
                    <th>Aktif</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($diagnoses as $d)
                    <tr>
                        <td>{{ $d->code ?? '-' }}</td>
                        <td class="fw-medium">{{ $d->name }}</td>
                        <td class="text-truncate" style="max-width: 200px;">{{ Str::limit($d->description, 40) }}</td>
                        <td>
                            <span class="badge {{ $d->is_active ? 'bg-label-success' : 'bg-label-danger' }}">{{ $d->is_active ? 'Ya' : 'Tidak' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="table-action-group">
                                <x-admin.action-badge type="edit" :href="route('admin.diagnoses.edit', $d)" />
                                <x-admin.action-badge type="delete" :href="route('admin.diagnoses.destroy', $d)" confirm="Yakin hapus diagnosis ini?" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data diagnosis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $diagnoses->links() }}</div>
</x-common.component-card>
@endsection
