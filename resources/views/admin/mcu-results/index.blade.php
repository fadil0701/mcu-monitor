@extends('layouts.sneat.app')

@section('title', 'Hasil MCU')
@section('pageTitle', 'Hasil MCU')

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<x-common.component-card title="Daftar Hasil MCU">
    <div class="page-toolbar mb-4">
        <div class="filter-toolbar">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6 col-lg-5">
                    <label class="form-label mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / NIK peserta..." class="form-control form-control-sm">
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search me-1"></i> Cari</button>
                </div>
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
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $r)
                    <tr>
                        <td>{{ $r->tanggal_pemeriksaan?->format('d/m/Y') }}</td>
                        <td class="fw-medium">{{ $r->participant?->nama_lengkap ?? $r->participant_id }}</td>
                        <td>
                            <span class="badge {{ $r->is_published ? 'bg-label-success' : 'bg-label-warning' }}">{{ $r->is_published ? 'Ya' : 'Tidak' }}</span>
                        </td>
                        <td>
                            <div class="table-action-group">
                                @if($r->participant?->email)
                                <form method="POST" action="{{ route('admin.mcu-results.send-email', $r) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Kirim Email"><i class="bx bx-envelope"></i></button>
                                </form>
                                @endif
                                @if($r->participant?->no_telp)
                                <form method="POST" action="{{ route('admin.mcu-results.send-whatsapp', $r) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Kirim WhatsApp"><i class="bx bxl-whatsapp"></i></button>
                                </form>
                                @endif
                                <x-admin.action-badge type="edit" :href="route('admin.mcu-results.edit', $r)" />
                                <x-admin.action-badge type="delete" :href="route('admin.mcu-results.destroy', $r)" confirm="Yakin hapus hasil MCU ini?" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada hasil MCU.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $results->links() }}</div>
</x-common.component-card>
@endsection
