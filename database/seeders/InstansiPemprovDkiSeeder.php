<?php

namespace Database\Seeders;

use App\Support\InstansiPemprovDkiCatalog;
use Illuminate\Database\Seeder;

class InstansiPemprovDkiSeeder extends Seeder
{
    public function run(): void
    {
        $result = InstansiPemprovDkiCatalog::syncToDatabase();

        $this->command?->info(sprintf(
            'Instansi Pemprov DKI: %d aktif disinkronkan, %d dinonaktifkan (tidak ada di config).',
            $result['synced'],
            $result['deactivated'],
        ));
    }
}
