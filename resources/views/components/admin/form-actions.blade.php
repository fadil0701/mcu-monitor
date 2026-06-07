@props(['cancelUrl', 'submitLabel' => 'Simpan'])

<div class="d-flex flex-wrap gap-2 mt-4 pt-2">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Batal</a>
</div>
