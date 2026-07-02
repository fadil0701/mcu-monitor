@props(['participant' => null])

<div class="col-12">
    <label class="form-label">Status CKG tahun {{ now()->year }}</label>
    <div id="participant-ckg-status-field" class="border rounded bg-light px-3 py-2">
        @if($participant)
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    @include('partials.participant-ckg-status-badge', ['participant' => $participant])
                </div>
                @if($participant->ckgStatusHint())
                    <small class="text-muted">{{ $participant->ckgStatusHint() }}</small>
                @endif
            </div>
        @else
            <span class="text-muted">Pilih peserta untuk melihat status CKG.</span>
        @endif
    </div>
</div>
