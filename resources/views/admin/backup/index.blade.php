@extends('layouts.sneat.app')

@section('title', 'Backup Database')
@section('pageTitle', 'Backup Database')

@section('content')
    <p class="text-muted mb-4">
        Monitoring dan unduh arsip cadangan database. Restore hanya lewat SSH di server (Super Admin).
    </p>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1">Enkripsi</p>
                    <p class="h5 mb-0">{{ $encryptEnabled ? 'GPG AES256' : 'Nonaktif' }}</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1">Retensi</p>
                    <p class="h5 mb-0">{{ $retentionDays }} hari</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1">mysqldump</p>
                    <p class="h5 mb-0 {{ $mysqldumpAvailable ? 'text-success' : 'text-danger' }}">
                        {{ $mysqldumpAvailable ? 'Tersedia' : 'Tidak ada' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1">GPG</p>
                    <p class="h5 mb-0 {{ $gpgAvailable ? 'text-success' : 'text-danger' }}">
                        {{ $gpgAvailable ? 'Tersedia' : 'Tidak ada' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning mb-4">
        <h6 class="alert-heading mb-2">Restore database</h6>
        <p class="mb-2 small">Restore <strong>tidak</strong> dilakukan dari halaman ini. Gunakan SSH di server:</p>
        <pre class="bg-white rounded p-2 small mb-2">./deploy/restore-database.sh --verify storage/backups/database/NAMA_FILE.sql.gz.gpg
./deploy/restore-database.sh storage/backups/database/NAMA_FILE.sql.gz.gpg</pre>
        <p class="mb-0 small text-muted">Backup otomatis (cron): <code>docker compose exec -T app php artisan mcu:backup-database</code></p>
    </div>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <p class="text-muted small mb-0">
            Folder: <code>{{ $backupDirectory }}</code>
        </p>
        <form method="POST" action="{{ route('admin.backup.store') }}" onsubmit="return confirm('Jalankan backup database sekarang?');">
            @csrf
            <input type="hidden" name="confirm" value="ya">
            <button
                type="submit"
                class="btn btn-primary"
                @disabled(! $mysqldumpAvailable || ($encryptEnabled && ! $gpgAvailable))
            >
                <i class="bx bx-cloud-upload me-1"></i> Backup sekarang
            </button>
        </form>
    </div>

    @error('backup')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>File</th>
                        <th>Ukuran</th>
                        <th>Waktu</th>
                        <th>Enkripsi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                        <tr>
                            <td><code class="small">{{ $backup->filename }}</code></td>
                            <td>{{ $backup->humanSize() }}</td>
                            <td>{{ $backup->modifiedAt->format('d/m/Y H:i:s') }}</td>
                            <td>
                                @if($backup->encrypted)
                                    <span class="badge bg-label-success">GPG</span>
                                @else
                                    <span class="badge bg-label-secondary">Plain</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.backup.download', ['filename' => $backup->filename]) }}" class="btn btn-sm btn-outline-primary">
                                    Unduh
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                Belum ada file backup. Jalankan backup manual atau tunggu cron.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(count($logTail) > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Log backup terakhir</h6>
            </div>
            <div class="card-body">
                <pre class="bg-light rounded p-3 small mb-0" style="max-height: 12rem; overflow: auto;">@foreach($logTail as $line){{ $line }}
@endforeach</pre>
            </div>
        </div>
    @endif
@endsection
