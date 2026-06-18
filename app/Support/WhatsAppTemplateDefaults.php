<?php

namespace App\Support;

class WhatsAppTemplateDefaults
{
    public const INVITATION_PARAM_KEYS = 'nama_lengkap,tanggal_pemeriksaan,jam_pemeriksaan,lokasi_pemeriksaan,queue_number';

    public const RESULT_PARAM_KEYS = 'participant_name,tanggal_pemeriksaan,hasil_url';

    public const INVITATION_META = <<<'TEXT'
Halo {{1}},

Anda diundang untuk mengikuti Medical Check Up pada:
📅 Tanggal: {{2}}
🕐 Jam: {{3}}
📍 Lokasi: {{4}}
🎫 Nomor Antrian: {{5}}

*Catatan Penting:*
• Hadir 15 menit lebih awal
• Bawa KTP/kartu identitas
• Puasa 8 jam sebelumnya

Mohon hadir tepat waktu.

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
            3 => 'jam_pemeriksaan',
            4 => 'lokasi_pemeriksaan',
            5 => 'queue_number',
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
        $provider ??= (string) \App\Models\Setting::getValue('whatsapp_provider', 'fonnte');

        return in_array($provider, ['apico', 'meta'], true);
    }
}
