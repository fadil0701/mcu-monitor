<?php

return [
    'base_url' => rtrim((string) env('CKG_API_BASE_URL', 'http://127.0.0.1:9006'), '/'),
    'api_key' => env('CKG_API_KEY'),
    'api_key_header' => env('CKG_API_KEY_HEADER', 'X-Mcu-Api-Key'),
    'per_page' => (int) env('CKG_SYNC_PER_PAGE', 100),
    'eligible_work_unit' => 'ASN DKI Jakarta',
    'eligible_categories' => ['pns', 'cpns', 'pppk'],
];
