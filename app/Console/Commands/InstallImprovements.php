<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallImprovements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcu:install-improvements {--force : Force installation even if already installed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install system improvements including security enhancements, queue system, and monitoring';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Installing MCU System Improvements...');
        $this->newLine();

        // Check if already installed
        if (!$this->option('force') && $this->isAlreadyInstalled()) {
            $this->warn('Improvements already installed. Use --force to reinstall.');
            return;
        }

        try {
            // 1. Run migrations
            $this->info('📊 Running database migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Database migrations completed');

            // 2. Clear caches
            $this->info('🧹 Clearing caches...');
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            $this->info('✅ Caches cleared');

            // 3. Generate application key if not exists
            if (empty(config('app.key'))) {
                $this->info('🔑 Generating application key...');
                Artisan::call('key:generate', ['--force' => true]);
                $this->info('✅ Application key generated');
            }

            // 4. Create sample settings for encryption
            $this->info('⚙️ Setting up encrypted settings...');
            $this->setupEncryptedSettings();

            // 5. Seed audit logs
            $this->info('📝 Seeding audit logs...');
            Artisan::call('db:seed', ['--class' => 'AuditLogSeeder', '--force' => true]);
            $this->info('✅ Audit logs seeded');

            // 6. Optimize application
            $this->info('⚡ Optimizing application...');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            $this->info('✅ Application optimized');

            $this->newLine();
            $this->info('🎉 System improvements installed successfully!');
            $this->newLine();
            
            $this->displayNextSteps();

        } catch (\Exception $e) {
            $this->error('❌ Installation failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function isAlreadyInstalled(): bool
    {
        try {
            return DB::table('audit_logs')->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function setupEncryptedSettings(): void
    {
        $settings = [
            'smtp_password' => [
                'value' => 'your-smtp-password',
                'type' => 'string',
                'group' => 'smtp',
                'description' => 'SMTP password for email sending'
            ],
            'whatsapp_token' => [
                'value' => 'your-whatsapp-token',
                'type' => 'string',
                'group' => 'whatsapp',
                'description' => 'WhatsApp API token'
            ],
            'whatsapp_instance_id' => [
                'value' => 'your-instance-id',
                'type' => 'string',
                'group' => 'whatsapp',
                'description' => 'WhatsApp instance ID for Meta provider'
            ],
        ];

        foreach ($settings as $key => $config) {
            \App\Models\Setting::setValue(
                $key,
                $config['value'],
                $config['type'],
                $config['group'],
                $config['description']
            );
        }
    }

    private function displayNextSteps(): void
    {
        $this->info('📋 Next Steps:');
        $this->line('1. Update your .env file with proper SMTP and WhatsApp credentials');
        $this->line('2. Configure queue driver in .env (recommended: database or redis)');
        $this->line('3. Run queue worker: php artisan queue:work');
        $this->line('4. Test the system health monitoring in the dashboard');
        $this->line('5. Review audit logs in the admin panel');
        $this->newLine();
        $this->warn('⚠️  Important: Update encrypted settings with your actual credentials!');
    }
}

