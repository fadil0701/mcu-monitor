<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Backup\DatabaseBackupCatalog;
use App\Support\Backup\DatabaseBackupRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    public function index(DatabaseBackupCatalog $catalog, DatabaseBackupRunner $runner): View
    {
        return view('admin.backup.index', [
            'backups' => $catalog->list(),
            'backupDirectory' => $catalog->directory(),
            'encryptEnabled' => (bool) config('backup.encrypt'),
            'retentionDays' => (int) config('backup.retention_days'),
            'logTail' => $catalog->tailLog(),
            'mysqldumpAvailable' => $runner->mysqldumpAvailable(),
            'gpgAvailable' => $runner->gpgAvailable(),
        ]);
    }

    public function store(Request $request, DatabaseBackupRunner $runner): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'in:ya'],
        ]);

        AuditLog::log(
            'backup.manual_started',
            'DatabaseBackup',
            null,
            null,
            ['user_email' => $request->user()?->email],
            'Backup database manual dimulai',
        );

        try {
            $finalPath = $runner->run();
        } catch (RuntimeException $e) {
            AuditLog::log(
                'backup.manual_failed',
                'DatabaseBackup',
                null,
                null,
                ['message' => $e->getMessage()],
                'Backup database manual gagal',
            );

            return redirect()
                ->route('admin.backup.index')
                ->withErrors(['backup' => $e->getMessage()]);
        }

        $filename = basename($finalPath);

        AuditLog::log(
            'backup.manual_completed',
            'DatabaseBackup',
            null,
            null,
            ['file' => $filename, 'size' => (int) filesize($finalPath)],
            'Backup database manual selesai',
        );

        $this->appendLog('UI backup by '.$request->user()?->email.': '.$finalPath);

        return redirect()
            ->route('admin.backup.index')
            ->with('success', "Backup berhasil: {$filename}");
    }

    public function download(string $filename, DatabaseBackupCatalog $catalog): StreamedResponse
    {
        $path = $catalog->resolvePath($filename);

        if ($path === null) {
            abort(404);
        }

        AuditLog::log(
            'backup.downloaded',
            'DatabaseBackup',
            null,
            null,
            ['file' => basename($filename), 'size' => (int) filesize($path)],
            'Unduh arsip backup database',
        );

        return response()->streamDownload(function () use ($path): void {
            $stream = fopen($path, 'rb');
            if ($stream === false) {
                return;
            }
            fpassthru($stream);
            fclose($stream);
        }, basename($filename), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    private function appendLog(string $line): void
    {
        $logFile = (string) config('backup.log_file');
        File::ensureDirectoryExists(dirname($logFile));
        $entry = '['.now()->format('Y-m-d H:i:s').'] '.$line.PHP_EOL;
        @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
