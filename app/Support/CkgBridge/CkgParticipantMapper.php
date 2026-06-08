<?php

namespace App\Support\CkgBridge;

class CkgParticipantMapper
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function toParticipantAttributes(array $payload): ?array
    {
        if (! $this->isEligible($payload)) {
            return null;
        }

        $nik = $this->normalizeNik((string) ($payload['nik'] ?? ''));
        if ($nik === '') {
            return null;
        }

        $category = strtolower((string) ($payload['participant_category'] ?? ''));

        return [
            'ckg_peserta_id' => (int) ($payload['ckg_peserta_id'] ?? 0) ?: null,
            'ckg_registration_code' => (string) ($payload['ckg_registration_code'] ?? '') ?: null,
            'nik_ktp' => $nik,
            'nrk_pegawai' => trim((string) ($payload['employee_nrk'] ?? '')) ?: 'NRK-'.$nik,
            'nama_lengkap' => trim((string) ($payload['nama_lengkap'] ?? '')),
            'tempat_lahir' => '-',
            'tanggal_lahir' => (string) ($payload['tanggal_lahir'] ?? '1990-01-01'),
            'jenis_kelamin' => $this->mapGender((string) ($payload['jenis_kelamin'] ?? '')),
            'skpd' => trim((string) ($payload['skpd'] ?? '')) ?: '-',
            'ukpd' => trim((string) ($payload['ukpd'] ?? '')) ?: '-',
            'no_telp' => trim((string) ($payload['no_hp'] ?? '')) ?: '-',
            'email' => $nik.'@ckg-sync.local',
            'status_pegawai' => $this->mapStatusPegawai($category),
            'ckg_synced_at' => now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function isEligible(array $payload): bool
    {
        $workUnit = (string) ($payload['work_unit'] ?? '');
        $category = strtolower((string) ($payload['participant_category'] ?? ''));

        return $workUnit === config('ckg_bridge.eligible_work_unit')
            && in_array($category, config('ckg_bridge.eligible_categories', []), true);
    }

    private function mapGender(string $gender): string
    {
        return match (strtolower($gender)) {
            'male', 'l', 'laki-laki', 'laki laki' => 'L',
            'female', 'p', 'perempuan' => 'P',
            default => 'L',
        };
    }

    private function mapStatusPegawai(string $category): string
    {
        return match ($category) {
            'cpns' => 'CPNS',
            'pppk' => 'PPPK',
            default => 'PNS',
        };
    }

    private function normalizeNik(string $nik): string
    {
        $digits = preg_replace('/\D+/', '', $nik) ?? '';

        return strlen($digits) === 16 ? $digits : '';
    }
}
