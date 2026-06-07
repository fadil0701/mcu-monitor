<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Schedule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function sendMcuInvitation(Schedule $schedule): bool
    {
        try {
            // Configure SMTP settings
            $this->configureMailSettings();
            
            // Get email template from Settings (new simple template)
            $subject = Setting::getValue('email_invitation_subject', 'Undangan Medical Check Up');
            $template = Setting::getValue('email_invitation_template', 'Kepada {nama_lengkap}, Anda diundang untuk mengikuti Medical Check Up.');
            
            // Prepare template data
            $templateData = $this->prepareTemplateData($schedule);
            
            // Render template (replace variables)
            $renderedSubject = $this->renderTemplate($subject, $templateData);
            $renderedBody = $this->renderTemplate($template, $templateData);
            
            // Send plain text email (no PDF attachment)
            Mail::raw($renderedBody, function ($message) use ($schedule, $renderedSubject) {
                $message->to($schedule->email)
                    ->subject($renderedSubject);
            });

            // Update schedule
            $schedule->update([
                'email_sent' => true,
                'email_sent_at' => now(),
            ]);

            Log::info("Email invitation sent successfully to {$schedule->email}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email invitation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Configure mail settings from Settings table
     */
    private function configureMailSettings(): void
    {
        $smtpSettings = Setting::getGroup('smtp');
        
        Config::set('mail.mailers.smtp.host', $smtpSettings['smtp_host'] ?? env('MAIL_HOST', 'smtp.gmail.com'));
        Config::set('mail.mailers.smtp.port', $smtpSettings['smtp_port'] ?? env('MAIL_PORT', 587));
        Config::set('mail.mailers.smtp.username', $smtpSettings['smtp_username'] ?? env('MAIL_USERNAME', ''));
        Config::set('mail.mailers.smtp.password', $smtpSettings['smtp_password'] ?? env('MAIL_PASSWORD', ''));
        Config::set('mail.mailers.smtp.encryption', $smtpSettings['smtp_encryption'] ?? env('MAIL_ENCRYPTION', 'tls'));
        Config::set('mail.from.address', $smtpSettings['smtp_from_address'] ?? env('MAIL_FROM_ADDRESS', 'noreply@mcu.local'));
        Config::set('mail.from.name', $smtpSettings['smtp_from_name'] ?? env('MAIL_FROM_NAME', 'Sistem MCU'));
    }

    /**
     * Prepare template data from schedule
     */
    private function prepareTemplateData(Schedule $schedule): array
    {
        return [
            'nama_lengkap' => $schedule->nama_lengkap,
            'nik_ktp' => $schedule->nik_ktp,
            'nrk_pegawai' => $schedule->nrk_pegawai,
            'tanggal_lahir' => $schedule->tanggal_lahir ? $schedule->tanggal_lahir->format('d/m/Y') : '-',
            'jenis_kelamin' => $schedule->jenis_kelamin === 'L' ? 'Laki-Laki' : 'Perempuan',
            'tanggal_pemeriksaan' => $schedule->tanggal_pemeriksaan ? $schedule->tanggal_pemeriksaan->format('d/m/Y') : '-',
            'hari_pemeriksaan' => $schedule->tanggal_pemeriksaan ? $schedule->tanggal_pemeriksaan->locale('id')->dayName : '-',
            'jam_pemeriksaan' => $schedule->jam_pemeriksaan ? $schedule->jam_pemeriksaan->format('H:i') : '-',
            'lokasi_pemeriksaan' => $schedule->lokasi_pemeriksaan,
            'queue_number' => $schedule->queue_number,
            'skpd' => $schedule->skpd,
            'ukpd' => $schedule->ukpd,
            'no_telp' => $schedule->no_telp,
            'email' => $schedule->email,
        ];
    }

    /**
     * Render template by replacing variables
     */
    private function renderTemplate(string $template, array $data): string
    {
        $rendered = $template;
        
        foreach ($data as $key => $value) {
            $rendered = str_replace('{' . $key . '}', $value, $rendered);
        }
        
        return $rendered;
    }

    /**
     * Send reminder (legacy method for backward compatibility)
     */
    public function sendReminder(Schedule $schedule, $template = null): bool
    {
        // Use the same invitation method
        return $this->sendMcuInvitation($schedule);
    }
}