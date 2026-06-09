<?php

namespace Tests\Unit;

use App\Models\InstansiPemprovDki;
use App\Support\InstansiPemprovDkiCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstansiPemprovDkiCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_activates_config_names_and_deactivates_legacy(): void
    {
        InstansiPemprovDki::query()->create([
            'name' => 'RSUD Tarakan',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $result = InstansiPemprovDkiCatalog::syncToDatabase();

        $this->assertGreaterThanOrEqual(90, $result['synced']);
        $this->assertSame(1, $result['deactivated']);
        $this->assertTrue(InstansiPemprovDki::query()->where('name', 'Dinas Kesehatan')->value('is_active'));
        $this->assertFalse(InstansiPemprovDki::query()->where('name', 'RSUD Tarakan')->value('is_active'));
    }

    public function test_options_for_forms_use_database_when_available(): void
    {
        InstansiPemprovDkiCatalog::syncToDatabase();

        $options = InstansiPemprovDkiCatalog::optionsForForms();

        $this->assertContains('Dinas Kesehatan', $options);
        $this->assertGreaterThanOrEqual(90, count($options));
    }

    public function test_default_names_loaded_from_config_file(): void
    {
        $names = InstansiPemprovDkiCatalog::defaultNames();

        $this->assertContains('Dinas Kesehatan', $names);
        $this->assertContains('Biro Pemerintahan', $names);
        $this->assertNotContains('RSUD Tarakan', $names);
    }
}
