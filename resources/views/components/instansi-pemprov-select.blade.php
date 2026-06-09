@props([
    'selectId' => 'skpd',
    'name' => 'skpd',
    'selected' => null,
    'required' => false,
    'hasError' => false,
    'label' => 'SKPD',
    'showLabel' => true,
])
@php
    $skpdVal = $selected ?? old($name);
    if ($skpdVal === '-') {
        $skpdVal = '';
    }
    $errorClass = $hasError ? ' is-invalid' : '';
    $instansiList = $instansiPemprov ?? \App\Support\InstansiPemprovDkiCatalog::optionsForForms();
@endphp
<div>
    @if($showLabel)
        <label for="{{ $selectId }}" class="form-label">
            {{ $label }}
            @if($required)<span class="text-danger">*</span>@endif
        </label>
    @endif
    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        data-instansi-searchable="1"
        @if($required) required @endif
        class="form-select{{ $errorClass }}"
    >
        <option value="" disabled @selected($skpdVal === null || $skpdVal === '')>Pilih instansi</option>
        @foreach($instansiList as $instansi)
            <option value="{{ $instansi }}" @selected($skpdVal === $instansi)>{{ $instansi }}</option>
        @endforeach
        @if($skpdVal && ! in_array($skpdVal, $instansiList, true))
            <option value="{{ $skpdVal }}" selected>{{ $skpdVal }}</option>
        @endif
    </select>
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

@once
    @push('scripts')
        <script src="{{ asset('assets/js/instansi-searchable-select.js') }}"></script>
    @endpush
@endonce
