<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QueryOptimizationService
{
    /**
     * Get optimized dashboard statistics
     */
    public static function getDashboardStats(): object
    {
        return Cache::remember('optimized_dashboard_stats', 900, function () {
            $startTime = microtime(true);
            
            // Single optimized query for all dashboard stats
            $stats = DB::select("
                SELECT 
                    COUNT(DISTINCT p.id) as total_participants,
                    COUNT(DISTINCT CASE WHEN s.status = 'Terjadwal' THEN s.id END) as scheduled_participants,
                    COUNT(DISTINCT mr.id) as completed_mcu,
                    COUNT(DISTINCT CASE WHEN s.status = 'Terjadwal' AND s.tanggal_pemeriksaan >= CURDATE() THEN s.id END) as pending_mcu,
                    COUNT(DISTINCT CASE WHEN s.status = 'Selesai' THEN s.id END) as completed_schedules,
                    COUNT(DISTINCT CASE WHEN s.status = 'Batal' THEN s.id END) as cancelled_schedules,
                    COUNT(DISTINCT CASE WHEN s.status = 'Ditolak' THEN s.id END) as rejected_schedules
                FROM participants p
                LEFT JOIN schedules s ON p.id = s.participant_id
                LEFT JOIN mcu_results mr ON p.id = mr.participant_id
            ");
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("Dashboard stats query executed in {$executionTime}ms");
            
            return $stats[0];
        });
    }

    /**
     * Get optimized SKPD statistics with pagination
     */
    public static function getSkpdStats(int $limit = 5): array
    {
        return Cache::remember("skpd_stats_{$limit}", 1800, function () use ($limit) {
            $startTime = microtime(true);
            
            $stats = DB::select("
                SELECT 
                    p.skpd,
                    COUNT(DISTINCT p.id) as total_participants,
                    COUNT(DISTINCT CASE WHEN s.status = 'Terjadwal' THEN s.id END) as scheduled_count,
                    COUNT(DISTINCT CASE WHEN s.status = 'Selesai' THEN s.id END) as completed_count,
                    COUNT(DISTINCT mr.id) as mcu_results_count,
                    ROUND(
                        (COUNT(DISTINCT CASE WHEN s.status = 'Selesai' THEN s.id END) / 
                         NULLIF(COUNT(DISTINCT p.id), 0)) * 100, 2
                    ) as completion_rate
                FROM participants p
                LEFT JOIN schedules s ON p.id = s.participant_id
                LEFT JOIN mcu_results mr ON p.id = mr.participant_id
                GROUP BY p.skpd
                HAVING total_participants > 0
                ORDER BY total_participants DESC, completion_rate DESC
                LIMIT ?
            ", [$limit]);
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("SKPD stats query executed in {$executionTime}ms");
            
            return $stats;
        });
    }

    /**
     * Get optimized chart data with date range
     */
    public static function getChartData(int $months = 6): array
    {
        return Cache::remember("chart_data_{$months}", 3600, function () use ($months) {
            $startTime = microtime(true);
            $startDate = now()->subMonths($months)->startOfMonth();
            $endDate = now()->endOfMonth();

            $data = DB::select("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    'participants' as type,
                    COUNT(*) as count
                FROM participants 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY month
                
                UNION ALL
                
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    'mcu_results' as type,
                    COUNT(*) as count
                FROM mcu_results 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY month
                
                ORDER BY month
            ", [$startDate, $endDate, $startDate, $endDate]);

            $participantsData = collect();
            $mcuResultsData = collect();
            
            foreach ($data as $row) {
                if ($row->type === 'participants') {
                    $participantsData->put($row->month, $row->count);
                } else {
                    $mcuResultsData->put($row->month, $row->count);
                }
            }
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("Chart data query executed in {$executionTime}ms");
            
            return compact('participantsData', 'mcuResultsData');
        });
    }

    /**
     * Get optimized today's queue with filters
     */
    public static function getTodayQueue(array $filters = []): array
    {
        $cacheKey = 'today_queue_' . md5(serialize($filters));
        
        return Cache::remember($cacheKey, 120, function () use ($filters) {
            $startTime = microtime(true);
            
            $query = DB::table('schedules')
                ->join('participants', 'schedules.participant_id', '=', 'participants.id')
                ->select([
                    'schedules.id',
                    'schedules.tanggal_pemeriksaan',
                    'schedules.jam_pemeriksaan',
                    'schedules.lokasi_pemeriksaan',
                    'schedules.status',
                    'schedules.queue_number',
                    'participants.nama_lengkap',
                    'participants.nik_ktp'
                ])
                ->whereDate('schedules.tanggal_pemeriksaan', now()->toDateString())
                ->orderBy('schedules.jam_pemeriksaan')
                ->limit(50);

            // Apply filters
            if (isset($filters['status'])) {
                $query->where('schedules.status', $filters['status']);
            }
            
            if (isset($filters['skpd'])) {
                $query->where('participants.skpd', $filters['skpd']);
            }

            $results = $query->get();
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("Today queue query executed in {$executionTime}ms");
            
            return $results->toArray();
        });
    }

    /**
     * Get health status distribution
     */
    public static function getHealthStatusDistribution(): array
    {
        return Cache::remember('health_status_distribution', 1800, function () {
            $startTime = microtime(true);
            
            $stats = DB::select("
                SELECT 
                    status_kesehatan,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM mcu_results), 2) as percentage
                FROM mcu_results 
                GROUP BY status_kesehatan
                ORDER BY count DESC
            ");
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("Health status distribution query executed in {$executionTime}ms");
            
            return $stats;
        });
    }

    /**
     * Analyze slow queries and suggest optimizations
     */
    public static function analyzeSlowQueries(): array
    {
        try {
            // Get slow query log (if enabled)
            $slowQueries = DB::select("
                SELECT 
                    sql_text,
                    exec_count,
                    avg_timer_wait/1000000000 as avg_time_seconds,
                    max_timer_wait/1000000000 as max_time_seconds
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE avg_timer_wait > 1000000000 
                ORDER BY avg_timer_wait DESC 
                LIMIT 10
            ");
            
            return $slowQueries;
        } catch (\Exception $e) {
            Log::warning('Could not analyze slow queries: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get database performance metrics
     */
    public static function getDatabaseMetrics(): array
    {
        return Cache::remember('database_metrics', 300, function () {
            try {
                $metrics = DB::select("
                    SELECT 
                        'table_size' as metric,
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as value
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE()
                    
                    UNION ALL
                    
                    SELECT 
                        'total_tables' as metric,
                        COUNT(*) as value
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE()
                    
                    UNION ALL
                    
                    SELECT 
                        'total_indexes' as metric,
                        COUNT(*) as value
                    FROM information_schema.statistics 
                    WHERE table_schema = DATABASE()
                ");
                
                return collect($metrics)->pluck('value', 'metric')->toArray();
            } catch (\Exception $e) {
                Log::warning('Could not get database metrics: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Clear all query caches
     */
    public static function clearQueryCaches(): void
    {
        $cacheKeys = [
            'optimized_dashboard_stats',
            'chart_data_6',
            'health_status_distribution',
            'database_metrics'
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // Clear SKPD stats with different limits
        for ($i = 1; $i <= 20; $i++) {
            Cache::forget("skpd_stats_{$i}");
        }
        
        Log::info('Query optimization caches cleared');
    }
}
