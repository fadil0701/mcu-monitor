@extends('layouts.sneat.app')

@section('title', 'Edit User')

@section('pageTitle', 'Edit User')

@section('content')

<x-common.component-card title="Form User">
    <form method="POST" action="{{ route('admin.users.update.post', $user) }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nama *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="form-control @error('name') is-invalid @enderror">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required class="form-control @error('email') is-invalid @enderror">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label">Password (kosongkan jika tidak diubah)</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="role" class="form-label">Role *</label>
                <select id="role" name="role" required class="form-select @error('role') is-invalid @enderror">
                    <option value="peserta" {{ old('role', $user->role) === 'peserta' ? 'selected' : '' }}>Peserta</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                </select>
                @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
            </div>
        </div>
        <x-admin.form-actions :cancelUrl="route('admin.users.index')" />
    </form>
</x-common.component-card>
@endsection
