<?php

namespace App\Imports;

use App\Models\Participant;
use App\Support\ParticipantEducation;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ParticipantsRowsImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithValidation
{
    public int $createdCount = 0;

    public int $updatedCount = 0;

    public int $skippedCount = 0;

    public function prepareForValidation($row, $index)
    {
        return $this->normalizeRowKeys($row);
    }

    public function model(array $row)
    {
        $row = $this->normalizeRowKeys($row);
        $mandatory = $this->mandatoryFields($row);
        $existing = $this->findExisting(
            $mandatory['nik_ktp'],
            $this->resolveNrk($row, $mandatory['nik_ktp']),
        );

        if ($existing !== null) {
            $this->skippedCount++;

            return null;
        }

        $this->createdCount++;

        return new Participant($this->buildParticipantDataForCreate($row, $mandatory));
    }

    /**
     * @return array{nik_ktp: string, nama_lengkap: string}
     */
    private function mandatoryFields(array $row): array
    {
        $nik = $this->normalizeNik($row['nik_ktp'] ?? null);

        return [
            'nik_ktp' => $nik,
            'nama_lengkap' => trim((string) ($row['nama_lengkap'] ?? '')),
        ];
    }

    /**
     * @param  array{nik_ktp: string, nama_lengkap: string}  $mandatory
     * @return array<string, mixed>
     */
    private function buildParticipantDataForCreate(array $row, array $mandatory): array
    {
        $nik = $mandatory['nik_ktp'];

        return array_merge(
            $this->defaultOptionalFields($nik),
            $this->optionalFieldsFromRow($row, $nik),
            $mandatory,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultOptionalFields(string $nik): array
    {
        return [
            'nrk_pegawai' => 'NRK-' . $nik,
            'tempat_lahir' => '-',
            'tanggal_lahir' => $this->birthDateFromNik($nik) ?? '1990-01-01',
            'jenis_kelamin' => 'L',
            'skpd' => '-',
            'ukpd' => '-',
            'no_telp' => '-',
            'email' => '',
            'status_pegawai' => 'PNS',
            'status_mcu' => 'Belum MCU',
            'tanggal_mcu_terakhir' => null,
            'catatan' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function optionalFieldsFromRow(array $row, string $nik): array
    {
        $optional = [];

        if (! $this->isEmpty($row['nrk_pegawai'] ?? null)) {
            $nrk = $this->normalizeString($row['nrk_pegawai']);
            if ($nrk !== '') {
                $optional['nrk_pegawai'] = $nrk;
            }
        }

        if (! $this->isEmpty($row['tempat_lahir'] ?? null)) {
            $optional['tempat_lahir'] = trim((string) $row['tempat_lahir']) ?: '-';
        }

        if (! $this->isEmpty($row['jenis_kelamin'] ?? null)) {
            $optional['jenis_kelamin'] = $this->normalizeGender($row['jenis_kelamin'] ?? null, required: true);
        }

        $tanggalLahir = $this->parseDate($row['tanggal_lahir'] ?? null);
        if ($tanggalLahir !== null) {
            $optional['tanggal_lahir'] = $tanggalLahir;
        }

        if (! $this->isEmpty($row['skpd'] ?? null)) {
            $optional['skpd'] = trim((string) $row['skpd']) ?: '-';
        }

        if (! $this->isEmpty($row['ukpd'] ?? null)) {
            $optional['ukpd'] = trim((string) $row['ukpd']) ?: '-';
        }

        if (! $this->isEmpty($row['no_telp'] ?? null)) {
            $optional['no_telp'] = $this->normalizePhone($row['no_telp']);
        }

        if (array_key_exists('email', $row) && ! $this->isEmpty($row['email'])) {
            $optional['email'] = trim((string) $row['email']);
        }

        if (! $this->isEmpty($row['status_pegawai'] ?? null)) {
            $optional['status_pegawai'] = $this->normalizeStatusPegawai($row['status_pegawai']);
        }

        $pendidikan = $this->normalizePendidikan($row['pendidikan_terakhir'] ?? null);
        if ($pendidikan !== null) {
            $optional['pendidikan_terakhir'] = $pendidikan;
        }

        if (! $this->isEmpty($row['status_mcu'] ?? null)) {
            $optional['status_mcu'] = $this->normalizeStatusMcu($row['status_mcu']);
        }

        $tanggalMcu = $this->parseDate($row['tanggal_mcu_terakhir'] ?? null);
        if ($tanggalMcu !== null) {
            $optional['tanggal_mcu_terakhir'] = $tanggalMcu;
        }

        if (array_key_exists('catatan', $row) && ! $this->isEmpty($row['catatan'])) {
            $optional['catatan'] = trim((string) $row['catatan']);
        }

        return $optional;
    }

    private function resolveNrk(array $row, string $nik): string
    {
        $nrk = $this->normalizeString($row['nrk_pegawai'] ?? null);

        return $nrk !== '' ? $nrk : 'NRK-' . $nik;
    }

    private function findExisting(string $nik, string $nrk): ?Participant
    {
        $byNik = Participant::query()->where('nik_ktp', $nik)->first();

        if ($byNik !== null) {
            return $byNik;
        }

        if ($nrk !== '' && ! str_starts_with($nrk, 'NRK-')) {
            return Participant::query()->where('nrk_pegawai', $nrk)->first();
        }

        return null;
    }

    public function rules(): array
    {
        return [
            'nik_ktp' => 'required',
            'nama_lengkap' => 'required',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nik_ktp.required' => 'NIK wajib diisi.',
            'nama_lengkap.required' => 'Nama wajib diisi.',
        ];
    }

    private function normalizeRowKeys(array $row): array
    {
        $aliases = [
            'nik' => 'nik_ktp',
            'nik_ktp' => 'nik_ktp',
            'nama' => 'nama_lengkap',
            'nama_lengkap' => 'nama_lengkap',
            'jenis_kelamin' => 'jenis_kelamin',
            'jk' => 'jenis_kelamin',
            'no_telp' => 'no_telp',
            'no_telepon' => 'no_telp',
            'telepon' => 'no_telp',
            'nrk' => 'nrk_pegawai',
            'nrk_pegawai' => 'nrk_pegawai',
            'tempat_lahir' => 'tempat_lahir',
            'tanggal_lahir' => 'tanggal_lahir',
            'skpd' => 'skpd',
            'ukpd' => 'ukpd',
            'email' => 'email',
            'status_pegawai' => 'status_pegawai',
            'pendidikan_terakhir' => 'pendidikan_terakhir',
            'pendidikan' => 'pendidikan_terakhir',
            'status_mcu' => 'status_mcu',
            'tanggal_mcu_terakhir' => 'tanggal_mcu_terakhir',
            'catatan' => 'catatan',
        ];

        $normalized = [];

        foreach ($row as $key => $value) {
            $slug = Str::slug((string) $key, '_');
            $target = $aliases[$slug] ?? $slug;

            if (! array_key_exists($target, $normalized) || $this->isEmpty($normalized[$target])) {
                $normalized[$target] = $value;
            }
        }

        return $normalized;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function normalizeNik(mixed $value): string
    {
        $digits = $this->digitsOnly($value);

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) !== 16) {
            throw new \InvalidArgumentException(
                'NIK harus tepat 16 digit (ditemukan: ' . strlen($digits) . ' digit). Format kolom NIK sebagai Teks di Excel sebelum import.'
            );
        }

        return $digits;
    }

    private function normalizeString(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_float($value) || is_int($value)) {
            return $this->digitsOnly($value);
        }

        return trim((string) $value);
    }

    private function normalizePhone(mixed $value): string
    {
        $phone = $this->normalizeString($value);

        if ($phone === '') {
            return '-';
        }

        $phone = preg_replace('/[^\d+]/', '', $phone) ?? $phone;

        return substr($phone, 0, 20);
    }

    private function digitsOnly(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_float($value) || is_int($value)) {
            return number_format((float) $value, 0, '', '');
        }

        $value = trim((string) $value);

        if (preg_match('/^[\d,.]+E[\d+-]+$/i', $value)) {
            return number_format((float) $value, 0, '', '');
        }

        if (preg_match('/^(\d+)\.0+$/', $value, $matches)) {
            $value = $matches[1];
        }

        return preg_replace('/\D/', '', $value) ?? '';
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::createFromTimestampUTC(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp((float) $value))
                    ->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function birthDateFromNik(string $nik): ?string
    {
        if (strlen($nik) !== 16) {
            return null;
        }

        $segment = substr($nik, 6, 6);
        $day = (int) substr($segment, 0, 2);
        $month = (int) substr($segment, 2, 2);
        $yearShort = (int) substr($segment, 4, 2);

        if ($day > 40) {
            $day -= 40;
        }

        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return null;
        }

        $year = $yearShort <= (int) date('y') ? 2000 + $yearShort : 1900 + $yearShort;

        try {
            return Carbon::createFromDate($year, $month, $day)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeGender(mixed $value, bool $required = false): string
    {
        $gender = strtoupper(trim((string) ($value ?? '')));

        if ($gender === '') {
            if ($required) {
                throw new \InvalidArgumentException('Jenis kelamin wajib diisi (L atau P).');
            }

            return 'L';
        }

        if (! in_array($gender, ['L', 'P'], true)) {
            throw new \InvalidArgumentException('Jenis kelamin harus L (Laki-laki) atau P (Perempuan).');
        }

        return $gender;
    }

    private function normalizeStatusPegawai(mixed $value): string
    {
        $status = strtoupper(trim((string) ($value ?? '')));

        return in_array($status, ['CPNS', 'PNS', 'PPPK'], true) ? $status : 'PNS';
    }

    private function normalizePendidikan(mixed $value): ?string
    {
        if ($this->isEmpty($value)) {
            return null;
        }

        $input = trim((string) $value);

        foreach (ParticipantEducation::levels() as $level) {
            if (strcasecmp($input, $level) === 0) {
                return $level;
            }
        }

        return null;
    }

    private function normalizeStatusMcu(mixed $value): string
    {
        $status = trim((string) ($value ?? ''));

        return in_array($status, ['Belum MCU', 'Sudah MCU', 'Ditolak'], true) ? $status : 'Belum MCU';
    }
}
