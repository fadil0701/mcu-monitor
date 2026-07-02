<?php

namespace Tests\Feature\Admin;

use App\Models\McuWorkCalendarClosure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McuWorkCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_work_calendar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.mcu-work-calendar.index'))
            ->assertOk()
            ->assertSee('Kalender Libur & Hari Kerja')
            ->assertSee('Blokir Sabtu & Minggu');

        $this->actingAs($admin)
            ->post(route('admin.mcu-work-calendar.closures.store'), [
                'closure_date' => '2026-12-25',
                'type' => 'libur_nasional',
                'label' => 'Hari Natal',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(
            McuWorkCalendarClosure::query()->whereDate('closure_date', '2026-12-25')->exists()
        );
        $closure = McuWorkCalendarClosure::query()->whereDate('closure_date', '2026-12-25')->firstOrFail();
        $this->assertSame('libur_nasional', $closure->type);
        $this->assertSame('Hari Natal', $closure->label);

        $this->actingAs($admin)
            ->delete(route('admin.mcu-work-calendar.closures.destroy', $closure))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('mcu_work_calendar_closures', ['id' => $closure->id]);
    }
}
