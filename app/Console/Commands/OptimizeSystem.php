<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimizeSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcu:optimize {--clear-cache : Clear all caches first}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize system performance by clearing caches and rebuilding indexes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Optimizing MCU System Performance...');
        $this->newLine();

        try {
            if ($this->option('clear-cache')) {
                $this->info('🧹 Clearing all caches...');
                $this->clearAllCaches();
                $this->info('✅ Caches cleared');
            }

            // 1. Run migrations for performance indexes
            $this->info('📊 Adding performance indexes...');
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Performance indexes added');

            // 2. Optimize database
            $this->info('🔧 Optimizing database...');
            $this->optimizeDatabase();
            $this->info('✅ Database optimized');

            // 3. Pre-warm caches
            $this->info('🔥 Pre-warming caches...');
            $this->preWarmCaches();
            $this->info('✅ Caches pre-warmed');

            // 4. Clear and rebuild application caches
            $this->info('⚡ Rebuilding application caches...');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            $this->info('✅ Application caches rebuilt');

            $this->newLine();
            $this->info('🎉 System optimization completed successfully!');
            $this->newLine();
            
            $this->displayPerformanceTips();

        } catch (\Exception $e) {
            $this->error('❌ Optimization failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function clearAllCaches(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('event:clear');
        
        // Clear custom caches
        Cache::forget('dashboard_stats');
        Cache::forget('skpd_stats');
        Cache::forget('mcu_chart_data');
        Cache::forget('health_status_chart');
    }

    private function optimizeDatabase(): void
    {
        // Analyze tables for better query optimization
        $tables = ['participants', 'schedules', 'mcu_results', 'settings', 'audit_logs'];
        
        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
            } catch (\Exception $e) {
                // Ignore if table doesn't exist or other errors
            }
        }
    }

    private function preWarmCaches(): void
    {
        // Pre-warm critical caches
        try {
            // Warm dashboard stats
            $stats = DB::select("
                SELECT 
                    COUNT(DISTINCT p.id) as total_participants,
                    COUNT(DISTINCT CASE WHEN s.status = 'Terjadwal' THEN s.id END) as scheduled_participants,
                    COUNT(DISTINCT mr.id) as completed_mcu,
                    COUNT(DISTINCT CASE WHEN s.status = 'Terjadwal' AND s.tanggal_pemeriksaan >= CURDATE() THEN s.id END) as pending_mcu
                FROM participants p
                LEFT JOIN schedules s ON p.id = s.participant_id
                LEFT JOIN mcu_results mr ON p.id = mr.participant_id
            ");
            
            Cache::put('dashboard_stats', $stats[0], 900);
            
            // Warm SKPD stats
            $skpdStats = DB::select("
                SELECT 
                    participants.skpd,
                    COUNT(DISTINCT participants.id) as total,
                    COUNT(DISTINCT CASE WHEN schedules.status = 'Terjadwal' THEN schedules.id END) as scheduled_count,
                    COUNT(DISTINCT mcu_results.id) as completed_count
                FROM participants
                LEFT JOIN schedules ON participants.id = schedules.participant_id AND schedules.status = 'Terjadwal'
                LEFT JOIN mcu_results ON participants.id = mcu_results.participant_id
                GROUP BY participants.skpd
                ORDER BY total DESC
                LIMIT 5
            ");
            
            Cache::put('skpd_stats', $skpdStats, 1800);
            
        } catch (\Exception $e) {
            $this->warn('Warning: Could not pre-warm some caches: ' . $e->getMessage());
        }
    }

    private function displayPerformanceTips(): void
    {
        $this->info('💡 Performance Tips:');
        $this->line('1. Use queue worker for background tasks: php artisan queue:work');
        $this->line('2. Monitor system health in the dashboard');
        $this->line('3. Consider using Redis for caching in production');
        $this->line('4. Regular database maintenance: php artisan mcu:optimize');
        $this->line('5. Monitor slow queries in logs');
        $this->newLine();
        $this->warn('⚠️  For production: Consider using Redis cache driver and database query optimization');
    }
}

