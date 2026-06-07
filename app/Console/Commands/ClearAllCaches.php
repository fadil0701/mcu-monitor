<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use App\Services\QueryOptimizationService;

class ClearAllCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcu:clear-all-caches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all caches for maximum performance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Clearing All Caches for Maximum Performance...');
        $this->newLine();

        try {
            // 1. Clear Laravel caches
            $this->info('1. Clearing Laravel caches...');
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('event:clear');
            $this->info('   ✅ Laravel caches cleared');

            // 2. Clear query optimization caches
            $this->info('2. Clearing query optimization caches...');
            QueryOptimizationService::clearQueryCaches();
            $this->info('   ✅ Query caches cleared');

            // 3. Clear custom widget caches
            $this->info('3. Clearing widget caches...');
            $widgetCaches = [
                'dashboard_stats',
                'skpd_stats',
                'mcu_chart_data',
                'health_status_chart',
                'health_status_distribution',
                'today_queue',
                'system_health',
                'database_metrics'
            ];
            
            foreach ($widgetCaches as $cache) {
                Cache::forget($cache);
            }
            
            // Clear SKPD stats with different limits
            for ($i = 1; $i <= 20; $i++) {
                Cache::forget("skpd_stats_{$i}");
            }
            
            // Clear chart data with different months
            for ($i = 1; $i <= 12; $i++) {
                Cache::forget("chart_data_{$i}");
            }
            
            $this->info('   ✅ Widget caches cleared');

            // 4. Rebuild application caches
            $this->info('4. Rebuilding application caches...');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            $this->info('   ✅ Application caches rebuilt');

            // 5. Pre-warm critical caches
            $this->info('5. Pre-warming critical caches...');
            try {
                QueryOptimizationService::getDashboardStats();
                QueryOptimizationService::getSkpdStats(5);
                QueryOptimizationService::getChartData(6);
                QueryOptimizationService::getHealthStatusDistribution();
                $this->info('   ✅ Critical caches pre-warmed');
            } catch (\Exception $e) {
                $this->warn('   ⚠️ Some caches could not be pre-warmed: ' . $e->getMessage());
            }

            $this->newLine();
            $this->info('🎉 All caches cleared and optimized successfully!');
            $this->newLine();
            
            $this->displayPerformanceTips();

        } catch (\Exception $e) {
            $this->error('❌ Cache clearing failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function displayPerformanceTips(): void
    {
        $this->info('💡 Performance Tips:');
        $this->line('1. Run this command when system feels slow');
        $this->line('2. Monitor cache hit rates in system health widget');
        $this->line('3. Consider using Redis for production');
        $this->line('4. Regular maintenance: php artisan mcu:optimize');
        $this->line('5. Check database performance: php artisan mcu:optimize-database --analyze');
        $this->newLine();
        $this->warn('⚠️  Note: First page load after cache clear may be slower as caches rebuild');
    }
}

