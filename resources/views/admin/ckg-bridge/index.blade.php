@extends('layouts.sneat.app')

@section('title', 'Integrasi CKG')
@section('pageTitle', 'Integrasi Portal CKG')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Konfigurasi CKG</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.ckg-bridge.config.update') }}" class="mb-0">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label" for="ckg_base_url">URL Portal CKG</label>
                        <input type="text" class="form-control font-monospace" id="ckg_base_url" name="base_url" required
                               inputmode="url" spellcheck="false" autocomplete="off"
                               value="{{ old('base_url', $config->base_url) }}"
                               placeholder="http://10.15.101.117:9006">
                        <div class="form-text">
                            <strong class="text-danger">Jangan <code>127.0.0.1</code></strong> dan jangan <code>host.docker.internal</code> (terblokir proxy FortiGate).<br>
                            <strong>VM (disarankan):</strong> <code>http://10.15.101.117:9006</code> atau <code>http://web</code> (jaringan Docker bersama CKG).<br>
                            Wajib <code>http://</code>, bukan <code>https://</code>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="ckg_api_key">API key (dari menu Bridging MCU di CKG)</label>
                        <input type="password" class="form-control" id="ckg_api_key" name="api_key" autocomplete="new-password"
                               placeholder="{{ filled($config->api_key) ? '•••••••• (kosongkan jika tidak diubah)' : 'Tempel API key dari CKG' }}">
                        <div class="form-text">
                            Status:
                            @if(filled($config->api_key))
                                <span class="text-success fw-semibold">Terisi</span>
                            @else
                                <span class="text-warning fw-semibold">Belum diisi</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="ckg_api_key_header">Header API key</label>
                        <input type="hidden" name="api_key_header" value="X-Mcu-Api-Key">
                        <input type="text" class="form-control" id="ckg_api_key_header" readonly
                               value="X-Mcu-Api-Key">
                        <div class="form-text">Harus sama dengan header di menu Bridging MCU CKG.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" for="ckg_per_page">Per halaman</label>
                            <input type="number" class="form-control" id="ckg_per_page" name="per_page" min="1" max="500"
                                   value="{{ old('per_page', $config->per_page) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="ckg_timeout">Timeout (detik)</label>
                            <input type="number" class="form-control" id="ckg_timeout" name="timeout_seconds" min="10" max="120"
                                   value="{{ old('timeout_seconds', $config->timeout_seconds) }}">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="ckg_is_active"
                               @checked(old('is_active', $config->is_active))>
                        <label class="form-check-label" for="ckg_is_active">Aktifkan konfigurasi database (override .env)</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Simpan konfigurasi</button>
                </form>

                <hr>

                <form method="POST" action="{{ route('admin.ckg-bridge.test') }}" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary w-100">Tes koneksi CKG</button>
                </form>

                <form method="POST" action="{{ route('admin.ckg-bridge.sync') }}">
                    @csrf
                    <button type="submit" class="btn btn-success w-100">Jalankan sinkron sekarang</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <small class="text-muted d-block mb-1">Konfigurasi aktif (dipakai saat tes/sync)</small>
                <code class="d-block small mb-2">{{ $effectiveBaseUrl }}/api/bridge/mcu/health</code>
                <div class="small">
                    Sumber:
                    @if($configUsesDatabase)
                        <span class="badge bg-label-primary">database</span>
                    @else
                        <span class="badge bg-label-secondary">.env</span>
                    @endif
                    · API key:
                    @if($hasEffectiveApiKey)
                        <span class="text-success fw-semibold">terisi</span>
                    @else
                        <span class="text-danger fw-semibold">kosong</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Sinkron terakhir sukses</small>
                        <strong>{{ $lastSuccess?->finished_at?->format('d/m/Y H:i') ?? '—' }}</strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Hasil terakhir</small>
                        @if($lastSuccess)
                            <strong>+{{ $lastSuccess->inserted }} / ~{{ $lastSuccess->updated }} update</strong>
                        @else
                            <strong>—</strong>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Log Sinkronisasi</h5>
                <form method="GET" action="{{ route('admin.ckg-bridge.index') }}">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua status</option>
                        @foreach(['running', 'success', 'failed'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Trigger</th>
                            <th>Status</th>
                            <th>Insert</th>
                            <th>Update</th>
                            <th>Skip</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-nowrap small">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="small">
                                    {{ $log->trigger }}
                                    @if($log->triggeredBy)
                                        <br><span class="text-muted">{{ $log->triggeredBy->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badge = match($log->status) {
                                            'success' => 'bg-label-success',
                                            'failed' => 'bg-label-danger',
                                            default => 'bg-label-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $log->status }}</span>
                                </td>
                                <td>{{ $log->inserted }}</td>
                                <td>{{ $log->updated }}</td>
                                <td>{{ $log->skipped }}</td>
                                <td class="small text-muted">{{ Str::limit($log->error_message, 50) ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada log sinkronisasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="card-footer">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
