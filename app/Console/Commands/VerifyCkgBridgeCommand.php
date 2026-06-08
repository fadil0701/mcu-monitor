<?php

namespace App\Console\Commands;

use App\Models\CkgBridgeConfig;
use App\Services\CkgParticipantSyncService;
use App\Support\CkgBridge\CkgBridgeSettings;
use Illuminate\Console\Command;

class VerifyCkgBridgeCommand extends Command
{
    protected $signature = 'ckg-bridge:verify
        {--warn-only : Selalu exit 0; cetak peringatan jika gagal (cocok untuk deploy)}';

    protected $description = 'Verifikasi konfigurasi bridge CKG tanpa mengubah setting';

    public function handle(CkgParticipantSyncService $syncService): int
    {
        $config = CkgBridgeConfig::current();
        $warnOnly = (bool) $this->option('warn-only');

        $this->line('Sumber konfigurasi : '.($config?->is_active ? 'database (aktif)' : '.env / fallback'));
        $this->line('Base URL efektif  : '.CkgBridgeSettings::baseUrl());
        $this->line('API key            : '.(CkgBridgeSettings::apiKey() !== '' ? 'terisi' : 'kosong'));

        if (! $config?->is_active) {
            $this->comment('Bridge tidak aktif di database — lewati tes koneksi.');

            return self::SUCCESS;
        }

        if (CkgBridgeSettings::apiKey() === '') {
            $message = 'Bridge aktif tetapi API key kosong.';
            if ($warnOnly) {
                $this->warn($message);

                return self::SUCCESS;
            }
            $this->error($message);

            return self::FAILURE;
        }

        try {
            $result = $syncService->testConnection();
            $this->info('Tes koneksi OK. Peserta eligible: '.($result['total_eligible'] ?? 0));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Tes koneksi gagal: '.$exception->getMessage());
            $this->line('Konfigurasi database tidak diubah. Perbaiki URL/API key lalu jalankan:');
            $this->line('  php artisan ckg-bridge:configure --base-url=... --api-key=... --activate --test');

            return $warnOnly ? self::SUCCESS : self::FAILURE;
        }
    }
}
