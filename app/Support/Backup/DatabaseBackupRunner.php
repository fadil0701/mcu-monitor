<?php

namespace App\Support\Backup;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupRunner
{
    public function __construct(
        private readonly DatabaseBackupCatalog $catalog,
    ) {}

    public function mysqldumpAvailable(): bool
    {
        try {
            $this->findMysqldump();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function gpgAvailable(): bool
    {
        try {
            $this->gpgHome();
        } catch (\Throwable) {
            return false;
        }

        $process = new Process(['gpg', '--version']);
        $process->setEnv(['GNUPGHOME' => $this->gpgHome()]);
        $process->run();

        return $process->isSuccessful();
    }

    public function run(): string
    {
        $this->catalog->ensureDirectory();

        $database = (string) config('database.connections.mysql.database', 'monitoring_mcu');
        $timestamp = now()->format('Ymd-His');
        $baseName = "backup-{$database}-{$timestamp}";
        $sqlPath = $this->catalog->directory().DIRECTORY_SEPARATOR."{$baseName}.sql";

        $this->dumpDatabase($sqlPath, $database);

        if (! is_file($sqlPath) || filesize($sqlPath) === 0) {
            @unlink($sqlPath);
            throw new RuntimeException('Dump database gagal — file backup kosong.');
        }

        $finalPath = $sqlPath;

        if (config('backup.compress')) {
            $gzPath = $sqlPath.'.gz';
            $compressed = gzencode((string) file_get_contents($sqlPath), 9);
            if ($compressed === false) {
                @unlink($sqlPath);
                throw new RuntimeException('Kompresi gzip gagal.');
            }
            file_put_contents($gzPath, $compressed);
            @unlink($sqlPath);
            $finalPath = $gzPath;
        }

        if (config('backup.encrypt')) {
            $plainBeforeEncrypt = $finalPath;
            $finalPath = $this->encryptGpg($finalPath);
            if (! config('backup.keep_plain')) {
                @unlink($plainBeforeEncrypt);
            }
        }

        $this->catalog->pruneOlderThan((int) config('backup.retention_days'));

        return $finalPath;
    }

    private function dumpDatabase(string $outputPath, string $database): void
    {
        $mysqldump = $this->findMysqldump();

        [$host, $port, $user, $password] = $this->dumpCredentials();

        $command = [
            $mysqldump,
            '--host='.$host,
            '--port='.$port,
            '--user='.$user,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--default-character-set=utf8mb4',
            $database,
        ];

        $process = new Process($command, timeout: 600);
        $process->setEnv(array_filter([
            'MYSQL_PWD' => $password !== '' ? $password : null,
        ]));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: 'mysqldump gagal.'));
        }

        file_put_contents($outputPath, $process->getOutput());
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function dumpCredentials(): array
    {
        $host = (string) config('database.connections.mysql.host', '127.0.0.1');
        $port = (string) config('database.connections.mysql.port', '3306');
        $rootPassword = (string) env('MYSQL_ROOT_PASSWORD', '');

        if ($rootPassword !== '') {
            return [$host, $port, 'root', $rootPassword];
        }

        return [
            $host,
            $port,
            (string) config('database.connections.mysql.username', 'root'),
            (string) config('database.connections.mysql.password', ''),
        ];
    }

    private function findMysqldump(): string
    {
        $candidates = ['mysqldump'];

        foreach ($this->laragonMysqlBinDirs() as $binDir) {
            array_unshift($candidates, $binDir.DIRECTORY_SEPARATOR.'mysqldump.exe');
            array_unshift($candidates, $binDir.DIRECTORY_SEPARATOR.'mysqldump');
        }

        $binDir = env('BACKUP_MYSQL_BIN_DIR');
        if ($binDir) {
            array_unshift($candidates, rtrim($binDir, '/\\').DIRECTORY_SEPARATOR.'mysqldump.exe');
            array_unshift($candidates, rtrim($binDir, '/\\').DIRECTORY_SEPARATOR.'mysqldump');
        }

        foreach ($candidates as $candidate) {
            if ($candidate !== 'mysqldump' && ! is_file($candidate)) {
                continue;
            }

            $process = new Process([$candidate, '--version']);
            $process->run();
            if ($process->isSuccessful()) {
                return $candidate;
            }
        }

        throw new RuntimeException(
            'Binary mysqldump tidak ditemukan. Pasang mysql-client, set LARAGON_ROOT di .env, atau BACKUP_MYSQL_BIN_DIR (contoh Laragon: D:/laragon/bin/mysql/mysql-8.0.30-winx64/bin).'
        );
    }

    /**
     * @return list<string>
     */
    private function laragonMysqlBinDirs(): array
    {
        $roots = array_values(array_filter(array_unique([
            env('LARAGON_ROOT'),
            'D:/laragon',
            'D:\\laragon',
            'C:/laragon',
            'C:\\laragon',
        ])));

        $dirs = [];

        foreach ($roots as $root) {
            $mysqlRoot = rtrim((string) $root, '/\\').DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'mysql';
            if (! is_dir($mysqlRoot)) {
                continue;
            }

            $glob = glob($mysqlRoot.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'bin') ?: [];
            foreach ($glob as $binDir) {
                if (is_dir($binDir)) {
                    $dirs[] = $binDir;
                }
            }
        }

        return $dirs;
    }

    private function encryptGpg(string $inputPath): string
    {
        $passphraseFile = (string) config('backup.gpg_passphrase_file');

        if (! is_readable($passphraseFile)) {
            throw new RuntimeException("File passphrase tidak ditemukan atau tidak bisa dibaca: {$passphraseFile}");
        }

        $gpgCheck = new Process(['gpg', '--version']);
        $gpgCheck->setEnv(['GNUPGHOME' => $this->gpgHome()]);
        $gpgCheck->run();
        if (! $gpgCheck->isSuccessful()) {
            throw new RuntimeException('Perintah gpg tidak tersedia. Pasang gnupg / Gpg4win.');
        }

        $outputPath = $inputPath.'.gpg';

        $process = new Process([
            'gpg',
            '--batch',
            '--yes',
            '--pinentry-mode', 'loopback',
            '--passphrase-file', $passphraseFile,
            '--symmetric',
            '--cipher-algo', 'AES256',
            '--output', $outputPath,
            $inputPath,
        ], timeout: 300);
        $process->setEnv(['GNUPGHOME' => $this->gpgHome()]);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($outputPath) || filesize($outputPath) === 0) {
            @unlink($outputPath);
            throw new RuntimeException(trim($process->getErrorOutput() ?: 'Enkripsi GPG gagal.'));
        }

        return $outputPath;
    }

    private function gpgHome(): string
    {
        $home = (string) config('backup.gnupg_home');

        File::ensureDirectoryExists($home);

        if (! is_writable($home)) {
            throw new RuntimeException("Folder GNUPGHOME tidak bisa ditulis: {$home}");
        }

        return $home;
    }
}
