<?php

namespace App\Support\Backup;

use Carbon\CarbonInterface;

final class DatabaseBackupFile
{
    public function __construct(
        public readonly string $filename,
        public readonly string $path,
        public readonly int $sizeBytes,
        public readonly CarbonInterface $modifiedAt,
        public readonly bool $encrypted,
    ) {}

    public function humanSize(): string
    {
        $bytes = $this->sizeBytes;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MiB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0).' KiB';
        }

        return $bytes.' B';
    }
}
