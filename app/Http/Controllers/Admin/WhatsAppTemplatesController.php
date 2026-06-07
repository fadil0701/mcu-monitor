<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class WhatsAppTemplatesController extends Controller
{
    public function index()
    {
        $invitation_template = Setting::getValue('whatsapp_invitation_template', '');
        $resultDefault = "Halo {participant_name},\n\nHasil MCU Anda untuk pemeriksaan tanggal {tanggal_pemeriksaan} telah tersedia.\n\n📋 Status Kesehatan: {status_kesehatan}\n📝 Diagnosis: {diagnosis}\n\nSilakan login ke {hasil_url} untuk melihat dan mendownload hasil lengkap.\n\nTerima kasih.";
        $result_template = Setting::getValue('whatsapp_result_template', $resultDefault) ?: $resultDefault;
        return view('admin.whatsapp-templates.index', compact('invitation_template', 'result_template'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'invitation_template' => 'required|string',
        ]);
        try {
            Setting::setValue(
                'whatsapp_invitation_template',
                $request->invitation_template,
                'text',
                'whatsapp_template',
                'Template WhatsApp Undangan'
            );
            return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Template undangan berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function updateResult(Request $request)
    {
        $request->validate([
            'result_template' => 'required|string',
        ]);
        try {
            Setting::setValue(
                'whatsapp_result_template',
                $request->result_template,
                'text',
                'whatsapp_template',
                'Template WhatsApp Hasil MCU'
            );
            return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Template hasil MCU berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function reset()
    {
        $default = "Halo {nama_lengkap},\n\nAnda diundang untuk mengikuti Medical Check Up pada:\n📅 Tanggal: {tanggal_pemeriksaan}\n🕐 Jam: {jam_pemeriksaan}\n📍 Lokasi: {lokasi_pemeriksaan}\n🎫 Nomor Antrian: {queue_number}\n\n*Catatan Penting:*\n• Hadir 15 menit lebih awal\n• Bawa KTP/kartu identitas\n• Puasa 8 jam sebelumnya\n\nMohon hadir tepat waktu.\n\nTerima kasih.";
        Setting::setValue('whatsapp_invitation_template', $default, 'text', 'whatsapp_template', 'Template WhatsApp Undangan');
        return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Template undangan direset ke default.');
    }

    public function resetResult()
    {
        $default = "Halo {participant_name},\n\nHasil MCU Anda untuk pemeriksaan tanggal {tanggal_pemeriksaan} telah tersedia.\n\n📋 Status Kesehatan: {status_kesehatan}\n📝 Diagnosis: {diagnosis}\n\nSilakan login ke {hasil_url} untuk melihat dan mendownload hasil lengkap.\n\nTerima kasih.";
        Setting::setValue('whatsapp_result_template', $default, 'text', 'whatsapp_template', 'Template WhatsApp Hasil MCU');
        return redirect()->route('admin.whatsapp-templates.index')->with('success', 'Template hasil MCU direset ke default.');
    }
}
