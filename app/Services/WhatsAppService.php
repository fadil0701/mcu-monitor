<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Schedule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private function getProvider(): string
    {
        return Setting::getValue('whatsapp_provider', 'fonnte');
    }

    public function sendMcuInvitation(Schedule $schedule): bool
    {
        try {
            // Get WhatsApp settings
            $whatsappSettings = Setting::getGroup('whatsapp');
            
            $token = $whatsappSettings['whatsapp_token'] ?? '';
            $instanceId = $whatsappSettings['whatsapp_instance_id'] ?? '';
            $provider = $this->getProvider();
            
            if (empty($token)) {
                Log::error('WhatsApp token not configured');
                return false;
            }

            // Get WhatsApp template
            $template = Setting::getValue('whatsapp_invitation_template', 'Halo {nama_lengkap}, Anda diundang untuk mengikuti Medical Check Up pada tanggal {tanggal_pemeriksaan} pukul {jam_pemeriksaan} di {lokasi_pemeriksaan}.');

            // Prepare template data
            $templateData = $this->prepareTemplateData($schedule);
            
            // Render template (replace variables)
            $message = $this->renderTemplate($template, $templateData);

            // Clean phone number
            $phoneNumber = $this->cleanPhoneNumber($schedule->no_telp);

            // Send WhatsApp message based on provider
            $result = false;
            
            switch ($provider) {
                case 'fonnte':
                    $result = $this->sendViaFonnte($token, $phoneNumber, $message);
                    break;
                    
                case 'wablas':
                    $result = $this->sendViaWablas($token, $phoneNumber, $message);
                    break;
                    
                case 'meta':
                    if (empty($instanceId)) {
                        Log::error('WhatsApp instance ID not configured for Meta provider');
                        return false;
                    }
                    $result = $this->sendViaMeta($token, $instanceId, $phoneNumber, $message);
                    break;
                    
                default:
                    Log::error('Unknown WhatsApp provider: ' . $provider);
                    return false;
            }

            if ($result) {
                // Update schedule
                $schedule->update([
                    'whatsapp_sent' => true,
                    'whatsapp_sent_at' => now(),
                ]);

                Log::info("WhatsApp sent successfully to {$phoneNumber} via {$provider}");
                return true;
            } else {
                Log::error("Failed to send WhatsApp to {$phoneNumber} via {$provider}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp invitation: ' . $e->getMessage());
            return false;
        }
    }

    private function sendViaFonnte(string $token, string $phoneNumber, string $message): bool
    {
        try {
            Log::info("Sending WhatsApp via Fonnte to {$phoneNumber}");
            
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for local development
            ])->withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $phoneNumber,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $responseData = $response->json();
            Log::info('Fonnte API response: ' . json_encode($responseData));

            if ($response->successful()) {
                // Check if Fonnte returned success
                // Fonnte returns: {"status": true, "message": "Message sent", ...}
                if (isset($responseData['status']) && $responseData['status'] == true) {
                    return true;
                }
                
                // If status is not true, log the reason
                $reason = $responseData['reason'] ?? 'Unknown error';
                Log::error("Fonnte API returned false status: {$reason}");
                return false;
            } else {
                Log::error('Fonnte API HTTP error: ' . $response->status() . ' - ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Fonnte exception: ' . $e->getMessage());
            return false;
        }
    }

    private function sendViaWablas(string $token, string $phoneNumber, string $message): bool
    {
        try {
            Log::info("Sending WhatsApp via Wablas to {$phoneNumber}");
            
            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders([
                'Authorization' => $token,
            ])->post('https://console.wablas.com/api/send-message', [
                'phone' => $phoneNumber,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('Wablas API response: ' . $response->body());
                return true;
            } else {
                Log::error('Wablas API error: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Wablas exception: ' . $e->getMessage());
            return false;
        }
    }

    private function sendViaMeta(string $token, string $phoneNumberId, string $recipientPhone, string $message): bool
    {
        try {
            Log::info("Sending WhatsApp via Meta to {$recipientPhone}");
            
            $response = Http::withOptions([
                'verify' => false,
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $recipientPhone,
                'type' => 'text',
                'text' => [
                    'body' => $message,
                ],
            ]);

            if ($response->successful()) {
                Log::info('Meta API response: ' . $response->body());
                return true;
            } else {
                Log::error('Meta API error: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Meta exception: ' . $e->getMessage());
            return false;
        }
    }

    public function sendBulkMcuInvitations(array $scheduleIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($scheduleIds as $scheduleId) {
            $schedule = Schedule::find($scheduleId);
            if ($schedule) {
                if ($this->sendMcuInvitation($schedule)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to send WhatsApp to {$schedule->nama_lengkap} ({$schedule->no_telp})";
                }
            }
        }

        return $results;
    }

    private function cleanPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, replace with 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        // If doesn't start with 62, add it
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
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
}