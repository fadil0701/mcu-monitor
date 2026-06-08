<?php

namespace App\Support\CkgBridge;

use App\Http\Requests\UpdateCkgBridgeConfigRequest;
use App\Models\CkgBridgeConfig;

final class CkgBridgeConfigPersister
{
    public function persist(UpdateCkgBridgeConfigRequest $request): CkgBridgeConfig
    {
        $data = $request->validated();
        $data['base_url'] = CkgBridgeUrlNormalizer::normalize((string) $data['base_url']);

        if (($data['api_key'] ?? '') === '') {
            unset($data['api_key']);
        }

        $data['api_key_header'] = filled($data['api_key_header'] ?? null)
            ? $data['api_key_header']
            : 'X-Mcu-Api-Key';

        return CkgBridgeConfig::query()->updateOrCreate(
            ['name' => 'CKG Bridge'],
            $data
        )->fresh();
    }
}
