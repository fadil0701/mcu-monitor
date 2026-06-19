<?php

namespace App\Console\Commands;

use App\Support\Backup\DatabaseBackupRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'mcu:backup-database';

    protected $description = 'Backup database MySQL (dump, gzip, enkripsi GPG opsional).';

    public function handle(DatabaseBackupRunner $runner): int
    {
        $this->info('==> Monitoring MCU — backup database MySQL');
        $this->line('    Database: '.config('database.connections.mysql.database'));
        $this->line('    Output:   '.config('backup.directory'));
        $this->line('    Enkripsi: '.(config('backup.encrypt') ? 'GPG AES256 (aktif)' : 'nonaktif'));

        try {
            $finalPath = $runner->run();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $this->appendLog('ERROR: '.$e->getMessage());

            return self::FAILURE;
        }

        $bytes = (int) filesize($finalPath);
        $this->info('==> Selesai.');
        $this->line("    File: {$finalPath} ({$bytes} bytes)");

        $this->appendLog('OK: '.$finalPath.' ('.$bytes.' bytes)');

        return self::SUCCESS;
    }

    private function appendLog(string $line): void
    {
        $logFile = (string) config('backup.log_file');
        File::ensureDirectoryExists(dirname($logFile));
        $entry = '['.now()->format('Y-m-d H:i:s').'] '.$line.PHP_EOL;
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
