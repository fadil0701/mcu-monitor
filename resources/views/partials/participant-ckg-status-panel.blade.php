@props(['participant' => null])

@if($participant)
    <div class="alert alert-light border py-2 mb-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <span class="text-muted small d-block">Status CKG tahun {{ now()->year }}</span>
                @include('partials.participant-ckg-status-badge', ['participant' => $participant])
            </div>
            @if($participant->ckgStatusHint())
                <small class="text-muted">{{ $participant->ckgStatusHint() }}</small>
            @endif
        </div>
    </div>
@endif
