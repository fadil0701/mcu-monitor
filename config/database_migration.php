<?php

/**
 * Urutan salin data MySQL → PostgreSQL (MCU Monitor).
 */
return [
    'source_connection' => env('DB_MIGRATION_SOURCE', 'mysql'),
    'target_connection' => env('DB_MIGRATION_TARGET', 'pgsql'),

    'chunk_size' => (int) env('DB_MIGRATION_CHUNK', 500),

    'skip_tables' => [
        'migrations',
    ],

    'tables' => [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'participants',
        'schedules',
        'mcu_results',
        'diagnoses',
        'settings',
        'specialist_doctors',
        'pdf_templates',
        'email_templates',
        'audit_logs',
        'instansi_pemprov_dkis',
        'ckg_bridge_configs',
        'ckg_bridge_sync_logs',
    ],

    'serial_tables' => [
        'users',
        'jobs',
        'failed_jobs',
        'permissions',
        'roles',
        'participants',
        'schedules',
        'mcu_results',
        'diagnoses',
        'settings',
        'specialist_doctors',
        'pdf_templates',
        'email_templates',
        'audit_logs',
        'instansi_pemprov_dkis',
        'ckg_bridge_configs',
        'ckg_bridge_sync_logs',
    ],
];
