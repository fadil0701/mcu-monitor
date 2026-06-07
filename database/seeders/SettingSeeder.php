<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SMTP Settings
        // NOTE: Konfigurasi SMTP dari cPanel dapat dilakukan via command: php artisan smtp:configure --interactive
        // Atau update settings dengan group 'smtp' via admin panel atau database
        $this->setValueIfNotExists('smtp_host', 'mail.puspelkesdki.id', 'string', 'smtp', 'SMTP Host');
        $this->setValueIfNotExists('smtp_port', '465', 'string', 'smtp', 'SMTP Port');
        $this->setValueIfNotExists('smtp_username', 'mcu@puspelkesdki.id', 'string', 'smtp', 'SMTP Username');
        $this->setValueIfNotExists('smtp_password', '', 'string', 'smtp', 'SMTP Password');
        $this->setValueIfNotExists('smtp_encryption', 'ssl', 'string', 'smtp', 'SMTP Encryption (ssl/tls)');
        $this->setValueIfNotExists('smtp_from_address', 'mcu@puspelkesdki.id', 'string', 'smtp', 'SMTP From Address');
        $this->setValueIfNotExists('smtp_from_name', 'Sistem Monitoring MCU PPKP', 'string', 'smtp', 'SMTP From Name');

        // WhatsApp Settings
        $this->setValueIfNotExists('whatsapp_provider', 'fonnte', 'string', 'whatsapp', 'WhatsApp Provider (fonnte, wablas, meta)');
        $this->setValueIfNotExists('whatsapp_token', '', 'string', 'whatsapp', 'WhatsApp API Token');
        $this->setValueIfNotExists('whatsapp_instance_id', '', 'string', 'whatsapp', 'WhatsApp Instance ID (untuk Meta)');
        $this->setValueIfNotExists('whatsapp_phone_number', '', 'string', 'whatsapp', 'WhatsApp Phone Number');

        // Email Template (Undangan saja)
        $this->setValueIfNotExists('email_invitation_subject', 'Undangan Medical Check Up', 'string', 'email_template', 'Subject Email Undangan');
        $this->setValueIfNotExists('email_invitation_template', 'Kepada Yth. {nama_lengkap}

Dengan hormat,

Kami mengundang Bapak/Ibu untuk mengikuti Medical Check Up yang akan dilaksanakan pada:

Tanggal: {tanggal_pemeriksaan}
Waktu: {jam_pemeriksaan}
Lokasi: {lokasi_pemeriksaan}
Nomor Antrian: {queue_number}

CATATAN PENTING:
1. Harap hadir 15 menit sebelum jadwal
2. Membawa KTP/kartu identitas
3. Puasa 8 jam sebelum pemeriksaan
4. Menggunakan pakaian yang nyaman

Mohon konfirmasi kehadiran Anda melalui sistem atau hubungi kami jika berhalangan hadir.

Terima kasih atas perhatian dan kerjasamanya.

Hormat kami,
Tim Medical Check Up', 'text', 'email_template', 'Template Email Undangan');

        // WhatsApp Template (Undangan saja)
        $this->setValueIfNotExists('whatsapp_invitation_template', 'Halo {nama_lengkap},

Anda diundang untuk mengikuti Medical Check Up pada:
📅 Tanggal: {tanggal_pemeriksaan}
🕐 Jam: {jam_pemeriksaan}
📍 Lokasi: {lokasi_pemeriksaan}
🎫 Nomor Antrian: {queue_number}

*Catatan Penting:*
• Hadir 15 menit lebih awal
• Bawa KTP/kartu identitas
• Puasa 8 jam sebelumnya

Mohon hadir tepat waktu.

Terima kasih.', 'text', 'whatsapp_template', 'Template WhatsApp Undangan');

        // General Settings
        $this->setValueIfNotExists('app_name', 'Sistem Monitoring MCU', 'string', 'general', 'Nama Aplikasi');
        $this->setValueIfNotExists('app_description', 'Sistem Monitoring Medical Check Up', 'string', 'general', 'Deskripsi Aplikasi');
        $this->setValueIfNotExists('mcu_interval_years', '3', 'string', 'general', 'Interval MCU (Tahun)');
    }

    /**
     * Set value only if it doesn't exist
     */
    private function setValueIfNotExists($key, $value, $type, $group, $description)
    {
        $existing = Setting::where('key', $key)->first();
        if (!$existing) {
            Setting::setValue($key, $value, $type, $group, $description);
        }
    }
}
