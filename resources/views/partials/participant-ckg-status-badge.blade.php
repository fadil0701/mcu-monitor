@props(['participant' => null])

@if($participant)
    <span
        class="badge {{ $participant->ckgStatusBadgeClass() }}"
        @if($participant->ckgStatusHint())
            title="{{ $participant->ckgStatusHint() }}"
        @endif
    >{{ $participant->ckgStatusLabel() }}</span>
@else
    <span class="badge bg-label-secondary">—</span>
@endif
