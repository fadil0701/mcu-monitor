<?php

namespace App\Console\Commands;

use App\Services\CkgParticipantSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncParticipantsFromCkg extends Command
{
    protected $signature = 'mcu:sync-participants-from-ckg {--since= : ISO8601 datetime untuk delta sync}';

    protected $description = 'Sinkron peserta eligible dari Portal CKG ke tabel participants MCU';

    public function handle(CkgParticipantSyncService $syncService): int
    {
        $sinceOption = $this->option('since');
        $since = is_string($sinceOption) && $sinceOption !== ''
            ? Carbon::parse($sinceOption)
            : null;

        $this->info($since
            ? 'Sinkron delta CKG sejak '.$since->toIso8601String()
            : 'Sinkron penuh peserta eligible dari CKG...');

        try {
            $stats = $syncService->syncWithLog($since, 'command');
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metrik', 'Nilai'],
            collect($stats)->except('log_id')->map(fn ($value, $key) => [$key, $value])->values()->all()
        );

        $this->info('Sinkron CKG selesai (log #'.$stats['log_id'].').');

        return self::SUCCESS;
    }
}
