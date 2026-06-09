@extends('layouts.sneat.app')

@section('title', 'Template Email Hasil MCU')

@section('pageTitle', 'Template Email Hasil MCU')

@section('content')

<x-common.component-card title="Template Email Hasil MCU">
    <p class="text-muted mb-4">
        Template fallback saat mengirim hasil MCU via email (plain text). Untuk template HTML lengkap, gunakan menu <a href="{{ route('admin.email-templates.index', ['type' => 'mcu_result']) }}">Email Templates</a>.
    </p>
    <p class="text-muted mb-4">
        Variabel: <code class="bg-light px-1 rounded">{participant_name}, {participant_email}, {tanggal_pemeriksaan}, {hasil_url}, {app_name}</code>
    </p>

    <form method="POST" action="{{ route('admin.settings.update-email-result-template') }}">
        @csrf
        <div class="row g-3">
            <div class="col-12">
                <label for="email_result_subject" class="form-label">Subject Email <span class="text-danger">*</span></label>
                <input type="text" id="email_result_subject" name="email_result_subject" value="{{ old('email_result_subject', $subject) }}" required class="form-control @error('email_result_subject') is-invalid @enderror" placeholder="Hasil MCU Anda Tersedia">
                @error('email_result_subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label for="email_result_template" class="form-label">Body Email (Plain Text) <span class="text-danger">*</span></label>
                <textarea id="email_result_template" name="email_result_template" rows="12" required class="form-control @error('email_result_template') is-invalid @enderror" placeholder="Kepada {participant_name}, ...">{{ old('email_result_template', $body) }}</textarea>
                @error('email_result_template')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
            <button type="submit" class="btn btn-primary">Simpan Template</button>
            <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary"><i class="bx bx-arrow-back me-1"></i> Kembali ke Pengaturan</a>
        </div>
    </form>
</x-common.component-card>
@endsection
