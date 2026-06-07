<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik_ktp',
        'nrk_pegawai',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'skpd',
        'ukpd',
        'no_telp',
        'email',
        'status_pegawai',
        'status_mcu',
        'tanggal_mcu_terakhir',
        'catatan',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_mcu_terakhir' => 'date',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function mcuResults(): HasMany
    {
        return $this->hasMany(McuResult::class);
    }

    public function getUmurAttribute(): int
    {
        return Carbon::parse($this->tanggal_lahir)->age;
    }

    public function getKategoriUmurAttribute(): string
    {
        $umur = $this->umur;
        
        if ($umur < 25) return '18-24 tahun';
        if ($umur < 35) return '25-34 tahun';
        if ($umur < 45) return '35-44 tahun';
        if ($umur < 55) return '45-54 tahun';
        return '55+ tahun';
    }

    public function canScheduleMcu(): bool
    {
        // Cek apakah status pegawai sesuai
        if (!in_array($this->status_pegawai, ['CPNS', 'PNS', 'PPPK'])) {
            return false;
        }

        // Cek apakah sudah pernah MCU dalam 3 tahun terakhir
        if ($this->tanggal_mcu_terakhir) {
            $threeYearsAgo = Carbon::now()->subYears(3);
            if ($this->tanggal_mcu_terakhir->gt($threeYearsAgo)) {
                return false;
            }
        }

        return true;
    }

    public function getStatusMcuColorAttribute(): string
    {
        return match($this->status_mcu) {
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

    /**
     * Boot the model and clear cache on changes
     */
    protected static function booted(): void
    {
        static::saved(function () {
            cache()->forget('dashboard_stats');
            cache()->forget('skpd_stats');
            cache()->forget('mcu_chart_data');
        });

        static::deleted(function () {
            cache()->forget('dashboard_stats');
            cache()->forget('skpd_stats');
            cache()->forget('mcu_chart_data');
        });
    }
}
