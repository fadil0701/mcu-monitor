<?php

namespace App\Console\Commands;

use App\Support\Database\MysqlToPostgresMigrator;
use Illuminate\Console\Command;

class MigrateMysqlToPostgresCommand extends Command
{
    protected $signature = 'mcu:migrate-mysql-to-pgsql
                            {--fresh : Kosongkan tabel target PostgreSQL sebelum salin}
                            {--verify : Bandingkan jumlah baris MySQL vs PostgreSQL}
                            {--chunk= : Ukuran chunk per tabel}';

    protected $description = 'Salin data MCU Monitor dari MySQL ke PostgreSQL (health-platform).';

    public function handle(MysqlToPostgresMigrator $migrator): int
    {
        $this->info('==> MCU Monitor — migrasi data MySQL → PostgreSQL');
        $this->line('    Sumber: '.config('database_migration.source_connection', 'mysql'));
        $this->line('    Target: '.config('database_migration.target_connection', 'pgsql'));

        if ($this->option('verify') && ! $this->option('fresh')) {
            return $this->verifyOnly($migrator);
        }

        $chunk = $this->option('chunk') !== null ? (int) $this->option('chunk') : null;

        try {
            $result = $migrator->sync((bool) $this->option('fresh'), $chunk);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Salin selesai:');
        $rows = [];
        foreach ($result['rows'] as $table => $count) {
            $rows[] = [$table, (string) $count];
        }
        $this->table(['Tabel', 'Baris'], $rows);

        if ($this->option('verify')) {
            return $this->verifyOnly($migrator);
        }

        return self::SUCCESS;
    }

    private function verifyOnly(MysqlToPostgresMigrator $migrator): int
    {
        try {
            $report = $migrator->verifyCounts();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $rows = [];
        $allMatch = true;
        foreach ($report as $table => $counts) {
            $rows[] = [
                $table,
                (string) $counts['mysql'],
                (string) $counts['pgsql'],
                $counts['match'] ? 'OK' : 'MISMATCH',
            ];
            if (! $counts['match']) {
                $allMatch = false;
            }
        }

        $this->table(['Tabel', 'MySQL', 'PostgreSQL', 'Status'], $rows);

        return $allMatch ? self::SUCCESS : self::FAILURE;
    }
}
