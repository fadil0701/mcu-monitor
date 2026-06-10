@extends('layouts.sneat.app')

@section('title', 'Email Templates')
@section('pageTitle', 'Email Templates')

@section('content')
<x-common.component-card title="Daftar Email Template">
    <div class="page-toolbar mb-4">
        <div class="filter-toolbar">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label mb-1">Tipe Template</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua Tipe</option>
                        <option value="mcu_invitation" {{ request('type') === 'mcu_invitation' ? 'selected' : '' }}>MCU Invitation</option>
                        <option value="reminder" {{ request('type') === 'reminder' ? 'selected' : '' }}>Reminder</option>
                        <option value="notification" {{ request('type') === 'notification' ? 'selected' : '' }}>Notification</option>
                        <option value="mcu_result" {{ request('type') === 'mcu_result' ? 'selected' : '' }}>Hasil MCU</option>
                        <option value="custom" {{ request('type') === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-filter-alt me-1"></i> Filter</button>
                </div>
            </form>
        </div>
        <div class="page-toolbar-actions">
            <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i> Tambah Template</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Subject</th>
                    <th>Aktif</th>
                    <th>Default</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $t)
                    <tr>
                        <td class="fw-medium">{{ $t->name }}</td>
                        <td>{{ $t->type }}</td>
                        <td class="text-truncate" style="max-width: 200px;">{{ Str::limit($t->subject, 40) }}</td>
                        <td>
                            <span class="badge {{ $t->is_active ? 'bg-label-success' : 'bg-label-danger' }}">{{ $t->is_active ? 'Ya' : 'Tidak' }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $t->is_default ? 'bg-label-success' : 'bg-label-warning' }}">{{ $t->is_default ? 'Ya' : 'Tidak' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="table-action-group">
                                <x-admin.action-badge type="edit" :href="route('admin.email-templates.edit', $t)" />
                                <x-admin.action-badge type="delete" :href="route('admin.email-templates.destroy', $t)" confirm="Yakin hapus template ini?" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada email template.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $templates->links() }}</div>
</x-common.component-card>
@endsection
