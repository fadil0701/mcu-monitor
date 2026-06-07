<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // MCU Invitation Template
        EmailTemplate::create([
            'name' => 'Default MCU Invitation',
            'type' => 'mcu_invitation',
            'subject' => 'Undangan Medical Check Up - {participant_name}',
            'body_html' => '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Medical Check Up</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 300; }
        .content { padding: 30px; }
        .content h2 { color: #333; margin-top: 0; }
        .info-box { background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0; border-radius: 0 5px 5px 0; }
        .info-item { margin: 10px 0; }
        .info-label { font-weight: bold; color: #555; }
        .queue-number { background: #667eea; color: white; padding: 5px 10px; border-radius: 15px; font-weight: bold; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 Undangan Medical Check Up</h1>
        </div>
        <div class="content">
            <h2>Kepada Yth. Bapak/Ibu <strong>{participant_name}</strong></h2>
            
            <p>Dengan hormat,</p>
            
            <p>Anda diundang untuk mengikuti kegiatan Medical Check Up (MCU) yang akan diselenggarakan dengan detail sebagai berikut:</p>
            
            <div class="info-box">
                <div class="info-item">
                    <span class="info-label">📅 Tanggal:</span> {examination_date}
                </div>
                <div class="info-item">
                    <span class="info-label">🕐 Waktu:</span> {examination_time}
                </div>
                <div class="info-item">
                    <span class="info-label">📍 Lokasi:</span> {examination_location}
                </div>
                <div class="info-item">
                    <span class="info-label">🎫 Nomor Antrian:</span> 
                    <span class="queue-number">{queue_number}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">🏢 SKPD:</span> {skpd}
                </div>
                <div class="info-item">
                    <span class="info-label">📋 UKPD:</span> {ukpd}
                </div>
            </div>
            
            <p><strong>Mohon untuk:</strong></p>
            <ul>
                <li>Hadir tepat waktu sesuai jadwal yang telah ditentukan</li>
                <li>Membawa dokumen identitas (KTP/NRK)</li>
                <li>Berpuasa 8-12 jam sebelum pemeriksaan (jika diperlukan)</li>
                <li>Menghubungi kami jika ada halangan atau perubahan jadwal</li>
            </ul>
            
            <p>Terima kasih atas perhatian dan partisipasi Anda dalam program Medical Check Up ini.</p>
        </div>
        <div class="footer">
            <p><strong>Hormat kami,</strong><br>
            Tim {app_name}</p>
            <p>📧 Email: {participant_email} | 📞 Telp: {participant_phone}</p>
        </div>
    </div>
</body>
</html>',
            'body_text' => 'UNDANGAN MEDICAL CHECK UP

Kepada Yth. Bapak/Ibu {participant_name}

Dengan hormat,

Anda diundang untuk mengikuti kegiatan Medical Check Up (MCU) yang akan diselenggarakan dengan detail sebagai berikut:

📅 Tanggal: {examination_date}
🕐 Waktu: {examination_time}
📍 Lokasi: {examination_location}
🎫 Nomor Antrian: {queue_number}
🏢 SKPD: {skpd}
📋 UKPD: {ukpd}

Mohon untuk:
- Hadir tepat waktu sesuai jadwal yang telah ditentukan
- Membawa dokumen identitas (KTP/NRK)
- Berpuasa 8-12 jam sebelum pemeriksaan (jika diperlukan)
- Menghubungi kami jika ada halangan atau perubahan jadwal

Terima kasih atas perhatian dan partisipasi Anda dalam program Medical Check Up ini.

Hormat kami,
Tim {app_name}

📧 Email: {participant_email} | 📞 Telp: {participant_phone}',
            'variables' => [
                'participant_name' => 'Nama peserta',
                'participant_email' => 'Email peserta',
                'participant_phone' => 'Nomor telepon peserta',
                'examination_date' => 'Tanggal pemeriksaan',
                'examination_time' => 'Jam pemeriksaan',
                'examination_location' => 'Lokasi pemeriksaan',
                'queue_number' => 'Nomor antrian',
                'skpd' => 'SKPD',
                'ukpd' => 'UKPD',
                'app_name' => 'Nama aplikasi',
            ],
            'is_active' => true,
            'is_default' => true,
            'description' => 'Template default untuk undangan Medical Check Up dengan format HTML dan plain text',
        ]);

        // Reminder Template
        EmailTemplate::create([
            'name' => 'MCU Reminder',
            'type' => 'reminder',
            'subject' => 'Pengingat: MCU Besok - {participant_name}',
            'body_html' => '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengingat MCU</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 300; }
        .content { padding: 30px; }
        .reminder-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .info-item { margin: 10px 0; }
        .info-label { font-weight: bold; color: #856404; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⏰ Pengingat MCU</h1>
        </div>
        <div class="content">
            <h2>Halo {participant_name},</h2>
            
            <p>Ini adalah pengingat bahwa Anda memiliki jadwal Medical Check Up besok:</p>
            
            <div class="reminder-box">
                <div class="info-item">
                    <span class="info-label">📅 Tanggal:</span> {examination_date}
                </div>
                <div class="info-item">
                    <span class="info-label">🕐 Waktu:</span> {examination_time}
                </div>
                <div class="info-item">
                    <span class="info-label">📍 Lokasi:</span> {examination_location}
                </div>
            </div>
            
            <p><strong>Jangan lupa:</strong></p>
            <ul>
                <li>Berpuasa 8-12 jam sebelum pemeriksaan</li>
                <li>Membawa KTP/NRK</li>
                <li>Datang 15 menit sebelum jadwal</li>
            </ul>
            
            <p>Terima kasih dan sampai jumpa besok!</p>
        </div>
        <div class="footer">
            <p><strong>Tim {app_name}</strong></p>
        </div>
    </div>
</body>
</html>',
            'body_text' => 'PENGINGAT MEDICAL CHECK UP

Halo {participant_name},

Ini adalah pengingat bahwa Anda memiliki jadwal Medical Check Up besok:

📅 Tanggal: {examination_date}
🕐 Waktu: {examination_time}
📍 Lokasi: {examination_location}

Jangan lupa:
- Berpuasa 8-12 jam sebelum pemeriksaan
- Membawa KTP/NRK
- Datang 15 menit sebelum jadwal

Terima kasih dan sampai jumpa besok!

Tim {app_name}',
            'variables' => [
                'participant_name' => 'Nama peserta',
                'examination_date' => 'Tanggal pemeriksaan',
                'examination_time' => 'Jam pemeriksaan',
                'examination_location' => 'Lokasi pemeriksaan',
                'app_name' => 'Nama aplikasi',
            ],
            'is_active' => true,
            'is_default' => false,
            'description' => 'Template pengingat untuk jadwal MCU yang akan datang',
        ]);
    }
}
