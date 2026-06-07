@extends('layouts.sneat.app')

@section('title', 'Tambah Diagnosis')

@section('pageTitle', 'Tambah Diagnosis')

@section('content')

<x-common.component-card title="Form Diagnosis">
    <form method="POST" action="{{ route('admin.diagnoses.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label for="code" class="form-label">Kode</label>
                <input type="text" id="code" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" placeholder="Opsional">
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="name" class="form-label">Nama Diagnosis <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required class="form-control @error('name') is-invalid @enderror">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea id="description" name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <input type="hidden" name="is_active" value="0">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
            </div>
        </div>
        <x-admin.form-actions :cancelUrl="route('admin.diagnoses.index')" />
    </form>
</x-common.component-card>
@endsection
