<?php

namespace App\Services;

use App\Models\PdfTemplate;
use App\Models\Schedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    /**
     * Generate PDF from template and schedule data
     */
    public function generateMcuLetter(Schedule $schedule, ?PdfTemplate $template = null): string
    {
        // Get template (use provided template or default)
        if (!$template) {
            $template = PdfTemplate::getDefault('mcu_letter');
        }

        // Prepare template data
        $templateData = $this->prepareTemplateData($schedule);
        
        // Render template
        $rendered = $template ? $template->render($templateData) : $this->getFallbackTemplate($schedule);
        
        // Generate PDF
        $pdf = Pdf::loadHTML($this->buildHtmlDocument($rendered, $template));
        
        // Apply PDF settings
        $settings = $rendered['settings'];
        $pdf->setPaper($settings['paper'], $settings['orientation']);
        $pdf->setOptions([
            'margin_top' => $settings['margin_top'],
            'margin_bottom' => $settings['margin_bottom'],
            'margin_left' => $settings['margin_left'],
            'margin_right' => $settings['margin_right'],
            'defaultFont' => $settings['font_family'] ?? 'DejaVu Sans',
        ]);

        // Save PDF to storage
        $filename = 'mcu_letter_' . $schedule->id . '_' . time() . '.pdf';
        $filepath = 'pdfs/' . $filename;
        
        Storage::disk('public')->put($filepath, $pdf->output());
        
        return Storage::disk('public')->path($filepath);
    }

    /**
     * Generate PDF content as string (for email attachment)
     */
    public function generateMcuLetterContent(Schedule $schedule, ?PdfTemplate $template = null): string
    {
        // Get template (use provided template or default)
        if (!$template) {
            $template = PdfTemplate::getDefault('mcu_letter');
        }

        // Prepare template data
        $templateData = $this->prepareTemplateData($schedule);
        
        // Render template
        $rendered = $template ? $template->render($templateData) : $this->getFallbackTemplate($schedule);
        
        // Generate PDF
        $pdf = Pdf::loadHTML($this->buildHtmlDocument($rendered, $template));
        
        // Apply PDF settings
        $settings = $rendered['settings'];
        $pdf->setPaper($settings['paper'], $settings['orientation']);
        $pdf->setOptions([
            'margin_top' => $settings['margin_top'],
            'margin_bottom' => $settings['margin_bottom'],
            'margin_left' => $settings['margin_left'],
            'margin_right' => $settings['margin_right'],
            'defaultFont' => $settings['font_family'] ?? 'DejaVu Sans',
        ]);

        return $pdf->output();
    }

    /**
     * Prepare template data from schedule
     */
    private function prepareTemplateData(Schedule $schedule): array
    {
        return [
            'letter_number' => $this->generateLetterNumber($schedule),
            'letter_date' => now()->format('d F Y'),
            'participant_name' => $schedule->nama_lengkap,
            'participant_nik' => $schedule->nik_ktp,
            'participant_birth_date' => $schedule->tanggal_lahir ? $schedule->tanggal_lahir->format('d/m/Y') : '',
            'participant_gender' => $schedule->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
            'participant_skpd' => $schedule->skpd,
            'participant_unit' => $schedule->ukpd,
            'examination_day' => $schedule->tanggal_pemeriksaan->locale('id')->dayName,
            'examination_date' => $schedule->tanggal_pemeriksaan->format('d F Y'),
            'examination_time' => $schedule->jam_pemeriksaan->format('H:i') . ' WIB s.d Selesai',
            'examination_location' => $schedule->lokasi_pemeriksaan,
            'contact_person' => 'dr. Lenny Hertidamai',
            'contact_phone' => '08119451978',
            'organization_name' => 'PEMERINTAH PROVINSI DAERAH KHUSUS IBUKOTA JAKARTA',
            'organization_subtitle' => 'DINAS KESEHATAN',
            'organization_subtitle2' => 'PUSAT PELAYANAN KESEHATAN PEGAWAI',
            'organization_address' => 'JL. Medan Merdeka Selatan 8-9 Blok E Lantai.2, Jakarta Pusat',
            'organization_phone' => '021-3823065',
            'organization_fax' => '021-3453351',
            'organization_email' => 'puspelkes@jakarta.go.id',
            'organization_postal_code' => '10110',
            'signature_name' => 'dr. Dwian Andhika',
            'signature_title' => 'Kepala Pusat Pelayanan Kesehatan Pegawai',
            'signature_nip' => '198311072010011021',
        ];
    }

    /**
     * Generate letter number
     */
    private function generateLetterNumber(Schedule $schedule): string
    {
        $year = now()->year;
        $month = now()->month;
        $day = now()->day;
        
        // Format: 297/KG.12.00/3615-CK
        return sprintf('%d/KG.12.00/3615-CK', rand(200, 999));
    }

    /**
     * Get fallback template when no template is available
     */
    private function getFallbackTemplate(Schedule $schedule): array
    {
        return [
            'title' => 'Surat Undangan MCU',
            'header_html' => '',
            'body_html' => $this->getDefaultBodyHtml($schedule),
            'footer_html' => $this->getDefaultFooterHtml(),
            'settings' => [
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
     * Build complete HTML document
     */
    private function buildHtmlDocument(array $rendered, ?PdfTemplate $template = null): string
    {
        $css = file_get_contents(resource_path('views/pdf-templates/styles.css'));
        
        return '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $rendered['title'] . '</title>
    <style>' . $css . '</style>
</head>
<body>
    <div class="container">
        ' . $this->processImagesInHtml($rendered['header_html'], $template) . '
        <div class="content">
            ' . $this->processImagesInHtml($rendered['body_html'], $template) . '
        </div>
        ' . $this->processImagesInHtml($rendered['footer_html'], $template) . '
    </div>
</body>
</html>';
    }

    /**
     * Process images in HTML content
     */
    private function processImagesInHtml(string $html, ?PdfTemplate $template = null): string
    {
        if (!$template) {
            return $html;
        }

        // Replace image placeholders with base64 encoded images
        $html = str_replace('{logo_image}', $this->getImageBase64($template, 'logo'), $html);
        $html = str_replace('{signature_image}', $this->getImageBase64($template, 'signature'), $html);
        $html = str_replace('{stamp_image}', $this->getImageBase64($template, 'stamp'), $html);
        
        return $html;
    }

    /**
     * Get image as base64 encoded string for PDF generation
     */
    private function getImageBase64(PdfTemplate $template, string $type): string
    {
        $imagePath = '';
        
        switch ($type) {
            case 'logo':
                $imagePath = $template->logo_path;
                break;
            case 'signature':
                $imagePath = $template->signature_image_path;
                break;
            case 'stamp':
                $imagePath = $template->stamp_image_path;
                break;
        }

        if ($imagePath && file_exists(storage_path('app/public/' . $imagePath))) {
            $fullPath = storage_path('app/public/' . $imagePath);
            
            // Check file size first
            $fileSize = filesize($fullPath);
            if ($fileSize > 500000) { // 500KB limit
                \Log::warning("Image too large for PDF: {$fullPath} ({$fileSize} bytes)");
                return '';
            }
            
            // Try to get optimized image
            $optimizedImage = $this->optimizeImageForPdf($fullPath, $type);
            
            if ($optimizedImage) {
                $mimeType = 'image/png'; // Always use PNG for optimized images
                $base64 = base64_encode($optimizedImage);
                return '<img src="data:' . $mimeType . ';base64,' . $base64 . '" class="' . $type . '-image" alt="' . ucfirst($type) . '">';
            }
        }

        return '';
    }

    /**
     * Optimize image for PDF generation
     */
    private function optimizeImageForPdf(string $imagePath, string $type): ?string
    {
        try {
            // Get image info
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return null;
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $mimeType = $imageInfo['mime'];

            // Set max dimensions based on type
            $maxWidth = 200;
            $maxHeight = 200;
            
            switch ($type) {
                case 'logo':
                    $maxWidth = 120;
                    $maxHeight = 120;
                    break;
                case 'signature':
                    $maxWidth = 240;
                    $maxHeight = 160;
                    break;
                case 'stamp':
                    $maxWidth = 160;
                    $maxHeight = 160;
                    break;
            }

            // Calculate new dimensions
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = intval($width * $ratio);
            $newHeight = intval($height * $ratio);

            // Create source image with error suppression for iCCP warnings
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = @imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $sourceImage = @imagecreatefrompng($imagePath);
                    break;
                case 'image/gif':
                    $sourceImage = @imagecreatefromgif($imagePath);
                    break;
                default:
                    return null;
            }

            if (!$sourceImage) {
                return null;
            }

            // Create new image
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Resize image
            imagecopyresampled(
                $newImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $width, $height
            );

            // Output to string
            ob_start();
            imagepng($newImage, null, 6); // Quality 6 (0-9)
            $imageData = ob_get_contents();
            ob_end_clean();

            // Clean up
            imagedestroy($sourceImage);
            imagedestroy($newImage);

            return $imageData;

        } catch (\Exception $e) {
            \Log::error("Failed to optimize image: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get default body HTML
     */
    private function getDefaultBodyHtml(Schedule $schedule): string
    {
        $participantName = $schedule->nama_lengkap;
        $examinationDate = $schedule->tanggal_pemeriksaan->format('d F Y');
        $examinationTime = $schedule->jam_pemeriksaan->format('H:i') . ' WIB s.d Selesai';
        $examinationLocation = $schedule->lokasi_pemeriksaan;
        
        return "
        <h3>Pemberitahuan Pemeriksaan Medical Check Up (MCU)</h3>
        
        <p>Dengan hormat,</p>
        
        <p>Berdasarkan hasil Cek Kesehatan Gratis (CKG) Pegawai Pemerintah Provinsi Daerah Khusus Ibukota Jakarta, disampaikan beberapa hal sebagai berikut:</p>
        
        <div class=\"participant-info\">
            <table>
                <tr><td>NIK KTP:</td><td>{$schedule->nik_ktp}</td></tr>
                <tr><td>Nama Lengkap:</td><td>{$participantName}</td></tr>
                <tr><td>Tanggal Lahir:</td><td>{$schedule->tanggal_lahir->format('d/m/Y')}</td></tr>
                <tr><td>Jenis Kelamin:</td><td>" . ($schedule->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan') . "</td></tr>
                <tr><td>SKPD:</td><td>{$schedule->skpd}</td></tr>
                <tr><td>Unit Kerja:</td><td>{$schedule->ukpd}</td></tr>
            </table>
        </div>
        
        <div class=\"examination-details\">
            <table>
                <tr><td>Hari:</td><td>{$schedule->tanggal_pemeriksaan->locale('id')->dayName}</td></tr>
                <tr><td>Tanggal:</td><td>{$examinationDate}</td></tr>
                <tr><td>Waktu:</td><td>{$examinationTime}</td></tr>
                <tr><td>Tempat:</td><td>{$examinationLocation}</td></tr>
            </table>
        </div>
        
        <div class=\"contact-info\">
            <p>Untuk konfirmasi lebih lanjut dapat menghubungi <strong>dr. Lenny Hertidamai (Hp. 08119451978)</strong> selaku PIC kegiatan.</p>
        </div>
        
        <p>Demikian surat pemberitahuan ini disampaikan, atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>
        ";
    }

    /**
     * Get default footer HTML
     */
    private function getDefaultFooterHtml(): string
    {
        return "
        <div class=\"footer\">
            <div class=\"signature\">
                <div class=\"signature-name\">dr. Dwian Andhika</div>
                <div class=\"signature-title\">Kepala Pusat Pelayanan Kesehatan Pegawai</div>
                <div class=\"signature-nip\">NIP. 198311072010011021</div>
            </div>
            <div class=\"stamp\">
                PEMERINTAH<br>
                PROVINSI DKI<br>
                JAKARTA<br>
                PUSAT PELAYANAN<br>
                KESEHATAN PEGAWAI
            </div>
        </div>
        ";
    }
}
