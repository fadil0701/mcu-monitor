<?php

namespace App\Console\Commands;

use App\Models\CkgBridgeConfig;
use App\Services\CkgParticipantSyncService;
use App\Support\CkgBridge\CkgBridgeSettings;
use App\Support\CkgBridge\CkgBridgeUrlNormalizer;
use Illuminate\Console\Command;

class ConfigureCkgBridgeCommand extends Command
{
    protected $signature = 'ckg-bridge:configure
        {--base-url= : Base URL CKG (http://HOST:PORT)}
        {--api-key= : API key dari menu Bridging MCU di CKG}
        {--activate : Aktifkan konfigurasi database (override .env)}
        {--test : Tes koneksi setelah simpan}
        {--sync : Jalankan sinkron setelah tes berhasil}';

    protected $description = 'Atur bridge CKG lewat CLI';

    public function handle(CkgParticipantSyncService $syncService): int
    {
        $baseUrl = $this->option('base-url');
        $apiKey = $this->option('api-key');

        if (! is_string($baseUrl) || $baseUrl === '') {
            $this->error('Wajib --base-url, contoh: --base-url=http://HOST:9006');

            return self::FAILURE;
        }

        $data = [
            'base_url' => CkgBridgeUrlNormalizer::normalize($baseUrl),
            'api_key_header' => 'X-Mcu-Api-Key',
            'per_page' => 100,
            'timeout_seconds' => 60,
        ];

        if (is_string($apiKey) && $apiKey !== '') {
            $data['api_key'] = $apiKey;
        }

        $config = CkgBridgeConfig::query()->firstWhere('name', 'CKG Bridge');

        if ($config === null) {
            $data['name'] = 'CKG Bridge';
            $data['is_active'] = (bool) $this->option('activate');
            $config = CkgBridgeConfig::query()->create($data);
        } else {
            if ($this->option('activate')) {
                $data['is_active'] = true;
            }
            $config->fill($data);
            $config->save();
        }

        $config = $config->fresh();

        $this->info('Konfigurasi bridge CKG disimpan.');
        $this->line('  Base URL : '.$config->base_url);
        $this->line('  Aktif DB : '.($config->is_active ? 'ya' : 'tidak'));
        $this->line('  API key  : '.(filled($config->api_key) ? 'terisi' : 'kosong'));

        if ($this->option('test') || $this->option('sync')) {
            if (CkgBridgeSettings::apiKey() === '') {
                $this->error('API key kosong. Tambahkan --api-key=...');

                return self::FAILURE;
            }

            try {
                $result = $syncService->testConnection();
                $this->info('Tes koneksi OK. Peserta eligible: '.($result['total_eligible'] ?? 0));
            } catch (\Throwable $exception) {
                $this->error('Tes koneksi gagal: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        if ($this->option('sync')) {
            try {
                $stats = $syncService->syncWithLog(null, 'command');
                $this->info(sprintf(
                    'Sinkron selesai — insert: %d, update: %d, skip: %d.',
                    $stats['inserted'],
                    $stats['updated'],
                    $stats['skipped'],
                ));
            } catch (\Throwable $exception) {
                $this->error('Sinkron gagal: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
