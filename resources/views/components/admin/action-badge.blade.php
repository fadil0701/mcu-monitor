@props(['type' => 'view', 'href' => '#', 'confirm' => null, 'label' => null])

@php
    $config = match($type) {
        'view' => ['class' => 'btn btn-sm btn-icon btn-outline-info', 'icon' => 'bx-show', 'title' => 'Lihat'],
        'edit' => ['class' => 'btn btn-sm btn-icon btn-outline-primary', 'icon' => 'bx-edit', 'title' => 'Edit'],
        'delete' => ['class' => 'btn btn-sm btn-icon btn-outline-danger', 'icon' => 'bx-trash', 'title' => 'Hapus'],
        default => ['class' => 'btn btn-sm btn-icon btn-outline-secondary', 'icon' => 'bx-link', 'title' => 'Aksi'],
    };
    $title = $label ?? $config['title'];
@endphp

@if($type === 'delete')
    <form action="{{ $href }}" method="POST" class="d-inline-flex m-0" onsubmit="return confirm('{{ $confirm ?? 'Yakin hapus data ini?' }}')">
        @csrf
        @method('DELETE')
        <button type="submit" class="{{ $config['class'] }}" title="{{ $title }}" aria-label="{{ $title }}">
            <i class="bx {{ $config['icon'] }}"></i>
        </button>
    </form>
@else
    <a href="{{ $href }}" class="{{ $config['class'] }}" title="{{ $title }}" aria-label="{{ $title }}">
        <i class="bx {{ $config['icon'] }}"></i>
    </a>
@endif
