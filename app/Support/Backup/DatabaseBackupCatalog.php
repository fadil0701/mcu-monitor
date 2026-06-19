<?php

namespace App\Support\Backup;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class DatabaseBackupCatalog
{
    public function directory(): string
    {
        return (string) config('backup.directory');
    }

    /**
     * @return Collection<int, DatabaseBackupFile>
     */
    public function list(): Collection
    {
        $directory = $this->directory();

        if (! is_dir($directory)) {
            return collect();
        }

        $files = (new Finder)
            ->files()
            ->in($directory)
            ->name('/^backup-.*\.(sql|gz|gpg)$/');

        return collect(iterator_to_array($files, false))
            ->map(function (\SplFileInfo $file): DatabaseBackupFile {
                $name = $file->getFilename();

                return new DatabaseBackupFile(
                    filename: $name,
                    path: $file->getPathname(),
                    sizeBytes: (int) $file->getSize(),
                    modifiedAt: Carbon::createFromTimestamp($file->getMTime()),
                    encrypted: str_ends_with($name, '.gpg'),
                );
            })
            ->sortByDesc(fn (DatabaseBackupFile $file) => $file->modifiedAt->getTimestamp())
            ->values();
    }

    public function resolvePath(string $filename): ?string
    {
        $filename = basename($filename);

        if (! $this->isAllowedFilename($filename)) {
            return null;
        }

        $path = $this->directory().DIRECTORY_SEPARATOR.$filename;

        if (! is_file($path)) {
            return null;
        }

        return $path;
    }

    public function isAllowedFilename(string $filename): bool
    {
        return (bool) preg_match((string) config('backup.filename_pattern'), basename($filename));
    }

    public function ensureDirectory(): void
    {
        File::ensureDirectoryExists($this->directory());
    }

    public function pruneOlderThan(int $days): int
    {
        if ($days <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($days)->getTimestamp();
        $removed = 0;

        foreach ($this->list() as $backup) {
            if ($backup->modifiedAt->getTimestamp() < $cutoff) {
                if (@unlink($backup->path)) {
                    $removed++;
                }
            }
        }

        return $removed;
    }

    /**
     * @return list<string>
     */
    public function tailLog(int $lines = 15): array
    {
        $logFile = (string) config('backup.log_file');

        if (! is_readable($logFile)) {
            return [];
        }

        $content = @file($logFile, FILE_IGNORE_NEW_LINES);

        if ($content === false) {
            return [];
        }

        return array_slice($content, -$lines);
    }
}
