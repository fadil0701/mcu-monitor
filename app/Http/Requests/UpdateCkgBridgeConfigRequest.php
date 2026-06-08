<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'base_url' => 'required|url|max:500',
            'api_key' => 'nullable|string|max:500',
            'api_key_header' => 'nullable|string|max:64',
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
    }
}
