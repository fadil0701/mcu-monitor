<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Backup\DatabaseBackupCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config([
            'backup.directory' => storage_path('framework/testing/backups/database'),
        ]);

        File::ensureDirectoryExists(config('backup.directory'));
        File::cleanDirectory(config('backup.directory'));
    }

    public function test_super_admin_can_view_backup_page(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('admin.backup.index'))
            ->assertOk()
            ->assertSee('Backup Database')
            ->assertSee('Restore database');
    }

    public function test_admin_cannot_view_backup_page(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('admin.backup.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_download_allowed_backup_file(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $user->assignRole('super_admin');
        $filename = 'backup-monitoring_mcu-20260101-120000.sql.gz.gpg';
        $path = config('backup.directory').'/'.$filename;
        File::put($path, 'encrypted-test-content');

        $this->actingAs($user)
            ->get(route('admin.backup.download', ['filename' => $filename]))
            ->assertOk()
            ->assertDownload($filename);
    }

    public function test_download_rejects_invalid_filename(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('admin.backup.download', ['filename' => '../../../etc/passwd']))
            ->assertNotFound();
    }

    public function test_catalog_lists_backup_files(): void
    {
        $filename = 'backup-test_db-20260101-120000.sql.gz';
        File::put(config('backup.directory').'/'.$filename, str_repeat('x', 1024));

        $catalog = app(DatabaseBackupCatalog::class);
        $files = $catalog->list();

        $this->assertCount(1, $files);
        $this->assertSame($filename, $files->first()->filename);
        $this->assertFalse($files->first()->encrypted);
    }
}
