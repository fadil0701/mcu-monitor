@extends('layouts.sneat.app')

@section('title', 'Template WhatsApp')
@section('pageTitle', 'Template WhatsApp')

@section('content')
@php
    $providerLabels = [
        'fonnte' => 'Fonnte',
        'wablas' => 'Wablas',
        'meta' => 'Meta',
        'apico' => 'Api.co.id Chat Gateway',
    ];
    $providerLabel = $providerLabels[$whatsappProvider] ?? $whatsappProvider;
@endphp

<div class="alert alert-info py-2 mb-4">
    <strong>Provider aktif:</strong> {{ $providerLabel }}.
    @if($useMetaFormat)
        Format variabel wajib <code>@{{1}}</code>, <code>@{{2}}</code>, …
        @if($apicoInvitationTemplateName !== '' || $apicoResultTemplateName !== '')
            <br><strong>Api.co.id:</strong> jika <em>Nama Template WA</em> sudah diisi di Pengaturan
            @if($apicoInvitationTemplateName !== '') (<code>{{ $apicoInvitationTemplateName }}</code>)@endif,
            pesan terkirim mengikuti template <strong>Meta yang APPROVED</strong>, bukan teks di bawah ini.
        @endif
    @else
        Saat ini format lama <code>{nama_lengkap}</code>. Untuk Api.co.id, ubah dulu
        <strong>Pengaturan → WhatsApp → Penyedia = Api.co.id</strong>, lalu <strong>Simpan</strong> dan muat ulang halaman ini.
    @endif
</div>

<x-common.component-card title="Template Undangan MCU" class="mb-4">
    @if($useMetaFormat)
        <div class="alert alert-warning py-2 mb-3">
            <strong>Format Meta / Api.co.id:</strong> gunakan <code>@{{1}}</code>, <code>@{{2}}</code>, dst. (dua kurung kurawal).
            Meta akan <strong>menolak</strong> template jika masih memakai <code>{nama_lengkap}</code>.
        </div>
        <p class="text-muted mb-3">
            Pemetaan variabel:
            @foreach($invitationLegend as $num => $label)
                <code class="bg-light px-1 rounded">{{ '{'.'{'. $num .'}'.'}' }}</code> = {{ $label }}@if(!$loop->last), @endif
            @endforeach
        </p>
    @else
        <p class="text-muted mb-3">Variabel: <code class="bg-light px-1 rounded">{nama_lengkap}, {nik_ktp}, {tanggal_pemeriksaan}, {jam_pemeriksaan}, {lokasi_pemeriksaan}, {queue_number}, {skpd}, {ukpd}, {no_telp}, {email}</code></p>
    @endif
    <form method="POST" action="{{ route('admin.whatsapp-templates.update') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Template Undangan WhatsApp <span class="text-danger">*</span></label>
            <textarea name="invitation_template" rows="12" required class="form-control" placeholder="Halo @{{1}}, Anda diundang...">{{ old('invitation_template', $invitation_template) }}</textarea>
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
    @if($useMetaFormat)
        <div class="alert alert-warning py-2 mb-3">
            Gunakan format <code>@{{1}}</code>, <code>@{{2}}</code>, dst. untuk template hasil MCU di Meta.
        </div>
        <p class="text-muted mb-3">
            Pemetaan variabel:
            @foreach($resultLegend as $num => $label)
                <code class="bg-light px-1 rounded">{{ '{'.'{'. $num .'}'.'}' }}</code> = {{ $label }}@if(!$loop->last), @endif
            @endforeach
        </p>
    @else
        <p class="text-muted mb-3">Variabel: <code class="bg-light px-1 rounded">{participant_name}, {participant_email}, {participant_phone}, {tanggal_pemeriksaan}, {rekomendasi}, {hasil_url}, {app_name}</code></p>
    @endif
    <form method="POST" action="{{ route('admin.whatsapp-templates.update-result') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Template Hasil MCU <span class="text-danger">*</span></label>
            <textarea name="result_template" rows="10" required class="form-control" placeholder="Halo @{{1}}, Hasil MCU...">{{ old('result_template', $result_template ?? '') }}</textarea>
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
