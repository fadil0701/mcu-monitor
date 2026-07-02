<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PreparePgsqlSchemaCommand extends Command
{
    protected $signature = 'mcu:prepare-pgsql-schema';

    protected $description = 'Sesuaikan skema PostgreSQL dengan data legacy MySQL sebelum salin data.';

    public function handle(): int
    {
        $connection = DB::connection('pgsql');

        if ($connection->getDriverName() !== 'pgsql') {
            $this->error('Koneksi pgsql tidak tersedia.');

            return self::FAILURE;
        }

        if (Schema::connection('pgsql')->hasTable('mcu_results')
            && Schema::connection('pgsql')->hasColumn('mcu_results', 'status_kesehatan')) {
            $connection->statement('ALTER TABLE mcu_results ALTER COLUMN status_kesehatan DROP NOT NULL');
            $this->info('mcu_results.status_kesehatan → nullable');
        }

        return self::SUCCESS;
    }
}
