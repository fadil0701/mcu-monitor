@props(['name' => 'pendidikan_terakhir', 'value' => null, 'required' => true])

@php
    $selectClass = 'form-select'.($errors->has($name) ? ' is-invalid' : '');
@endphp

<select
    id="{{ $name }}"
    name="{{ $name }}"
    @if($required) required @endif
    {{ $attributes->merge(['class' => $selectClass]) }}
>
    <option value="" disabled {{ blank($value) ? 'selected' : '' }}>Pilih pendidikan terakhir</option>
    @foreach(\App\Support\ParticipantEducation::levels() as $level)
        <option value="{{ $level }}" {{ old($name, $value) === $level ? 'selected' : '' }}>{{ $level }}</option>
    @endforeach
</select>
