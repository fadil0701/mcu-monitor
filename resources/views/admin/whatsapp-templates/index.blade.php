@extends('layouts.sneat.app')

@section('title', 'Template WhatsApp')
@section('pageTitle', 'Template WhatsApp')

@section('content')
<x-common.component-card title="Template Undangan MCU" class="mb-4">
    <p class="text-muted mb-3">Variabel: <code class="bg-light px-1 rounded">{nama_lengkap}, {nik_ktp}, {tanggal_pemeriksaan}, {jam_pemeriksaan}, {lokasi_pemeriksaan}, {queue_number}, {skpd}, {ukpd}, {no_telp}, {email}</code></p>
    <form method="POST" action="{{ route('admin.whatsapp-templates.update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Template Undangan WhatsApp <span class="text-danger">*</span></label>
            <textarea name="invitation_template" rows="12" required class="form-control" placeholder="Contoh: Halo {nama_lengkap}, Anda diundang...">{{ old('invitation_template', $invitation_template) }}</textarea>
            @error('invitation_template')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-save me-1"></i> Simpan</button>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.whatsapp-templates.reset') }}" class="d-inline mt-2" onsubmit="return confirm('Reset template undangan ke default?');">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bx bx-reset me-1"></i> Reset Default</button>
    </form>
</x-common.component-card>

<x-common.component-card title="Template Hasil MCU">
    <p class="text-muted mb-3">Variabel: <code class="bg-light px-1 rounded">{participant_name}, {participant_email}, {participant_phone}, {tanggal_pemeriksaan}, {rekomendasi}, {hasil_url}, {app_name}</code></p>
    <form method="POST" action="{{ route('admin.whatsapp-templates.update-result') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Template Hasil MCU <span class="text-danger">*</span></label>
            <textarea name="result_template" rows="10" required class="form-control" placeholder="Halo {participant_name}, Hasil MCU Anda...">{{ old('result_template', $result_template ?? '') }}</textarea>
            @error('result_template')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-save me-1"></i> Simpan</button>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.whatsapp-templates.reset-result') }}" class="d-inline mt-2" onsubmit="return confirm('Reset template hasil MCU ke default?');">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bx bx-reset me-1"></i> Reset Default</button>
    </form>
</x-common.component-card>

<div class="mt-3">
    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i> Kembali ke Pengaturan</a>
</div>
@endsection
