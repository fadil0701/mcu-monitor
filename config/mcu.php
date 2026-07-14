<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCU System Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk sistem monitoring Medical Check Up
    |
    */

    // Fallback interval MCU (tahun kalender). Sumber utama: Pengaturan Admin → mcu_interval_years.
    'interval_years' => env('MCU_INTERVAL_YEARS', 1),

    // Status pegawai yang diizinkan
    'allowed_employee_status' => [
        'CPNS',
        'PNS',
        'PPPK',
    ],

    // File upload settings
    'file' => [
        'max_size' => env('MCU_MAX_FILE_SIZE', 10240), // KB
        'allowed_types' => explode(',', env('MCU_ALLOWED_FILE_TYPES', 'pdf,doc,docx,jpg,jpeg,png')),
        'storage_path' => 'mcu-results',
    ],

    // Email settings
    'email' => [
        'from_name' => env('MAIL_FROM_NAME', 'Sistem MCU'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@mcu.local'),
    ],

    // WhatsApp settings
    'whatsapp' => [
        'api_token' => env('WHATSAPP_API_TOKEN'),
        'instance_id' => env('WHATSAPP_INSTANCE_ID'),
        'phone_number' => env('WHATSAPP_PHONE_NUMBER'),
        'api_base_url' => env('WHATSAPP_API_BASE_URL', 'https://chat.api.co.id'),
    ],

    // Pagination
    'pagination' => [
        'per_page' => 15,
    ],

    // Dashboard settings
    'dashboard' => [
        'stats_refresh_interval' => 300, // seconds
        'chart_months' => 6,
    ],

    // Notification settings
    'notifications' => [
        'email_enabled' => true,
        'whatsapp_enabled' => true,
        'reminder_days_before' => [7, 3, 1],
    ],

    'default_location' => env('MCU_DEFAULT_LOCATION', 'Klinik Utama Balaikota'),

    // Captcha hitung angka pada form login
    'login_captcha' => [
        'ttl_minutes' => (int) env('MCU_LOGIN_CAPTCHA_TTL_MINUTES', 10),
    ],

    // Jam pengajuan jadwal MCU oleh peserta (portal)
    'examination_hours' => [
        'start' => env('MCU_EXAMINATION_TIME_START', '07:30'),
        'end' => env('MCU_EXAMINATION_TIME_END', '10:00'),
    ],

    'daily_quota' => (int) env('MCU_DAILY_QUOTA', 100),

    'menu' => [
        'master_data_enabled' => filter_var(env('MCU_MENU_MASTER_DATA_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],
];
