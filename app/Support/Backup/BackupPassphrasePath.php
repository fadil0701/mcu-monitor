<?php

namespace App\Support\Backup;

class BackupPassphrasePath
{
    /**
     * @return list<string>
     */
    public static function candidates(): array
    {
        $paths = [];

        $fromEnv = env('BACKUP_GPG_PASSPHRASE_FILE');
        if (is_string($fromEnv) && $fromEnv !== '') {
            $paths[] = self::resolvePath($fromEnv);
        }

        $paths[] = base_path('.backup-passphrase');
        $paths[] = '/etc/mcuppkp/backup.pass';

        return array_values(array_unique(array_filter($paths)));
    }

    public static function resolve(): ?string
    {
        foreach (self::candidates() as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    public static function resolveOrFail(): string
    {
        $configured = env('BACKUP_GPG_PASSPHRASE_FILE');
        $resolved = self::resolve();

        if ($resolved !== null) {
            return $resolved;
        }

        $hint = is_string($configured) && $configured !== ''
            ? " Dikonfigurasi: {$configured}."
            : '';

        throw new \RuntimeException(
            'File passphrase backup tidak ditemukan.'.$hint
            .' Buat .backup-passphrase di folder proyek atau /etc/mcuppkp/backup.pass.'
        );
    }

    private static function resolvePath(string $file): string
    {
        if (str_starts_with($file, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $file)) {
            return $file;
        }

        return base_path($file);
    }
}
