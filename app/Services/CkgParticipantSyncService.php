<?php

namespace App\Services;

use App\Models\CkgBridgeSyncLog;
use App\Models\Participant;
use App\Support\CkgBridge\CkgBridgeSettings;
use App\Support\CkgBridge\CkgParticipantMapper;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
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
                .'. Periksa base URL bridge di admin atau docs/DEPLOY.md.'
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

        $existing = $this->findExistingParticipant($attributes);

        if ($existing === null) {
            try {
                Participant::query()->create(array_merge($attributes, [
                    'status_mcu' => 'Belum MCU',
                    'tanggal_mcu_terakhir' => null,
                ]));

                return 'inserted';
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                $existing = $this->findExistingParticipant($attributes);
                if ($existing === null) {
                    throw $exception;
                }
            }
        }

        $this->applyParticipantUpdate($existing, $attributes);

        return 'updated';
    }

    /**
     * Kunci idempotensi: NIK stabil antar tahun; ckg_peserta_id bisa berubah tiap siklus CKG.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function findExistingParticipant(array $attributes): ?Participant
    {
        $byNik = Participant::query()
            ->where('nik_ktp', $attributes['nik_ktp'])
            ->first();

        if ($byNik !== null) {
            return $byNik;
        }

        if (! empty($attributes['ckg_peserta_id'])) {
            $byCkgId = Participant::query()
                ->where('ckg_peserta_id', $attributes['ckg_peserta_id'])
                ->first();

            if ($byCkgId !== null) {
                return $byCkgId;
            }
        }

        $nrk = trim((string) ($attributes['nrk_pegawai'] ?? ''));
        if ($nrk !== '' && ! str_starts_with($nrk, 'NRK-')) {
            return Participant::query()
                ->where('nrk_pegawai', $nrk)
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function applyParticipantUpdate(Participant $existing, array $attributes): void
    {
        $nrk = (string) $attributes['nrk_pegawai'];
        $safeNrk = $this->resolveSafeNrk($existing, $nrk);

        $existing->fill([
            'ckg_peserta_id' => $attributes['ckg_peserta_id'] ?? $existing->ckg_peserta_id,
            'ckg_registration_code' => $attributes['ckg_registration_code'] ?? $existing->ckg_registration_code,
            'nik_ktp' => $attributes['nik_ktp'],
            'nama_lengkap' => $attributes['nama_lengkap'],
            'nrk_pegawai' => $safeNrk,
            'tanggal_lahir' => $attributes['tanggal_lahir'],
            'jenis_kelamin' => $attributes['jenis_kelamin'],
            'skpd' => $attributes['skpd'],
            'ukpd' => $attributes['ukpd'],
            'no_telp' => $attributes['no_telp'],
            'alamat_domisili' => $attributes['alamat_domisili'] ?? $existing->alamat_domisili,
            'status_pernikahan' => $attributes['status_pernikahan'] ?? $existing->status_pernikahan,
            'status_pegawai' => $attributes['status_pegawai'],
            'ckg_synced_at' => $attributes['ckg_synced_at'],
        ]);
        $existing->save();
    }

    private function resolveSafeNrk(Participant $existing, string $nrk): string
    {
        if ($nrk === '' || $nrk === $existing->nrk_pegawai) {
            return $existing->nrk_pegawai;
        }

        $taken = Participant::query()
            ->where('nrk_pegawai', $nrk)
            ->where('id', '!=', $existing->id)
            ->exists();

        return $taken ? $existing->nrk_pegawai : $nrk;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? '';

        return in_array($sqlState, ['23000', '23505'], true);
    }

    private function client(): PendingRequest
    {
        $client = Http::acceptJson()
            ->timeout(CkgBridgeSettings::timeoutSeconds())
            ->withHeaders([
                CkgBridgeSettings::apiKeyHeader() => CkgBridgeSettings::apiKey(),
            ]);

        if (config('ckg_bridge.disable_proxy', true)) {
            $client = $client->withOptions(['proxy' => false]);
        }

        return $client;
    }

    private function endpoint(string $path): string
    {
        return CkgBridgeSettings::baseUrl().$path;
    }
}
