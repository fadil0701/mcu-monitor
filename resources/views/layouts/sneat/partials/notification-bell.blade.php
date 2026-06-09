@php
    $user = auth()->user();
    $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
    $recentNotifications = $user ? $user->notifications()->limit(5)->get() : collect();
    $notificationsUrl = $user && $user->isAdmin()
        ? route('admin.notifications.index')
        : route('client.notifications.index');
@endphp

<li class="nav-item navbar-dropdown dropdown me-3">
    <a class="nav-link dropdown-toggle hide-arrow position-relative" href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="Notifikasi">
        <i class="bx bx-bell bx-sm"></i>
        @if($unreadCount > 0)
            <span class="badge bg-danger badge-notifications rounded-pill">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </a>
    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 22rem;">
        <li class="dropdown-header d-flex align-items-center justify-content-between">
            <span class="fw-semibold">Notifikasi</span>
            @if($unreadCount > 0)
                <span class="badge bg-label-danger">{{ $unreadCount }} baru</span>
            @endif
        </li>
        <li><hr class="dropdown-divider"></li>
        @forelse($recentNotifications as $notification)
            <li>
                <a class="dropdown-item py-2 {{ $notification->read_at ? '' : 'bg-label-primary' }}" href="{{ $notificationsUrl }}">
                    <div class="fw-medium small">{{ $notification->data['title'] ?? 'Notifikasi' }}</div>
                    <div class="text-muted small text-truncate">{{ $notification->data['message'] ?? class_basename($notification->type) }}</div>
                    <div class="text-muted" style="font-size: 0.7rem;">{{ $notification->created_at->diffForHumans() }}</div>
                </a>
            </li>
        @empty
            <li><span class="dropdown-item-text text-muted small">Belum ada notifikasi.</span></li>
        @endforelse
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item text-center small fw-semibold" href="{{ $notificationsUrl }}">Lihat semua</a>
        </li>
    </ul>
</li>
