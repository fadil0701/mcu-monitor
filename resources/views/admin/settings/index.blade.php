@extends('layouts.sneat.app')

@section('title', 'Pengaturan')
@section('pageTitle', 'Pengaturan')

@section('content')
<div class="card mb-4">
    <div class="card-body pb-0">
        <p class="text-muted mb-3">Kelola konfigurasi sistem monitoring MCU. Pilih kategori di bawah, ubah nilainya, lalu klik Simpan.</p>
        <ul class="nav nav-tabs nav-tabs-settings" role="tablist">
            @foreach($sections as $sectionKey => $section)
                <li class="nav-item" role="presentation">
                    <a
                        class="nav-link {{ $activeTab === $sectionKey ? 'active' : '' }}"
                        href="{{ route('admin.settings.index', ['tab' => $sectionKey]) }}"
                        role="tab"
                    >
                        <i class="bx {{ $section['icon'] ?? 'bx-cog' }} me-1"></i>
                        {{ $section['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    @foreach($sections as $sectionKey => $section)
        @if($activeTab === $sectionKey)
            <div class="card-body border-top">
                @if(!empty($section['description']))
                    <p class="text-muted mb-4">{{ $section['description'] }}</p>
                @endif

                <form method="POST" action="{{ route('admin.settings.update-section', $sectionKey) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        @foreach($section['fields'] as $fieldKey => $field)
                            <div class="{{ in_array($field['type'] ?? 'text', ['textarea']) ? 'col-12' : 'col-md-6' }}">
                                <label for="field_{{ $fieldKey }}" class="form-label">
                                    {{ $field['label'] }}
                                    @if(str_contains($field['rules'] ?? '', 'required'))
                                        <span class="text-danger">*</span>
                                    @endif
                                </label>

                                @php $fieldType = $field['type'] ?? 'text'; @endphp

                                @if($fieldType === 'textarea')
                                    <textarea
                                        id="field_{{ $fieldKey }}"
                                        name="{{ $fieldKey }}"
                                        rows="{{ $field['rows'] ?? 6 }}"
                                        class="form-control font-monospace @error($fieldKey) is-invalid @enderror"
                                        @if(str_contains($field['rules'] ?? '', 'required')) required @endif
                                    >{{ old($fieldKey, $values[$fieldKey] ?? '') }}</textarea>
                                @elseif($fieldType === 'select')
                                    <select
                                        id="field_{{ $fieldKey }}"
                                        name="{{ $fieldKey }}"
                                        class="form-select @error($fieldKey) is-invalid @enderror"
                                        @if(str_contains($field['rules'] ?? '', 'required')) required @endif
                                    >
                                        @foreach($field['options'] ?? [] as $optValue => $optLabel)
                                            <option value="{{ $optValue }}" {{ old($fieldKey, $values[$fieldKey] ?? '') == $optValue ? 'selected' : '' }}>
                                                {{ $optLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif($fieldType === 'password')
                                    <input
                                        type="password"
                                        id="field_{{ $fieldKey }}"
                                        name="{{ $fieldKey }}"
                                        value="{{ old($fieldKey) }}"
                                        class="form-control @error($fieldKey) is-invalid @enderror"
                                        placeholder="{{ !empty($secretConfigured[$fieldKey] ?? false) ? 'Kosongkan jika tidak diubah' : '••••••••' }}"
                                        autocomplete="off"
                                    >
                                    @if(!empty($secretConfigured[$fieldKey] ?? false))
                                        <div class="form-text text-success">
                                            <i class="bx bx-check-circle"></i> Sudah tersimpan di database. Isi ulang hanya jika ingin mengganti.
                                        </div>
                                    @endif
                                @else
                                    <input
                                        type="{{ $fieldType === 'number' ? 'number' : ($fieldType === 'email' ? 'email' : 'text') }}"
                                        id="field_{{ $fieldKey }}"
                                        name="{{ $fieldKey }}"
                                        value="{{ old($fieldKey, $values[$fieldKey] ?? '') }}"
                                        class="form-control @error($fieldKey) is-invalid @enderror"
                                        placeholder="{{ $field['placeholder'] ?? '' }}"
                                        @if(str_contains($field['rules'] ?? '', 'required')) required @endif
                                    >
                                @endif

                                @error($fieldKey)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if(!empty($field['help']))
                                    <div class="form-text">{{ $field['help'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex gap-2 mt-4 pt-2 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Simpan {{ $section['label'] }}
                        </button>
                    </div>
                </form>
            </div>
        @endif
    @endforeach
</div>

@if(count($links) > 0)
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bx bx-link-external me-2"></i>Pengaturan Template Lanjutan</h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">Fitur template khusus untuk super admin. Gunakan hanya jika perlu kustomisasi lebih detail.</p>
            <div class="row g-3">
                @foreach($links as $link)
                    <div class="col-md-4">
                        <a href="{{ route($link['route']) }}" class="card h-100 text-body text-decoration-none report-card">
                            <div class="card-body d-flex align-items-start gap-3">
                                <span class="avatar flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="bx {{ $link['icon'] }}"></i>
                                    </span>
                                </span>
                                <div>
                                    <h6 class="mb-1">{{ $link['label'] }}</h6>
                                    <small class="text-muted">{{ $link['description'] }}</small>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
@endsection

@push('page-css')
<style>
.nav-tabs-settings .nav-link {
    color: #697a8d;
    border: none;
    border-bottom: 2px solid transparent;
    padding-bottom: 0.75rem;
}
.nav-tabs-settings .nav-link.active {
    color: #696cff;
    border-bottom-color: #696cff;
    background: transparent;
}
</style>
@endpush
