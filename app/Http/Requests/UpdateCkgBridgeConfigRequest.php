<?php

namespace App\Http\Requests;

use App\Support\CkgBridge\CkgBridgeUrlNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCkgBridgeConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'base_url' => ['required', 'string', 'max:500', 'regex:/^https?:\/\/.+/i'],
            'api_key' => 'nullable|string|max:500',
            'api_key_header' => 'nullable|string|max:64|in:X-Mcu-Api-Key',
            'per_page' => 'integer|min:1|max:500',
            'timeout_seconds' => 'integer|min:10|max:120',
            'is_active' => 'boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active') && ! $this->boolean('is_active')) {
            $this->merge(['is_active' => false]);
        }

        if ($this->filled('base_url')) {
            $this->merge([
                'base_url' => CkgBridgeUrlNormalizer::normalize((string) $this->input('base_url')),
            ]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $url = (string) $this->input('base_url', '');
            if ($url === '') {
                return;
            }

            if (str_contains(strtolower($url), '/api/bridge')) {
                $validator->errors()->add(
                    'base_url',
                    'Isi hanya base URL (mis. http://HOST:9006), bukan path endpoint API.'
                );
            }

            $host = parse_url($url, PHP_URL_HOST);
            $port = parse_url($url, PHP_URL_PORT);
            $internalHost = (string) config('ckg_bridge.internal_host', '127.0.0.1');
            $internalHosts = array_filter(array_unique([
                '127.0.0.1',
                'localhost',
                'host.docker.internal',
                $internalHost,
            ]));
            if (in_array($host, $internalHosts, true) && $port === null) {
                $validator->errors()->add(
                    'base_url',
                    'Untuk akses LAN/internal, sertakan port Docker CKG (biasanya :9006).'
                );
            }
        });
    }
}
