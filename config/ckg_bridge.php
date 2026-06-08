<?php

return [
    'base_url' => rtrim((string) env('CKG_API_BASE_URL', 'http://127.0.0.1:9006'), '/'),
    'api_key' => env('CKG_API_KEY'),
    'api_key_header' => env('CKG_API_KEY_HEADER', 'X-Mcu-Api-Key'),
    'per_page' => (int) env('CKG_SYNC_PER_PAGE', 100),
    // VM dengan HTTP_PROXY korporat: jangan proxy request ke CKG internal
    'disable_proxy' => filter_var(env('CKG_BRIDGE_DISABLE_PROXY', true), FILTER_VALIDATE_BOOL),
    // Remap 127.0.0.1 dari form → host ini (production VM)
    'internal_host' => env('CKG_BRIDGE_INTERNAL_HOST', '10.15.101.117'),
    'internal_port' => (int) env('CKG_BRIDGE_INTERNAL_PORT', 9006),
    'eligible_work_unit' => 'ASN DKI Jakarta',
    'eligible_categories' => ['pns', 'cpns', 'pppk'],
];
