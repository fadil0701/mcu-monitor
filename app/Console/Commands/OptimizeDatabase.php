<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Services\QueryOptimizationService;

class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcu:optimize-database {--analyze : Analyze slow queries} {--vacuum : Vacuum/optimize tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database performance for MCU system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Optimizing Database Performance...');
        $this->newLine();

        try {
            // 1. Analyze slow queries
            if ($this->option('analyze')) {
                $this->info('📊 Analyzing slow queries...');
                $this->analyzeSlowQueries();
            }

            // 2. Optimize tables
            if ($this->option('vacuum')) {
                $this->info('🗄️ Optimizing database tables...');
                $this->optimizeTables();
            }

            // 3. Update table statistics
            $this->info('📈 Updating table statistics...');
            $this->updateTableStatistics();

            // 4. Clear query caches
            $this->info('🧹 Clearing query caches...');
            QueryOptimizationService::clearQueryCaches();

            // 5. Pre-warm critical caches
            $this->info('🔥 Pre-warming critical caches...');
            $this->preWarmCaches();

            $this->newLine();
            $this->info('✅ Database optimization completed successfully!');
            $this->newLine();
            
            $this->displayOptimizationResults();

        } catch (\Exception $e) {
            $this->error('❌ Database optimization failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function analyzeSlowQueries(): void
    {
        $slowQueries = QueryOptimizationService::analyzeSlowQueries();
        
        if (empty($slowQueries)) {
            $this->warn('No slow queries found or slow query log not enabled.');
            return;
        }

        $this->table(
            ['Query', 'Exec Count', 'Avg Time (s)', 'Max Time (s)'],
            collect($slowQueries)->map(function ($query) {
                return [
                    substr($query->sql_text, 0, 50) . '...',
                    $query->exec_count,
                    round($query->avg_time_seconds, 3),
                    round($query->max_time_seconds, 3)
                ];
            })->toArray()
        );
    }

    private function optimizeTables(): void
    {
        $tables = ['participants', 'schedules', 'mcu_results', 'settings', 'audit_logs'];
        
        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
                $this->line("✅ Optimized table: {$table}");
            } catch (\Exception $e) {
                $this->warn("⚠️ Could not optimize table {$table}: " . $e->getMessage());
            }
        }
    }

    private function updateTableStatistics(): void
    {
        $tables = ['participants', 'schedules', 'mcu_results', 'settings', 'audit_logs'];
        
        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
                $this->line("✅ Updated statistics for: {$table}");
            } catch (\Exception $e) {
                $this->warn("⚠️ Could not update statistics for {$table}: " . $e->getMessage());
            }
        }
    }

    private function preWarmCaches(): void
    {
        try {
            // Pre-warm dashboard stats
            QueryOptimizationService::getDashboardStats();
            $this->line("✅ Pre-warmed dashboard stats cache");

            // Pre-warm SKPD stats
            QueryOptimizationService::getSkpdStats(5);
            $this->line("✅ Pre-warmed SKPD stats cache");

            // Pre-warm chart data
            QueryOptimizationService::getChartData(6);
            $this->line("✅ Pre-warmed chart data cache");

            // Pre-warm health status distribution
            QueryOptimizationService::getHealthStatusDistribution();
            $this->line("✅ Pre-warmed health status cache");

        } catch (\Exception $e) {
            $this->warn("⚠️ Could not pre-warm some caches: " . $e->getMessage());
        }
    }

    private function displayOptimizationResults(): void
    {
        $metrics = QueryOptimizationService::getDatabaseMetrics();
        
        if (!empty($metrics)) {
            $this->info('📊 Database Metrics:');
            foreach ($metrics as $metric => $value) {
                $this->line("  • {$metric}: {$value}");
            }
            $this->newLine();
        }

        $this->info('💡 Performance Tips:');
        $this->line('1. Run this command regularly: php artisan mcu:optimize-database --analyze --vacuum');
        $this->line('2. Monitor slow query log in production');
        $this->line('3. Consider using Redis for caching');
        $this->line('4. Use database connection pooling');
        $this->line('5. Monitor database performance metrics');
    }
}

