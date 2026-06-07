<?php

namespace App\Imports;

use App\Models\Participant;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ParticipantsImport implements ToModel, WithHeadingRow, SkipsEmptyRows, WithValidation
{
    public function prepareForValidation($row, $index)
    {
        return $this->normalizeRowKeys($row);
    }

    public function model(array $row)
    {
        $row = $this->normalizeRowKeys($row);

        $nik = $this->normalizeNik($row['nik_ktp'] ?? null);
        $nrk = $this->normalizeString($row['nrk_pegawai'] ?? null);
        $noTelp = $this->normalizePhone($row['no_telp'] ?? null, required: true);

        $data = [
            'nik_ktp' => $nik,
            'nrk_pegawai' => $nrk !== '' ? $nrk : 'NRK-' . $nik,
            'nama_lengkap' => trim((string) ($row['nama_lengkap'] ?? '')),
            'tempat_lahir' => trim((string) ($row['tempat_lahir'] ?? '')) ?: '-',
            'tanggal_lahir' => $this->parseDate($row['tanggal_lahir'] ?? null)
                ?? $this->birthDateFromNik($nik)
                ?? '1990-01-01',
            'jenis_kelamin' => $this->normalizeGender($row['jenis_kelamin'] ?? null, required: true),
            'skpd' => trim((string) ($row['skpd'] ?? '')) ?: '-',
            'ukpd' => trim((string) ($row['ukpd'] ?? '')) ?: '-',
            'no_telp' => $noTelp,
            'email' => trim((string) ($row['email'] ?? '')),
            'status_pegawai' => $this->normalizeStatusPegawai($row['status_pegawai'] ?? null),
            'status_mcu' => $this->normalizeStatusMcu($row['status_mcu'] ?? null),
            'tanggal_mcu_terakhir' => $this->parseDate($row['tanggal_mcu_terakhir'] ?? null),
            'catatan' => trim((string) ($row['catatan'] ?? '')),
        ];

        return new Participant($data);
    }

    public function rules(): array
    {
        return [
            'nik_ktp' => 'required',
            'nama_lengkap' => 'required',
            'jenis_kelamin' => 'required',
            'no_telp' => 'required',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nik_ktp.required' => 'NIK wajib diisi.',
            'nama_lengkap.required' => 'Nama wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib diisi (L atau P).',
            'no_telp.required' => 'No telp wajib diisi.',
        ];
    }

    /**
     * Samakan nama kolom Excel (NIK, Nama, dll.) ke key internal.
     */
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

    /**
     * Normalisasi NIK dari Excel (angka, notasi ilmiah, desimal .0).
     */
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

    private function normalizePhone(mixed $value, bool $required = false): string
    {
        $phone = $this->normalizeString($value);

        if ($phone === '') {
            if ($required) {
                throw new \InvalidArgumentException('No telp wajib diisi.');
            }

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

    /**
     * Ambil tanggal lahir dari digit NIK (posisi 7–12: DDMMYY).
     */
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

    private function normalizeStatusMcu(mixed $value): string
    {
        $status = trim((string) ($value ?? ''));

        return in_array($status, ['Belum MCU', 'Sudah MCU', 'Ditolak'], true) ? $status : 'Belum MCU';
    }
}
