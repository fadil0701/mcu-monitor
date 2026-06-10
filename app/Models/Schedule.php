<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'nik_ktp',
        'nrk_pegawai',
        'nama_lengkap',
        'tanggal_lahir',
        'jenis_kelamin',
        'skpd',
        'ukpd',
        'no_telp',
        'email',
        'tanggal_pemeriksaan',
        'jam_pemeriksaan',
        'lokasi_pemeriksaan',
        'status',
        'queue_number',
        'participant_confirmed',
        'participant_confirmed_at',
        'reschedule_requested',
        'reschedule_new_date',
        'reschedule_new_time',
        'reschedule_reason',
        'reschedule_requested_at',
        'email_sent',
        'whatsapp_sent',
        'email_sent_at',
        'whatsapp_sent_at',
        'catatan',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_pemeriksaan' => 'date',
        'jam_pemeriksaan' => 'datetime:H:i',
        'email_sent' => 'boolean',
        'whatsapp_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
        'reschedule_requested' => 'boolean',
        'reschedule_new_date' => 'date',
        'reschedule_new_time' => 'datetime:H:i',
        'reschedule_requested_at' => 'datetime',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function mcuResult(): HasOne
    {
        return $this->hasOne(McuResult::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Terjadwal' => 'warning',
            'Selesai' => 'success',
            'Batal' => 'danger',
            default => 'secondary',
        };
    }

    public function getJenisKelaminTextAttribute(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    public function getTanggalPemeriksaanFormattedAttribute(): string
    {
        return $this->tanggal_pemeriksaan->format('d/m/Y');
    }

    public function getJamPemeriksaanFormattedAttribute(): string
    {
        return $this->jam_pemeriksaan->format('H:i');
    }

    public function getDateTimePemeriksaanAttribute(): string
    {
        return $this->tanggal_pemeriksaan->format('d/m/Y') . ' ' . $this->jam_pemeriksaan->format('H:i');
    }

    public function isToday(): bool
    {
        return $this->tanggal_pemeriksaan->isToday();
    }

    public function isPast(): bool
    {
        return $this->tanggal_pemeriksaan->isPast();
    }

    public function isFuture(): bool
    {
        return $this->tanggal_pemeriksaan->isFuture();
    }

    /**
     * Boot the model and clear cache on changes
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \App\Services\QueryOptimizationService::clearQueryCaches();
        });

        static::deleted(function () {
            \App\Services\QueryOptimizationService::clearQueryCaches();
        });
    }

    public static function hasQuotaAvailable(string $date, string $location, ?int $excludeId = null): bool
    {
        $quota = config('mcu.daily_quota', 100);
        if ($quota <= 0) {
            return true;
        }

        $query = static::query()
            ->whereDate('tanggal_pemeriksaan', $date)
            ->where('lokasi_pemeriksaan', $location)
            ->whereIn('status', ['Terjadwal', 'Selesai']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->count() < $quota;
    }

    public static function getNextQueueNumber(string $date, string $location, ?int $excludeId = null): int
    {
        $query = static::query()
            ->whereDate('tanggal_pemeriksaan', $date)
            ->where('lokasi_pemeriksaan', $location)
            ->whereIn('status', ['Terjadwal', 'Selesai']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $max = (int) $query->max('queue_number');

        return $max + 1;
    }
}
