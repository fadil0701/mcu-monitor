<?php

namespace Tests\Unit;

use App\Helpers\MenuHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_menu_includes_komunikasi_section(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user);

        $labels = collect(MenuHelper::getMainNavItems())
            ->map(fn (array $item) => $item['type'] ?? null === 'header' ? $item['name'] : ($item['name'] ?? null))
            ->filter()
            ->values()
            ->all();

        $this->assertContains('Komunikasi', $labels);
        $this->assertContains('MCU & Peserta', $labels);
    }

    public function test_admin_menu_excludes_super_admin_only_sections(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user);

        $labels = collect(MenuHelper::getMainNavItems())
            ->map(fn (array $item) => $item['type'] ?? null === 'header' ? $item['name'] : ($item['name'] ?? null))
            ->filter()
            ->values()
            ->all();

        $this->assertNotContains('Komunikasi', $labels);
    }
}
