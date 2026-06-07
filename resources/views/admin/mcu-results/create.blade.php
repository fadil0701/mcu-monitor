@extends('layouts.sneat.app')

@section('title', 'Tambah Hasil MCU')

@section('pageTitle', 'Tambah Hasil MCU')

@section('content')

<x-common.component-card title="Form Hasil MCU">
    <form method="POST" action="{{ route('admin.mcu-results.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-3">
            <div class="col-12">
                <x-form.searchable-select
                    name="participant_id"
                    label="Peserta *"
                    :options="$participants"
                    value-key="id"
                    label-key="nama_lengkap"
                    sublabel-key="nik_ktp"
                    placeholder="-- Pilih Peserta --"
                    :value="old('participant_id', $participantId ?? '')"
                    :required="true"
                />
            </div>
            <div class="col-md-6">
                <label for="tanggal_pemeriksaan" class="form-label">Tanggal Pemeriksaan *</label>
                <input type="date" id="tanggal_pemeriksaan" name="tanggal_pemeriksaan" value="{{ old('tanggal_pemeriksaan', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required class="form-control @error('tanggal_pemeriksaan') is-invalid @enderror">
                @error('tanggal_pemeriksaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="file_hasil" class="form-label">Dokumen Hasil MCU *</label>
                <input type="file" id="file_hasil" name="file_hasil[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.bmp,.tiff" class="form-control @error('file_hasil') is-invalid @enderror @error('file_hasil.*') is-invalid @enderror">
                <div class="form-text">PDF, DOC, DOCX, JPG, PNG. Maks 10MB per file.</div>
                @error('file_hasil')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('file_hasil.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="is_published" value="1" id="is_published" {{ old('is_published') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_published">Publikasikan ke peserta</label>
                </div>
            </div>
        </div>
        <x-admin.form-actions :cancelUrl="route('admin.mcu-results.index')" />
    </form>
</x-common.component-card>
@endsection
