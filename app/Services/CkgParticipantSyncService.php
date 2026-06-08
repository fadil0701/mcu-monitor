<?php

namespace App\Services;

use App\Models\CkgBridgeSyncLog;
use App\Models\Participant;
use App\Support\CkgBridge\CkgBridgeSettings;
use App\Support\CkgBridge\CkgParticipantMapper;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CkgParticipantSyncService
{
    public function __construct(
        private readonly CkgParticipantMapper $mapper = new CkgParticipantMapper,
    ) {}

    /**
     * @return array{inserted: int, updated: int, skipped: int, pages: int, log_id: int}
     */
    public function syncWithLog(
        ?Carbon $since = null,
        string $trigger = 'command',
        ?int $userId = null,
    ): array {
        $log = CkgBridgeSyncLog::query()->create([
            'status' => 'running',
            'trigger' => $trigger,
            'triggered_by_user_id' => $userId,
            'since' => $since,
            'started_at' => now(),
        ]);

        try {
            $stats = $this->sync($since);
            $log->update([
                'status' => 'success',
                'inserted' => $stats['inserted'],
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'pages' => $stats['pages'],
                'finished_at' => now(),
            ]);

            return array_merge($stats, ['log_id' => $log->id]);
        } catch (Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return array{inserted: int, updated: int, skipped: int, pages: int}
     */
    public function sync(?Carbon $since = null): array
    {
        $apiKey = CkgBridgeSettings::apiKey();
        if ($apiKey === '') {
            throw new RuntimeException('API key bridge CKG belum diisi. Atur di menu Integrasi CKG atau .env (CKG_API_KEY).');
        }

        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'pages' => 0,
        ];

        $page = 1;
        $lastPage = 1;

        do {
            $response = $this->client()
                ->get($this->endpoint('/api/bridge/mcu/participants'), array_filter([
                    'page' => $page,
                    'per_page' => CkgBridgeSettings::perPage(),
                    'since' => $since?->toIso8601String(),
                ]));

            if (! $response->successful()) {
                throw new RuntimeException(
                    'CKG bridge gagal (HTTP '.$response->status().'): '.$response->body()
                );
            }

            $payload = $response->json();
            $lastPage = (int) ($payload['meta']['last_page'] ?? 1);
            $stats['pages']++;

            foreach ($payload['data'] ?? [] as $row) {
                if (! is_array($row)) {
                    $stats['skipped']++;

                    continue;
                }

                $result = $this->upsertRow($row);
                $stats[$result]++;
            }

            $page++;
        } while ($page <= $lastPage);

        return $stats;
    }

    public function testConnection(): array
    {
        $apiKey = CkgBridgeSettings::apiKey();
        if ($apiKey === '') {
            throw new RuntimeException('API key bridge CKG belum diisi.');
        }

        $healthUrl = $this->endpoint('/api/bridge/mcu/health');
        $health = $this->client()->get($healthUrl);
        if (! $health->successful()) {
            throw new RuntimeException(
                'Health check gagal (HTTP '.$health->status().') ke '.$healthUrl
                .'. Pastikan URL bukan 127.0.0.1 (gunakan host.docker.internal:9006 dari container MCU).'
            );
        }

        $participantsUrl = $this->endpoint('/api/bridge/mcu/participants');
        $participants = $this->client()->get($participantsUrl, [
            'per_page' => 1,
        ]);

        if (! $participants->successful()) {
            throw new RuntimeException('Endpoint participants gagal (HTTP '.$participants->status().') ke '.$participantsUrl.'.');
        }

        return [
            'health' => $health->json(),
            'total_eligible' => (int) ($participants->json('meta.total') ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertRow(array $row): string
    {
        $attributes = $this->mapper->toParticipantAttributes($row);
        if ($attributes === null) {
            Log::warning('CKG bridge skip ineligible participant', [
                'ckg_peserta_id' => $row['ckg_peserta_id'] ?? null,
                'nik' => $row['nik'] ?? null,
            ]);

            return 'skipped';
        }

        $existing = null;
        if (! empty($attributes['ckg_peserta_id'])) {
            $existing = Participant::query()
                ->where('ckg_peserta_id', $attributes['ckg_peserta_id'])
                ->first();
        }

        $existing ??= Participant::query()
            ->where('nik_ktp', $attributes['nik_ktp'])
            ->first();

        if ($existing === null) {
            Participant::query()->create(array_merge($attributes, [
                'status_mcu' => 'Belum MCU',
                'tanggal_mcu_terakhir' => null,
            ]));

            return 'inserted';
        }

        $existing->fill([
            'ckg_peserta_id' => $attributes['ckg_peserta_id'] ?? $existing->ckg_peserta_id,
            'ckg_registration_code' => $attributes['ckg_registration_code'] ?? $existing->ckg_registration_code,
            'nama_lengkap' => $attributes['nama_lengkap'],
            'nrk_pegawai' => $attributes['nrk_pegawai'],
            'tanggal_lahir' => $attributes['tanggal_lahir'],
            'jenis_kelamin' => $attributes['jenis_kelamin'],
            'skpd' => $attributes['skpd'],
            'ukpd' => $attributes['ukpd'],
            'no_telp' => $attributes['no_telp'],
            'status_pegawai' => $attributes['status_pegawai'],
            'ckg_synced_at' => $attributes['ckg_synced_at'],
        ]);
        $existing->save();

        return 'updated';
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(CkgBridgeSettings::timeoutSeconds())
            ->withHeaders([
                CkgBridgeSettings::apiKeyHeader() => CkgBridgeSettings::apiKey(),
            ]);
    }

    private function endpoint(string $path): string
    {
        return CkgBridgeSettings::baseUrl().$path;
    }
}
