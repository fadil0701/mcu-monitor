<?php

namespace App\Models;

use App\Services\QueryOptimizationService;
use App\Support\McuIntervalSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'ckg_peserta_id',
        'ckg_registration_code',
        'ckg_synced_at',
        'nik_ktp',
        'nrk_pegawai',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'skpd',
        'ukpd',
        'no_telp',
        'alamat_domisili',
        'status_pernikahan',
        'email',
        'status_pegawai',
        'pendidikan_terakhir',
        'status_mcu',
        'tanggal_mcu_terakhir',
        'catatan',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_mcu_terakhir' => 'date',
        'ckg_synced_at' => 'datetime',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function mcuResults(): HasMany
    {
        return $this->hasMany(McuResult::class);
    }

    public function hasCompletedCkgScreening(): bool
    {
        return $this->ckg_peserta_id !== null || filled($this->ckg_registration_code);
    }

    public function ckgScreeningYear(): ?int
    {
        if ($this->ckg_synced_at) {
            return (int) $this->ckg_synced_at->format('Y');
        }

        if (preg_match('/(\d{4})/', (string) $this->ckg_registration_code, $matches) === 1) {
            $year = (int) $matches[1];
            if ($year >= 2020 && $year <= 2100) {
                return $year;
            }
        }

        return null;
    }

    public function hasCkgForCurrentYear(): bool
    {
        return $this->hasCompletedCkgScreening()
            && $this->ckgScreeningYear() === (int) now()->format('Y');
    }

    public function ckgStatusLabel(): string
    {
        if (! $this->hasCompletedCkgScreening()) {
            return 'Belum CKG';
        }

        $year = $this->ckgScreeningYear();
        $currentYear = (int) now()->format('Y');

        if ($year === $currentYear) {
            return 'Sudah CKG '.$currentYear;
        }

        if ($year !== null) {
            return 'CKG '.$year;
        }

        return 'Sudah CKG';
    }

    public function ckgStatusBadgeClass(): string
    {
        if (! $this->hasCompletedCkgScreening()) {
            return 'bg-label-danger';
        }

        if ($this->hasCkgForCurrentYear()) {
            return 'bg-label-success';
        }

        return 'bg-label-warning';
    }

    public function ckgStatusHint(): ?string
    {
        if (! $this->hasCompletedCkgScreening()) {
            return 'Belum tersinkron dari Portal CKG (belum selesai pemeriksaan atau perlu sync).';
        }

        $parts = array_filter([
            $this->ckg_registration_code ? 'Kode: '.$this->ckg_registration_code : null,
            $this->ckg_synced_at ? 'Sinkron: '.$this->ckg_synced_at->format('d/m/Y H:i') : null,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : null;
    }

    public function isWithinMcuInterval(): bool
    {
        if ($this->tanggal_mcu_terakhir === null) {
            return false;
        }

        return McuIntervalSettings::isWithinInterval(
            (int) $this->tanggal_mcu_terakhir->format('Y')
        );
    }

    public function mcuEligibleFrom(): ?Carbon
    {
        if ($this->tanggal_mcu_terakhir === null) {
            return null;
        }

        $eligibleYear = McuIntervalSettings::eligibleCalendarYear(
            (int) $this->tanggal_mcu_terakhir->format('Y')
        );

        return Carbon::create($eligibleYear, 1, 1)->startOfDay();
    }

    public function getUmurAttribute(): int
    {
        return Carbon::parse($this->tanggal_lahir)->age;
    }

    public function getKategoriUmurAttribute(): string
    {
        $umur = $this->umur;

        if ($umur < 25) {
            return '18-24 tahun';
        }
        if ($umur < 35) {
            return '25-34 tahun';
        }
        if ($umur < 45) {
            return '35-44 tahun';
        }
        if ($umur < 55) {
            return '45-54 tahun';
        }

        return '55+ tahun';
    }

    public function canScheduleMcu(): bool
    {
        if (! in_array($this->status_pegawai, ['CPNS', 'PNS', 'PPPK'], true)) {
            return false;
        }

        return ! $this->isWithinMcuInterval();
    }

    public function getStatusMcuColorAttribute(): string
    {
        return match ($this->status_mcu) {
            'Belum MCU' => 'warning',
            'Sudah MCU' => 'success',
            'Ditolak' => 'danger',
            default => 'secondary',
        };
    }

    public function getJenisKelaminTextAttribute(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    public function getTanggalLahirFormattedAttribute(): string
    {
        return $this->tanggal_lahir ? $this->tanggal_lahir->format('d/m/Y') : '-';
    }

    public function getTanggalMcuTerakhirFormattedAttribute(): string
    {
        return $this->tanggal_mcu_terakhir ? $this->tanggal_mcu_terakhir->format('d/m/Y') : '-';
    }

    public static function isPlaceholderEmail(?string $email): bool
    {
        $email = strtolower(trim((string) $email));

        if ($email === '' || $email === '-') {
            return true;
        }

        return str_ends_with($email, '@ckg-sync.local');
    }

    public function emailForForm(): string
    {
        return self::isPlaceholderEmail($this->email) ? '' : trim((string) $this->email);
    }

    /**
     * Boot the model and clear cache on changes
     */
    protected static function booted(): void
    {
        static::saved(function () {
            QueryOptimizationService::clearQueryCaches();
        });

        static::deleted(function () {
            QueryOptimizationService::clearQueryCaches();
        });
    }
}
