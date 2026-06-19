<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database backup (UI + artisan mcu:backup-database)
    |--------------------------------------------------------------------------
    |
    | Shell scripts di deploy/ dipakai untuk backup/restore manual via SSH.
    | Cron produksi: docker compose exec -T app php artisan mcu:backup-database
    |
    */

    'directory' => env('BACKUP_DIR')
        ? (str_starts_with((string) env('BACKUP_DIR'), '/')
            ? (string) env('BACKUP_DIR')
            : base_path((string) env('BACKUP_DIR')))
        : storage_path('backups/database'),

    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),

    'compress' => filter_var(env('BACKUP_COMPRESS', true), FILTER_VALIDATE_BOOL),

    'encrypt' => filter_var(env('BACKUP_ENCRYPT', false), FILTER_VALIDATE_BOOL),

    'gpg_passphrase_file' => \App\Support\Backup\BackupPassphrasePath::resolve()
        ?? (function (): string {
            $file = env('BACKUP_GPG_PASSPHRASE_FILE', '.backup-passphrase');

            if ($file === null || $file === '') {
                return base_path('.backup-passphrase');
            }

            $file = (string) $file;

            if (str_starts_with($file, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $file)) {
                return $file;
            }

            return base_path($file);
        })(),

    'keep_plain' => filter_var(env('BACKUP_KEEP_PLAIN', false), FILTER_VALIDATE_BOOL),

    'log_file' => storage_path('logs/backup.log'),

    'gnupg_home' => env('BACKUP_GNUPGHOME', storage_path('app/gnupg')),

    'filename_pattern' => '/^backup-[a-zA-Z0-9_]+-\d{8}-\d{6}\.(sql|sql\.gz|sql\.gz\.gpg|gpg)$/',

];
