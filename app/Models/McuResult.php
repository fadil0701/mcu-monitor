<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class McuResult extends Model
{
    use HasFactory;

    public function getRekomendasiDokterSpesialisAttribute(): string
    {
        if (is_array($this->specialist_doctor_ids) && count($this->specialist_doctor_ids)) {
            // Cache the result to avoid repeated queries
            $cacheKey = 'specialist_doctors_' . md5(implode(',', $this->specialist_doctor_ids));
            return cache()->remember($cacheKey, 3600, function () {
                $names = \App\Models\SpecialistDoctor::whereIn('id', $this->specialist_doctor_ids)->pluck('name')->toArray();
                return implode(', ', $names);
            });
        }
        return '';
    }

    protected $fillable = [
        'participant_id',
        'schedule_id',
        'tanggal_pemeriksaan',
        'diagnosis',
        'diagnosis_list',
        'hasil_pemeriksaan',
        'status_kesehatan',
        'rekomendasi',
        'specialist_doctor_ids',
        'file_hasil',
        'file_hasil_files',
        'is_downloaded',
        'downloaded_at',
        'uploaded_by',
        'is_published',
    ];

    public function specialistDoctors()
    {
        return $this->belongsToMany(\App\Models\SpecialistDoctor::class, null, null, null, 'id', 'id')
            ->whereIn('id', $this->specialist_doctor_ids ?? []);
    }

    protected $casts = [
        'tanggal_pemeriksaan' => 'date',
        'is_downloaded' => 'boolean',
        'downloaded_at' => 'datetime',
        'file_hasil_files' => 'array',
        'diagnosis_list' => 'array',
        'specialist_doctor_ids' => 'array',
        'is_published' => 'boolean',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function schedule(): BelongsTo
    {   
        return $this->belongsTo(Schedule::class);
    }

    public function getStatusKesehatanColorAttribute(): string
    {
        return match($this->status_kesehatan) {
            'Sehat' => 'success',
            'Kurang Sehat' => 'warning',
            'Tidak Sehat' => 'danger',
            default => 'secondary',
        };
    }

    public function getTanggalPemeriksaanFormattedAttribute(): string
    {
        return $this->tanggal_pemeriksaan->format('d/m/Y');
    }

    public function getFileUrlAttribute(): string
    {
        if ($this->file_hasil) {
            return asset('storage/' . $this->file_hasil);
        }
        return '';
    }

    public function getFileUrlsAttribute(): array
    {
        $files = $this->file_hasil_files ?? [];
        return array_map(fn($path) => asset('storage/' . ltrim($path, '/')), $files);
    }

    public function markAsDownloaded(): void
    {
        $this->update([
            'is_downloaded' => true,
            'downloaded_at' => Carbon::now(),
        ]);
    }

    public function hasFile(): bool
    {
        return !empty($this->file_hasil) || !empty($this->file_hasil_files);
    }

    public function getDiagnosisTextAttribute(): string
    {
        if (is_array($this->diagnosis_list) && count($this->diagnosis_list)) {
            return implode(', ', $this->diagnosis_list);
        }
        return $this->diagnosis ?? '-';
    }

    /**
     * Boot the model and clear cache on changes
     */
    protected static function booted(): void
    {
        static::saved(function () {
            cache()->forget('dashboard_stats');
            cache()->forget('mcu_chart_data');
            cache()->forget('health_status_chart');
            cache()->forget('skpd_stats');
        });

        static::deleted(function () {
            cache()->forget('dashboard_stats');
            cache()->forget('mcu_chart_data');
            cache()->forget('health_status_chart');
            cache()->forget('skpd_stats');
        });
    }
}
