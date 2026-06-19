<?php

namespace App\Support;

use App\Models\Setting;

class WhatsAppTemplateDefaults
{
    public const INVITATION_PARAM_KEYS = 'nama_lengkap,tanggal_pemeriksaan,hari_pemeriksaan,jam_pemeriksaan,queue_number,lokasi_pemeriksaan';

    public const RESULT_PARAM_KEYS = 'participant_name,tanggal_pemeriksaan,hasil_url';

    public const INVITATION_META = <<<'TEXT'
Kepada Yth. Bapak/Ibu {{1}},

Sehubungan dengan kegiatan Medical Check Up (MCU), berikut rincian kehadiran Anda:
Tanggal: {{2}}
Hari: {{3}}
Jam: {{4}}
Nomor Urut: {{5}}
Tempat: {{6}}

Catatan: hadir 15 menit lebih awal, bawa identitas, puasa 8 jam sebelum pemeriksaan.

Terima kasih.
TEXT;

    public const RESULT_META = <<<'TEXT'
Halo {{1}},

Hasil MCU Anda untuk pemeriksaan tanggal {{2}} telah tersedia.

Silakan login ke {{3}} untuk melihat dan mendownload hasil lengkap.

Terima kasih.
TEXT;

    public const INVITATION_LEGACY = <<<'TEXT'
Halo {nama_lengkap},

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

Terima kasih.
TEXT;

    public const RESULT_LEGACY = <<<'TEXT'
Halo {participant_name},

Hasil MCU Anda untuk pemeriksaan tanggal {tanggal_pemeriksaan} telah tersedia.

Silakan login ke {hasil_url} untuk melihat dan mendownload hasil lengkap.

Terima kasih.
TEXT;

    /**
     * @return array<int, string>
     */
    public static function invitationVariableLegend(): array
    {
        return [
            1 => 'nama_lengkap',
            2 => 'tanggal_pemeriksaan',
            3 => 'hari_pemeriksaan',
            4 => 'jam_pemeriksaan',
            5 => 'queue_number',
            6 => 'lokasi_pemeriksaan',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function resultVariableLegend(): array
    {
        return [
            1 => 'participant_name',
            2 => 'tanggal_pemeriksaan',
            3 => 'hasil_url',
        ];
    }

    public static function usesMetaFormat(?string $provider = null): bool
    {
        $provider ??= (string) Setting::getValue('whatsapp_provider', 'fonnte');

        return in_array($provider, ['apico', 'meta'], true);
    }
}
