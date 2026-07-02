<?php

namespace App\Console\Commands;

use App\Support\Database\MysqlToPostgresMigrator;
use Illuminate\Console\Command;

class FixPgsqlSequencesCommand extends Command
{
    protected $signature = 'mcu:fix-pgsql-sequences
                            {--connection= : Koneksi PG (default database_migration.target_connection)}';

    protected $description = 'Perbaiki sequence PostgreSQL (id) setelah migrasi MySQL — cegah duplicate key pada insert.';

    public function handle(MysqlToPostgresMigrator $migrator): int
    {
        $connection = $this->option('connection')
            ?: (string) config('database_migration.target_connection', 'pgsql');

        if (config("database.connections.{$connection}.driver") !== 'pgsql') {
            $this->error("Koneksi \"{$connection}\" bukan PostgreSQL.");

            return self::FAILURE;
        }

        $this->info("==> MCU Monitor — perbaiki sequence PostgreSQL ({$connection})");

        try {
            $migrator->resetSequences();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sequence diperbarui. Coba ulang operasi bridge/sync.');

        return self::SUCCESS;
    }
}
