<?php

namespace Tests\Unit;

use App\Support\CkgBridge\CkgParticipantMapper;
use Tests\TestCase;

class CkgParticipantMapperTest extends TestCase
{
    public function test_maps_eligible_pns_payload(): void
    {
        $mapper = new CkgParticipantMapper;

        $attributes = $mapper->toParticipantAttributes([
            'ckg_peserta_id' => 10,
            'nik' => '3173012345678010',
            'nama_lengkap' => 'Contoh',
            'tanggal_lahir' => '1991-02-03',
            'jenis_kelamin' => 'female',
            'no_hp' => '08123',
            'work_unit' => 'ASN DKI Jakarta',
            'participant_category' => 'cpns',
            'employee_nrk' => '556677',
            'skpd' => 'Dinas X',
            'ukpd' => 'UPT Y',
        ]);

        $this->assertNotNull($attributes);
        $this->assertSame('P', $attributes['jenis_kelamin']);
        $this->assertSame('CPNS', $attributes['status_pegawai']);
        $this->assertSame('3173012345678010@ckg-sync.local', $attributes['email']);
    }

    public function test_rejects_pppk_paruh_waktu(): void
    {
        $mapper = new CkgParticipantMapper;

        $attributes = $mapper->toParticipantAttributes([
            'nik' => '3173012345678099',
            'work_unit' => 'ASN DKI Jakarta',
            'participant_category' => 'pppk_paruh_waktu',
        ]);

        $this->assertNull($attributes);
    }
}
