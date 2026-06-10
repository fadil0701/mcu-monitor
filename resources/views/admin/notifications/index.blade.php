@extends('layouts.sneat.app')

@section('title', 'Notifikasi Admin')
@section('pageTitle', 'Notifikasi Admin')

@section('content')
<x-common.component-card title="Daftar Notifikasi">
    @php $user = auth()->user(); @endphp
    @if($user->unreadNotifications->count() > 0)
        <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}" class="mb-4">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-check-double me-1"></i> Tandai Semua Dibaca</button>
        </form>
    @endif

    <div class="d-flex flex-column gap-2">
        @forelse($notifications as $n)
            <div class="card {{ $n->read_at ? 'bg-light' : 'border-primary border-start border-3' }}">
                <div class="card-body py-3">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="flex-grow-1 min-w-0">
                            @if(!empty($n->data['title']))
                                <p class="mb-1 fw-semibold {{ $n->read_at ? 'text-muted' : 'text-primary' }}">{{ $n->data['title'] }}</p>
                            @endif
                            <p class="mb-1">{{ $n->data['message'] ?? class_basename($n->type) }}</p>
                            <p class="text-muted small mb-0">{{ $n->created_at->diffForHumans() }}</p>
                        </div>
                        @if(is_null($n->read_at))
                            <form method="POST" action="{{ route('admin.notifications.mark-read', $n->id) }}" class="flex-shrink-0">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Tandai dibaca</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-muted py-4 mb-0">Tidak ada notifikasi.</p>
        @endforelse
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
</x-common.component-card>
@endsection
