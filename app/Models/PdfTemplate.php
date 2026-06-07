<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PdfTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'title',
        'combined_html',
        'header_html',
        'body_html',
        'footer_html',
        'variables',
        'settings',
        'logo_path',
        'signature_image_path',
        'stamp_image_path',
        'image_settings',
        'is_active',
        'is_default',
        'description',
    ];

    protected $casts = [
        'variables' => 'array',
        'settings' => 'array',
        'image_settings' => 'array',
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
            'mcu_letter' => [
                'letter_number' => 'Nomor surat',
                'letter_date' => 'Tanggal surat',
                'participant_name' => 'Nama peserta',
                'participant_nik' => 'NIK peserta',
                'participant_birth_date' => 'Tanggal lahir peserta',
                'participant_gender' => 'Jenis kelamin peserta',
                'participant_skpd' => 'SKPD peserta',
                'participant_unit' => 'Unit kerja peserta',
                'examination_day' => 'Hari pemeriksaan',
                'examination_date' => 'Tanggal pemeriksaan',
                'examination_time' => 'Waktu pemeriksaan',
                'examination_location' => 'Lokasi pemeriksaan',
                'contact_person' => 'Nama PIC',
                'contact_phone' => 'Nomor telepon PIC',
                'organization_name' => 'Nama organisasi',
                'organization_address' => 'Alamat organisasi',
                'organization_phone' => 'Nomor telepon organisasi',
                'organization_fax' => 'Nomor fax organisasi',
                'organization_email' => 'Email organisasi',
                'signature_name' => 'Nama penandatangan',
                'signature_title' => 'Jabatan penandatangan',
                'signature_nip' => 'NIP penandatangan',
            ],
            'reminder_letter' => [
                'letter_number' => 'Nomor surat',
                'letter_date' => 'Tanggal surat',
                'participant_name' => 'Nama peserta',
                'examination_date' => 'Tanggal pemeriksaan',
                'examination_time' => 'Waktu pemeriksaan',
                'examination_location' => 'Lokasi pemeriksaan',
                'organization_name' => 'Nama organisasi',
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
        // If combined_html exists, use it and split it
        if (!empty($this->combined_html)) {
            $combinedHtml = $this->combined_html;
            
            // Replace variables in combined HTML
            foreach ($data as $key => $value) {
                $placeholder = '{' . $key . '}';
                $combinedHtml = str_replace($placeholder, $value, $combinedHtml);
            }
            
            // Split combined HTML into parts
            $parts = $this->splitCombinedHtml($combinedHtml);
            
            return [
                'title' => $this->title,
                'header_html' => $parts['header'],
                'body_html' => $parts['body'],
                'footer_html' => $parts['footer'],
                'settings' => $this->settings ?? [
                    'paper' => 'A4',
                    'orientation' => 'portrait',
                    'margin_top' => 20,
                    'margin_bottom' => 20,
                    'margin_left' => 20,
                    'margin_right' => 20,
                ],
            ];
        }
        
        // Fallback to individual HTML sections
        $headerHtml = $this->header_html;
        $bodyHtml = $this->body_html;
        $footerHtml = $this->footer_html;

        // Replace variables in all HTML sections
        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $headerHtml = str_replace($placeholder, $value, $headerHtml);
            $bodyHtml = str_replace($placeholder, $value, $bodyHtml);
            $footerHtml = str_replace($placeholder, $value, $footerHtml);
        }

        return [
            'title' => $this->title,
            'header_html' => $headerHtml,
            'body_html' => $bodyHtml,
            'footer_html' => $footerHtml,
            'settings' => $this->settings ?? [
                'paper' => 'A4',
                'orientation' => 'portrait',
                'margin_top' => 20,
                'margin_bottom' => 20,
                'margin_left' => 20,
                'margin_right' => 20,
            ],
        ];
    }
    
    /**
     * Split combined HTML into header, body, footer parts
     */
    private function splitCombinedHtml(string $combinedHtml): array
    {
        $parts = [
            'header' => '',
            'body' => '',
            'footer' => ''
        ];
        
        if (strpos($combinedHtml, '<!-- HEADER_END -->') !== false) {
            $split = explode('<!-- HEADER_END -->', $combinedHtml, 2);
            $parts['header'] = trim($split[0]);
            $remaining = $split[1] ?? '';
            
            if (strpos($remaining, '<!-- FOOTER_START -->') !== false) {
                $split2 = explode('<!-- FOOTER_START -->', $remaining, 2);
                $parts['body'] = trim($split2[0]);
                $parts['footer'] = trim($split2[1] ?? '');
            } else {
                $parts['body'] = trim($remaining);
            }
        } else {
            // If no markers, treat everything as body
            $parts['body'] = trim($combinedHtml);
        }
        
        return $parts;
    }

    /**
     * Get PDF settings with defaults
     */
    public function getPdfSettings()
    {
        $defaults = [
            'paper' => 'A4',
            'orientation' => 'portrait',
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 20,
            'margin_right' => 20,
            'font_family' => 'DejaVu Sans',
            'font_size' => 12,
        ];

        return array_merge($defaults, $this->settings ?? []);
    }
}
