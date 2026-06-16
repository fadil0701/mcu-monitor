<?php

namespace App\Support;

use App\Models\InstansiPemprovDki;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar instansi SKPD dari config/instansi_pemprov_dki.php (baca file langsung, bukan config cache).
 */
class InstansiPemprovDkiCatalog
{
    /**
     * @return list<string>
     */
    public static function defaultNames(): array
    {
        $config = require config_path('instansi_pemprov_dki.php');

        return collect($config['instansi_pemprov_dki'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Opsi dropdown form SKPD: DB aktif, fallback ke config.
     *
     * @return list<string>
     */
    public static function optionsForForms(): array
    {
        if (! Schema::hasTable('instansi_pemprov_dkis')) {
            return self::defaultNames();
        }

        $fromDb = InstansiPemprovDki::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return $fromDb !== [] ? $fromDb : self::defaultNames();
    }

    /**
     * @return array{synced: int, deactivated: int}
     */
    public static function syncToDatabase(): array
    {
        if (! Schema::hasTable('instansi_pemprov_dkis')) {
            return ['synced' => 0, 'deactivated' => 0];
        }

        $names = self::defaultNames();
        $order = 10;

        foreach ($names as $name) {
            InstansiPemprovDki::query()->updateOrCreate(
                ['name' => $name],
                ['sort_order' => $order, 'is_active' => true],
            );
            $order += 10;
        }

        $deactivated = 0;
        if ($names !== []) {
            $deactivated = InstansiPemprovDki::query()
                ->whereNotIn('name', $names)
                ->update(['is_active' => false]);
        }

        return [
            'synced' => count($names),
            'deactivated' => $deactivated,
        ];
    }
}
