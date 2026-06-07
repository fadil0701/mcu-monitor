<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'is_active',
        'is_default',
        'description',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Get the default template for a specific type
     */
    public static function getDefault($type)
    {
        return static::where('type', $type)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all active templates for a specific type
     */
    public static function getByType($type)
    {
        return static::where('type', $type)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Set as default template
     */
    public function setAsDefault()
    {
        // Remove default from other templates of same type
        static::where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this template as default
        $this->update(['is_default' => true]);
    }

    /**
     * Daftar variabel template per tipe (untuk form admin).
     */
    public static function getVariablesByType(): array
    {
        return [
            'mcu_invitation' => [
                'nama_lengkap' => 'Nama peserta',
                'nik_ktp' => 'NIK KTP',
                'nrk_pegawai' => 'NRK pegawai',
                'tanggal_lahir' => 'Tanggal lahir',
                'jenis_kelamin' => 'Jenis kelamin',
                'tanggal_pemeriksaan' => 'Tanggal pemeriksaan',
                'hari_pemeriksaan' => 'Hari pemeriksaan',
                'jam_pemeriksaan' => 'Jam pemeriksaan',
                'lokasi_pemeriksaan' => 'Lokasi pemeriksaan',
                'queue_number' => 'Nomor antrian',
                'skpd' => 'SKPD',
                'ukpd' => 'UKPD',
                'no_telp' => 'Nomor telepon',
                'email' => 'Email peserta',
            ],
            'reminder' => [
                'nama_lengkap' => 'Nama peserta',
                'tanggal_pemeriksaan' => 'Tanggal pemeriksaan',
                'jam_pemeriksaan' => 'Jam pemeriksaan',
                'lokasi_pemeriksaan' => 'Lokasi pemeriksaan',
                'queue_number' => 'Nomor antrian',
            ],
            'notification' => [
                'nama_lengkap' => 'Nama peserta',
                'pesan' => 'Isi notifikasi',
                'app_name' => 'Nama aplikasi',
            ],
            'mcu_result' => [
                'participant_name' => 'Nama peserta',
                'participant_email' => 'Email peserta',
                'participant_phone' => 'Nomor telepon',
                'tanggal_pemeriksaan' => 'Tanggal pemeriksaan',
                'status_kesehatan' => 'Status kesehatan',
                'diagnosis' => 'Diagnosis',
                'rekomendasi' => 'Rekomendasi',
                'hasil_url' => 'Link hasil MCU',
                'app_name' => 'Nama aplikasi',
            ],
            'custom' => [],
        ];
    }

    /**
     * Get available variables for this template type
     */
    public function getAvailableVariables()
    {
        return static::getVariablesByType()[$this->type] ?? [];
    }

    /**
     * Render template with data
     */
    public function render($data = [])
    {
        $subject = $this->subject;
        $bodyHtml = $this->body_html;
        $bodyText = $this->body_text;

        // Replace variables in subject and body
        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $subject = str_replace($placeholder, $value, $subject);
            $bodyHtml = str_replace($placeholder, $value, $bodyHtml);
            $bodyText = str_replace($placeholder, $value, $bodyText);
        }

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }
}
