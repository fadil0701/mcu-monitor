<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;

class ConfigureSmtp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smtp:configure
                            {--host= : SMTP Host}
                            {--port= : SMTP Port}
                            {--username= : SMTP Username}
                            {--password= : SMTP Password}
                            {--encryption= : SMTP Encryption (ssl/tls)}
                            {--from-address= : From Email Address}
                            {--from-name= : From Name}
                            {--interactive : Run in interactive mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure SMTP settings for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('interactive')) {
            return $this->configureInteractive();
        }

        // Get values from options or use defaults
        $host = $this->option('host') ?: $this->ask('SMTP Host');
        $port = $this->option('port') ?: $this->ask('SMTP Port', '465');
        $username = $this->option('username') ?: $this->ask('SMTP Username');
        $password = $this->option('password') ?: $this->secret('SMTP Password');
        $encryption = $this->option('encryption') ?: $this->choice('SMTP Encryption', ['ssl', 'tls'], 0);
        $fromAddress = $this->option('from-address') ?: $this->ask('From Email Address', $username);
        $fromName = $this->option('from-name') ?: $this->ask('From Name', 'Sistem Monitoring MCU');

        // Validate required fields
        if (empty($host) || empty($username) || empty($password)) {
            $this->error('Host, username, and password are required!');
            return Command::FAILURE;
        }

        // Update settings
        $this->updateSetting('smtp_host', $host);
        $this->updateSetting('smtp_port', $port);
        $this->updateSetting('smtp_username', $username);
        $this->updateSetting('smtp_password', $password);
        $this->updateSetting('smtp_encryption', $encryption);
        $this->updateSetting('smtp_from_address', $fromAddress);
        $this->updateSetting('smtp_from_name', $fromName);

        $this->info('SMTP settings configured successfully!');
        $this->displayConfiguration();

        return Command::SUCCESS;
    }

    /**
     * Configure SMTP in interactive mode
     */
    private function configureInteractive()
    {
        $this->info('=== SMTP Configuration ===');
        $this->newLine();

        // Display default cPanel values from screenshot
        $this->warn('From your cPanel settings:');
        $this->line('  SMTP Server: mail.puspelkesdki.id');
        $this->line('  SMTP Port: 465 (SSL)');
        $this->line('  Username: mcu@puspelkesdki.id');
        $this->newLine();

        $useDefaults = $this->confirm('Use these default settings?', true);

        if ($useDefaults) {
            $host = 'mail.puspelkesdki.id';
            $port = '465';
            $username = 'mcu@puspelkesdki.id';
            $encryption = 'ssl';
            $fromName = 'Sistem Monitoring MCU PPKP';
            
            $password = $this->secret('Enter SMTP Password');
            $fromAddress = $this->ask('From Email Address', $username);
        } else {
            $host = $this->ask('SMTP Host');
            $port = $this->ask('SMTP Port', '465');
            $username = $this->ask('SMTP Username');
            $password = $this->secret('SMTP Password');
            $encryption = $this->choice('SMTP Encryption', ['ssl', 'tls'], 0);
            $fromAddress = $this->ask('From Email Address', $username);
            $fromName = $this->ask('From Name', 'Sistem Monitoring MCU');
        }

        if (empty($password)) {
            $this->error('Password is required!');
            return Command::FAILURE;
        }

        $this->newLine();
        
        // Show preview
        $this->info('Configuration Preview:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Host', $host],
                ['Port', $port],
                ['Username', $username],
                ['Password', str_repeat('*', strlen($password))],
                ['Encryption', $encryption],
                ['From Address', $fromAddress],
                ['From Name', $fromName],
            ]
        );

        if ($this->confirm('Save these settings?', true)) {
            $this->updateSetting('smtp_host', $host);
            $this->updateSetting('smtp_port', $port);
            $this->updateSetting('smtp_username', $username);
            $this->updateSetting('smtp_password', $password);
            $this->updateSetting('smtp_encryption', $encryption);
            $this->updateSetting('smtp_from_address', $fromAddress);
            $this->updateSetting('smtp_from_name', $fromName);

            $this->info('✓ SMTP settings saved successfully!');
            $this->newLine();
            $this->displayConfiguration();
        }

        return Command::SUCCESS;
    }

    /**
     * Update a setting
     */
    private function updateSetting($key, $value, $type = 'string', $group = 'smtp')
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $this->getSettingDescription($key),
            ]
        );
    }

    /**
     * Get setting description
     */
    private function getSettingDescription($key): string
    {
        return match($key) {
            'smtp_host' => 'SMTP Server Host',
            'smtp_port' => 'SMTP Port Number',
            'smtp_username' => 'SMTP Username',
            'smtp_password' => 'SMTP Password',
            'smtp_encryption' => 'SMTP Encryption (ssl/tls)',
            'smtp_from_address' => 'Default From Email Address',
            'smtp_from_name' => 'Default From Name',
            default => '',
        };
    }

    /**
     * Display current configuration
     */
    private function displayConfiguration()
    {
        $this->newLine();
        $this->info('Current SMTP Configuration:');
        
        $settings = Setting::where('group', 'smtp')->get();
        
        $data = [];
        foreach ($settings as $setting) {
            $value = $setting->value;
            if ($setting->key === 'smtp_password' && !empty($value)) {
                $value = str_repeat('*', strlen($value));
            }
            $data[] = [$setting->key, $value];
        }
        
        if (!empty($data)) {
            $this->table(['Key', 'Value'], $data);
        } else {
            $this->warn('No SMTP settings found. Please run the seeder first.');
        }
    }
}




