@extends('layouts.sneat.app')

@section('title', 'Manajemen User')
@section('pageTitle', 'Manajemen User')

@section('content')
<x-common.component-card title="Daftar User">
    <div class="page-toolbar mb-4">
        <div class="filter-toolbar">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5 col-lg-4">
                    <label class="form-label mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / email..." class="form-control form-control-sm">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label mb-1">Role</label>
                    <select name="role" class="form-select form-select-sm">
                        <option value="">Semua Role</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="peserta" {{ request('role') === 'peserta' ? 'selected' : '' }}>Peserta</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search me-1"></i> Cari</button>
                </div>
            </form>
        </div>

        <div class="page-toolbar-actions">
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <i class="bx bx-plus me-1"></i> Tambah User
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td class="fw-medium">{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $u->role ?? 'peserta')) }}</td>
                        <td>
                            <span class="badge {{ $u->is_active ? 'bg-label-success' : 'bg-label-danger' }}">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="table-action-group">
                                <x-admin.action-badge type="edit" :href="route('admin.users.edit', $u)" />
                                <x-admin.action-badge type="delete" :href="route('admin.users.destroy', $u)" confirm="Yakin hapus pengguna ini?" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="bx bx-user d-block mb-2"></i>
                            Belum ada user.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="mt-3">{{ $users->links() }}</div>
    @endif
</x-common.component-card>
@endsection
