<?php

namespace Tests\Unit;

use App\Models\Participant;
use Tests\TestCase;

class ParticipantCkgStatusTest extends TestCase
{
    public function test_ckg_status_label_for_current_year(): void
    {
        $participant = new Participant([
            'ckg_peserta_id' => 42,
            'ckg_registration_code' => 'CKG-2026-001',
            'ckg_synced_at' => now()->setDate(2026, 3, 15),
        ]);

        $this->assertTrue($participant->hasCkgForCurrentYear());
        $this->assertSame('Sudah CKG 2026', $participant->ckgStatusLabel());
        $this->assertSame('bg-label-success', $participant->ckgStatusBadgeClass());
    }

    public function test_ckg_status_label_for_previous_year(): void
    {
        $participant = new Participant([
            'ckg_peserta_id' => 42,
            'ckg_registration_code' => 'CKG-2025-001',
            'ckg_synced_at' => now()->setDate(2025, 6, 1),
        ]);

        $this->assertFalse($participant->hasCkgForCurrentYear());
        $this->assertSame('CKG 2025', $participant->ckgStatusLabel());
        $this->assertSame('bg-label-warning', $participant->ckgStatusBadgeClass());
    }

    public function test_ckg_status_label_when_not_completed(): void
    {
        $participant = new Participant([
            'ckg_peserta_id' => null,
            'ckg_registration_code' => null,
            'ckg_synced_at' => null,
        ]);

        $this->assertFalse($participant->hasCompletedCkgScreening());
        $this->assertSame('Belum CKG', $participant->ckgStatusLabel());
        $this->assertSame('bg-label-danger', $participant->ckgStatusBadgeClass());
    }
}
