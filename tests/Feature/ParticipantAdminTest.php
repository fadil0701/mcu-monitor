<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_participant_via_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = $this->makeParticipant(['nama_lengkap' => 'Nama Lama']);

        $this->actingAs($admin)
            ->post(route('admin.participants.update.post', $participant), [
                'nik_ktp' => $participant->nik_ktp,
                'nrk_pegawai' => $participant->nrk_pegawai,
                'nama_lengkap' => 'Nama Baru',
                'tempat_lahir' => $participant->tempat_lahir,
                'tanggal_lahir' => $participant->tanggal_lahir->format('Y-m-d'),
                'jenis_kelamin' => $participant->jenis_kelamin,
                'skpd' => $participant->skpd,
                'ukpd' => $participant->ukpd,
                'status_pegawai' => $participant->status_pegawai,
                'no_telp' => $participant->no_telp,
                'email' => $participant->email,
                'status_mcu' => $participant->status_mcu,
            ])
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'nama_lengkap' => 'Nama Baru',
        ]);
    }

    public function test_edit_form_posts_to_update_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = $this->makeParticipant();

        $this->actingAs($admin)
            ->get(route('admin.participants.edit', $participant))
            ->assertOk()
            ->assertSee(route('admin.participants.update.post', $participant), false);
    }

    public function test_admin_can_delete_participant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = $this->makeParticipant();

        $this->actingAs($admin)
            ->delete(route('admin.participants.destroy', $participant))
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
    }

    public function test_delete_bulk_destroy_path_does_not_return_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete('/admin/participants/bulk-destroy')
            ->assertRedirect(route('admin.participants.index'))
            ->assertSessionHas('error');
    }

    public function test_edit_form_does_not_mark_filled_education_as_invalid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $participant = $this->makeParticipant(['pendidikan_terakhir' => 'Sarjana']);

        $response = $this->actingAs($admin)
            ->get(route('admin.participants.edit', $participant));

        $response->assertOk();

        preg_match('/<select[^>]*id="pendidikan_terakhir"[^>]*>/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $this->assertStringNotContainsString('is-invalid', $matches[0]);
    }

    public function test_admin_can_download_import_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.participants.template'))
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeParticipant(array $overrides = []): Participant
    {
        return Participant::query()->create(array_merge([
            'nik_ktp' => '3173012345678999',
            'nrk_pegawai' => 'NRK-999',
            'nama_lengkap' => 'Peserta Test',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => 'Dinas Test',
            'ukpd' => 'UPT Test',
            'no_telp' => '081234567899',
            'email' => 'peserta@test.local',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
        ], $overrides));
    }
}
