<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearDashboardCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all dashboard widget caches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing dashboard caches...');

        $cacheKeys = [
            'dashboard_stats',
            'mcu_chart_data',
            'health_status_chart',
            'skpd_stats',
        ];

        foreach ($cacheKeys as $key) {
            if (cache()->forget($key)) {
                $this->line("✓ Cleared: {$key}");
            } else {
                $this->line("- Not found: {$key}");
            }
        }

        $this->newLine();
        $this->info('Dashboard cache cleared successfully!');
        
        return Command::SUCCESS;
    }
}
