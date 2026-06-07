@extends('layouts.sneat.app')

@section('title', 'Dokter Spesialis')
@section('pageTitle', 'Dokter Spesialis')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<x-common.component-card title="Daftar Dokter Spesialis">
    <div class="page-toolbar mb-4">
        <div class="filter-toolbar">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5 col-lg-4">
                    <label class="form-label mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / spesialisasi..." class="form-control form-control-sm">
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
            <a href="{{ route('admin.specialist-doctors.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i> Tambah Dokter</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Spesialisasi</th>
                    <th>Deskripsi</th>
                    <th>Aktif</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($doctors as $doc)
                    <tr>
                        <td class="fw-medium">{{ $doc->name }}</td>
                        <td>{{ $doc->specialty ?? '-' }}</td>
                        <td class="text-truncate" style="max-width: 200px;">{{ Str::limit($doc->description, 40) }}</td>
                        <td>
                            <span class="badge {{ $doc->is_active ? 'bg-label-success' : 'bg-label-danger' }}">{{ $doc->is_active ? 'Ya' : 'Tidak' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="table-action-group">
                                <x-admin.action-badge type="edit" :href="route('admin.specialist-doctors.edit', $doc)" />
                                <x-admin.action-badge type="delete" :href="route('admin.specialist-doctors.destroy', $doc)" confirm="Yakin hapus data ini?" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data dokter spesialis.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $doctors->links() }}</div>
</x-common.component-card>
@endsection
