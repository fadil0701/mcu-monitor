<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCkgBridgeConfigRequest;
use App\Models\CkgBridgeConfig;
use App\Models\CkgBridgeSyncLog;
use App\Services\CkgParticipantSyncService;
use App\Support\CkgBridge\CkgBridgeConfigPersister;
use App\Support\CkgBridge\CkgBridgeSettings;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CkgBridgeMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $config = CkgBridgeConfig::query()->firstOrCreate(
            ['name' => 'CKG Bridge'],
            [
                'base_url' => config('ckg_bridge.base_url', 'http://127.0.0.1:9006'),
                'api_key_header' => 'X-Mcu-Api-Key',
                'per_page' => 100,
                'timeout_seconds' => 60,
                'is_active' => false,
            ]
        );

        $logs = CkgBridgeSyncLog::query()
            ->with('triggeredBy:id,name,email')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $lastSuccess = CkgBridgeSyncLog::query()
            ->where('status', 'success')
            ->latest('finished_at')
            ->first();

        $effectiveBaseUrl = CkgBridgeSettings::baseUrl();
        $configUsesDatabase = (bool) $config->is_active;
        $hasEffectiveApiKey = CkgBridgeSettings::apiKey() !== '';
        $displayBaseUrl = $config->base_url ?: config('ckg_bridge.base_url', 'http://127.0.0.1:9006');
        $urlUsesHttps = str_starts_with(strtolower($displayBaseUrl), 'https://');

        return view('admin.ckg-bridge.index', compact(
            'config',
            'logs',
            'lastSuccess',
            'effectiveBaseUrl',
            'configUsesDatabase',
            'hasEffectiveApiKey',
            'displayBaseUrl',
            'urlUsesHttps',
        ));
    }

    public function updateConfig(UpdateCkgBridgeConfigRequest $request, CkgBridgeConfigPersister $persister): RedirectResponse
    {
        $persister->persist($request);

        return redirect()
            ->route('admin.ckg-bridge.index')
            ->with('success', 'Konfigurasi bridge CKG berhasil disimpan.');
    }

    public function testConnection(CkgParticipantSyncService $syncService): RedirectResponse
    {
        try {
            $result = $syncService->testConnection();
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.ckg-bridge.index')
                ->withErrors(['connection' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.ckg-bridge.index')
            ->with('success', 'Koneksi CKG OK. Peserta eligible: '.($result['total_eligible'] ?? 0));
    }

    public function runSync(Request $request, CkgParticipantSyncService $syncService): RedirectResponse
    {
        $since = $request->filled('since')
            ? Carbon::parse($request->string('since'))
            : null;

        try {
            $stats = $syncService->syncWithLog(
                $since,
                'manual',
                $request->user()?->id,
            );
        } catch (\Throwable $exception) {
            return redirect()
                ->route('admin.ckg-bridge.index')
                ->withErrors(['sync' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.ckg-bridge.index')
            ->with('success', sprintf(
                'Sinkron selesai — insert: %d, update: %d, skip: %d.',
                $stats['inserted'],
                $stats['updated'],
                $stats['skipped'],
            ));
    }
}
