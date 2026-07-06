<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_pimpinan_can_access_dashboard_and_schedules_but_not_participants(): void
    {
        $pimpinan = User::factory()->create(['role' => 'pimpinan']);

        $this->actingAs($pimpinan)
            ->get(route('admin.schedules.index'))
            ->assertOk();

        $this->actingAs($pimpinan)
            ->get(route('admin.participants.index'))
            ->assertForbidden();
    }

    public function test_admin_cannot_access_ckg_bridge_or_reschedule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.ckg-bridge.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.reschedule-center.index'))
            ->assertForbidden();
    }

    public function test_pimpinan_can_access_reschedule_center(): void
    {
        $pimpinan = User::factory()->create(['role' => 'pimpinan']);

        $this->actingAs($pimpinan)
            ->get(route('admin.reschedule-center.index'))
            ->assertOk();
    }

    public function test_peserta_cannot_access_admin_panel(): void
    {
        $peserta = User::factory()->create(['role' => 'user']);

        $this->actingAs($peserta)
            ->get(route('admin.schedules.index'))
            ->assertForbidden();
    }

    public function test_admin_can_only_create_peserta_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Peserta Baru',
                'email' => 'peserta-baru@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'peserta',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'peserta-baru@example.com',
            'role' => 'user',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Baru',
                'email' => 'admin-baru@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'admin',
                'is_active' => '1',
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_change_user_role_on_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $peserta = User::factory()->create(['role' => 'user', 'email' => 'peserta@example.com']);

        $this->actingAs($admin)
            ->post(route('admin.users.update.post', $peserta), [
                'name' => 'Peserta Diubah',
                'email' => 'peserta@example.com',
                'role' => 'admin',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $peserta->id,
            'role' => 'user',
            'name' => 'Peserta Diubah',
        ]);
    }

    public function test_staff_with_linked_participant_can_access_client_portal(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'nik_ktp' => '3201010101010001',
        ]);

        Participant::query()->create([
            'nik_ktp' => '3201010101010001',
            'nrk_pegawai' => 'NRK-001',
            'nama_lengkap' => 'Staff Peserta',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Test',
            'ukpd' => 'UPT Test',
            'no_telp' => '081234567801',
            'email' => 'staff-peserta@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
        ]);

        $this->actingAs($admin)
            ->get(route('client.dashboard'))
            ->assertOk();
    }
}
