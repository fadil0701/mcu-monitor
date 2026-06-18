<?php

namespace App\Services;

use App\Models\McuResult;
use App\Models\Setting;
use App\Models\Schedule;
use App\Support\WhatsAppTemplateDefaults;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function setError(string $message): bool
    {
        $this->lastError = $message;
        Log::error($message);

        return false;
    }

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
                return $this->setError('WhatsApp token not configured');
            }

            // Get WhatsApp template
            $template = Setting::getValue(
                'whatsapp_invitation_template',
                WhatsAppTemplateDefaults::usesMetaFormat($provider)
                    ? WhatsAppTemplateDefaults::INVITATION_META
                    : WhatsAppTemplateDefaults::INVITATION_LEGACY,
            );

            // Prepare template data
            $templateData = $this->prepareTemplateData($schedule);

            $paramKeys = $this->resolveTemplateParamKeys(
                'whatsapp_apico_invitation_param_keys',
                WhatsAppTemplateDefaults::INVITATION_PARAM_KEYS,
            );

            // Render template (replace variables)
            $message = $this->renderTemplate($template, $templateData, $paramKeys);

            // Clean phone number
            $phoneNumber = $this->cleanPhoneNumber($schedule->no_telp);

            $result = $this->sendTextMessage(
                $token,
                $instanceId,
                $phoneNumber,
                $message,
                $templateData,
                $schedule->nama_lengkap,
            );

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

    private function sendTextMessage(
        string $token,
        string $instanceId,
        string $phoneNumber,
        string $message,
        array $templateData = [],
        ?string $recipientName = null,
        ?string $apicoTemplateSetting = null,
        ?string $apicoParamKeysSetting = null,
    ): bool {
        $provider = $this->getProvider();

        return match ($provider) {
            'fonnte' => $this->sendViaFonnte($token, $phoneNumber, $message),
            'wablas' => $this->sendViaWablas($token, $phoneNumber, $message),
            'meta' => $this->sendViaMeta($token, $instanceId, $phoneNumber, $message),
            'apico' => $this->sendViaApiCo(
                $token,
                $instanceId,
                $phoneNumber,
                $message,
                $templateData,
                $recipientName,
                $apicoTemplateSetting,
                $apicoParamKeysSetting,
            ),
            default => tap(false, function () use ($provider) {
                $this->setError('Unknown WhatsApp provider: '.$provider);
            }),
        };
    }

    private function apiCoBaseUrl(): string
    {
        return rtrim((string) config('mcu.whatsapp.api_base_url', 'https://chat.api.co.id'), '/');
    }

    /**
     * @return array<string, string>
     */
    private function apiCoHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
        ];
    }

    private function ensureApiCoCustomer(
        string $token,
        string $phoneNumberId,
        string $recipientPhone,
        ?string $name = null,
    ): bool {
        $response = Http::withHeaders($this->apiCoHeaders($token))
            ->post("{$this->apiCoBaseUrl()}/api/v1/public/customers", [
                'phone_number' => $recipientPhone,
                'name' => $name ?: $recipientPhone,
                'consent_status' => true,
                'consent_source' => 'mcu-monitor',
                'whatsapp_phone_number_id' => $phoneNumberId,
            ]);

        if ($response->successful()) {
            return true;
        }

        $errorCode = $response->json('error.code');
        if ($errorCode === 'CustomerExists') {
            return true;
        }

        return $this->setError(
            'Api.co.id: gagal mendaftarkan customer — '.($response->json('error.message') ?? $response->body()),
        );
    }

    /**
     * @param  array<string, string>  $templateData
     */
    private function sendViaApiCo(
        string $token,
        string $phoneNumberId,
        string $recipientPhone,
        string $message,
        array $templateData = [],
        ?string $recipientName = null,
        ?string $templateNameSetting = null,
        ?string $paramKeysSetting = null,
    ): bool {
        $this->lastError = null;

        if ($phoneNumberId === '') {
            return $this->setError('Phone Number ID belum diisi di Pengaturan WhatsApp.');
        }

        if (! $this->ensureApiCoCustomer($token, $phoneNumberId, $recipientPhone, $recipientName)) {
            return false;
        }

        $templateName = trim((string) ($templateNameSetting ?? Setting::getValue('whatsapp_apico_template_name', '')));
        if ($templateName !== '') {
            $language = (string) Setting::getValue('whatsapp_apico_template_language', 'id');
            $paramKeys = $paramKeysSetting ?? (string) Setting::getValue(
                'whatsapp_apico_invitation_param_keys',
                'nama_lengkap,tanggal_pemeriksaan,jam_pemeriksaan,lokasi_pemeriksaan,queue_number',
            );
            $bodyParams = $this->buildApiCoTemplateParams($templateData, $paramKeys);

            return $this->sendApiCoMessage($token, $phoneNumberId, $recipientPhone, [
                'message_type' => 'template',
                'template' => $this->buildApiCoTemplatePayload($templateName, $language, $bodyParams),
            ]);
        }

        return $this->sendApiCoMessage($token, $phoneNumberId, $recipientPhone, [
            'message_type' => 'text',
            'content' => $message,
        ]);
    }

    /**
     * @param  array<string, string>  $templateData
     * @return list<string>
     */
    private function buildApiCoTemplateParams(array $templateData, string $paramKeys): array
    {
        $keys = array_filter(array_map('trim', explode(',', $paramKeys)));

        return array_map(
            fn (string $key) => (string) ($templateData[$key] ?? '-'),
            $keys,
        );
    }

    /**
     * @param  list<string>  $bodyParams
     * @return array<string, mixed>
     */
    private function buildApiCoTemplatePayload(string $name, string $language, array $bodyParams): array
    {
        $template = [
            'name' => $name,
            'language' => ['code' => $language],
        ];

        if ($bodyParams !== []) {
            $template['components'] = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $text) => ['type' => 'text', 'text' => $text],
                    $bodyParams,
                ),
            ]];
        }

        return $template;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendApiCoMessage(
        string $token,
        string $phoneNumberId,
        string $recipientPhone,
        array $payload,
    ): bool {
        try {
            Log::info('Sending WhatsApp via Api.co.id Chat Gateway', [
                'to' => $recipientPhone,
                'message_type' => $payload['message_type'] ?? 'unknown',
            ]);

            $response = Http::withHeaders($this->apiCoHeaders($token))
                ->post("{$this->apiCoBaseUrl()}/api/v1/public/messages/send", array_merge([
                    'phone_number' => $recipientPhone,
                    'channel' => 'whatsapp',
                    'whatsapp_phone_number_id' => $phoneNumberId,
                ], $payload));

            Log::info('Api.co.id Chat Gateway response: '.$response->body());

            if ($response->successful() && ($response->json('success') ?? false)) {
                return true;
            }

            $message = (string) ($response->json('error.message') ?? $response->body());
            $code = (string) ($response->json('error.code') ?? '');

            if ($code === 'WindowClosed' || str_contains($message, 'Only template messages allowed')) {
                return $this->setError(
                    'Pesan teks ditolak WhatsApp: pelanggan belum pernah chat dalam 24 jam. Buat & sync template Meta di Api.co.id, lalu isi "Nama Template WA (Undangan)" di Pengaturan.',
                );
            }

            if ($code === 'NotFound' && str_contains($message, 'Customer not found')) {
                return $this->setError(
                    'Customer belum terdaftar di Api.co.id. Isi "Nama Template WA" di Pengaturan untuk kirim via template (auto-create customer).',
                );
            }

            return $this->setError('Api.co.id: '.$message);
        } catch (\Exception $e) {
            return $this->setError('Api.co.id exception: '.$e->getMessage());
        }
    }
    private function sendViaMeta(string $token, string $phoneNumberId, string $recipientPhone, string $message): bool
    {
        if ($phoneNumberId === '') {
            Log::error('WhatsApp instance ID not configured for Meta provider');

            return false;
        }

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
     * @param  array<string, string>  $data
     */
    private function renderTemplate(string $template, array $data, ?string $orderedParamKeys = null): string
    {
        $rendered = $template;

        if ($orderedParamKeys !== null && $orderedParamKeys !== '') {
            $keys = array_filter(array_map('trim', explode(',', $orderedParamKeys)));
            foreach ($keys as $index => $key) {
                $rendered = str_replace('{{'.($index + 1).'}}', (string) ($data[$key] ?? '-'), $rendered);
            }

            return $rendered;
        }

        foreach ($data as $key => $value) {
            $rendered = str_replace('{'.$key.'}', (string) $value, $rendered);
        }

        return $rendered;
    }

    private function resolveTemplateParamKeys(string $settingKey, string $defaultKeys): ?string
    {
        if (! WhatsAppTemplateDefaults::usesMetaFormat($this->getProvider())) {
            return null;
        }

        return (string) Setting::getValue($settingKey, $defaultKeys);
    }

    public function sendMcuResult(McuResult $result): bool
    {
        $result->loadMissing('participant');
        $participant = $result->participant;

        if ($participant === null || blank($participant->no_telp)) {
            Log::error('MCU result WhatsApp skipped: participant phone missing', [
                'mcu_result_id' => $result->id,
            ]);

            return false;
        }

        try {
            $whatsappSettings = Setting::getGroup('whatsapp');

            $token = $whatsappSettings['whatsapp_token'] ?? '';
            $instanceId = $whatsappSettings['whatsapp_instance_id'] ?? '';

            if ($token === '') {
                Log::error('WhatsApp token not configured');

                return false;
            }

            $defaultTemplate = WhatsAppTemplateDefaults::usesMetaFormat()
                ? WhatsAppTemplateDefaults::RESULT_META
                : WhatsAppTemplateDefaults::RESULT_LEGACY;
            $template = Setting::getValue('whatsapp_result_template', $defaultTemplate) ?: $defaultTemplate;

            $templateData = $this->prepareMcuResultTemplateData($result);

            $paramKeys = $this->resolveTemplateParamKeys(
                'whatsapp_apico_result_param_keys',
                WhatsAppTemplateDefaults::RESULT_PARAM_KEYS,
            );

            $message = $this->renderTemplate($template, $templateData, $paramKeys);

            $phoneNumber = $this->cleanPhoneNumber($participant->no_telp);

            $sent = $this->sendTextMessage(
                $token,
                $instanceId,
                $phoneNumber,
                $message,
                $templateData,
                $participant->nama_lengkap,
                (string) Setting::getValue('whatsapp_apico_result_template_name', ''),
                (string) Setting::getValue(
                    'whatsapp_apico_result_param_keys',
                    'participant_name,tanggal_pemeriksaan,hasil_url',
                ),
            );

            if ($sent) {
                Log::info("MCU result WhatsApp sent successfully to {$participant->no_telp}", [
                    'mcu_result_id' => $result->id,
                ]);
            }

            return $sent;
        } catch (\Throwable $e) {
            Log::error('Failed to send MCU result WhatsApp: '.$e->getMessage(), [
                'mcu_result_id' => $result->id,
                'exception' => $e::class,
            ]);

            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function prepareMcuResultTemplateData(McuResult $result): array
    {
        $participant = $result->participant;

        return [
            'participant_name' => (string) ($participant?->nama_lengkap ?? '-'),
            'participant_email' => (string) ($participant?->email ?? '-'),
            'participant_phone' => (string) ($participant?->no_telp ?? '-'),
            'tanggal_pemeriksaan' => $result->tanggal_pemeriksaan?->format('d/m/Y') ?? '-',
            'rekomendasi' => (string) ($result->rekomendasi ?? '-'),
            'hasil_url' => route('client.results'),
            'app_name' => (string) config('app.name', 'MCU Monitor'),
            'nama_lengkap' => (string) ($participant?->nama_lengkap ?? '-'),
        ];
    }
}