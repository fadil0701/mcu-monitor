<?php

namespace App\Services;

use App\Support\SqlDialect;
use App\Support\SqlFilter;
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
            $intervalYears = (int) config('mcu.interval_years', 3);
            $cutoffDate = now()->subYears($intervalYears)->toDateString();
            $today = now()->toDateString();

            // Subquery terpisah agar selaras dengan halaman Data Peserta (status_mcu)
            // dan tidak terdistorsi oleh JOIN schedules + mcu_results.
            $stats = DB::selectOne('
                SELECT
                    (SELECT COUNT(*) FROM participants) as total_participants,
                    (SELECT COUNT(DISTINCT participant_id) FROM schedules WHERE status = ? AND tanggal_pemeriksaan >= ?) as scheduled_participants,
                    (SELECT COUNT(*) FROM participants WHERE status_mcu = ?) as sudah_mcu_status,
                    (SELECT COUNT(*) FROM participants WHERE status_mcu = ?) as belum_mcu_status,
                    (SELECT COUNT(*) FROM participants WHERE status_mcu = ?) as ditolak_mcu_status,
                    (SELECT COUNT(*) FROM participants WHERE tanggal_mcu_terakhir IS NOT NULL AND tanggal_mcu_terakhir >= ?) as mcu_sudah_interval,
                    (SELECT COUNT(*) FROM participants WHERE tanggal_mcu_terakhir IS NULL OR tanggal_mcu_terakhir < ?) as mcu_belum_interval,
                    (SELECT COUNT(*) FROM schedules WHERE status = ?) as completed_schedules,
                    (SELECT COUNT(*) FROM schedules WHERE status = ?) as cancelled_schedules,
                    (SELECT COUNT(*) FROM schedules WHERE status = ?) as rejected_schedules
            ', [
                'Terjadwal',
                $today,
                'Sudah MCU',
                'Belum MCU',
                'Ditolak',
                $cutoffDate,
                $cutoffDate,
                'Selesai',
                'Batal',
                'Ditolak',
            ]);

            $stats->interval_years = $intervalYears;
            $stats->interval_cutoff = $cutoffDate;
            $stats->completed_mcu = $stats->sudah_mcu_status;
            $stats->pending_mcu = $stats->belum_mcu_status;

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::info("Dashboard stats query executed in {$executionTime}ms");

            return $stats;
        });
    }

    /**
     * Ringkasan hasil MCU (upload & publikasi).
     */
    public static function getMcuResultSummary(): object
    {
        return Cache::remember('mcu_result_summary', 900, function () {
            return DB::selectOne('
                SELECT
                    (SELECT COUNT(*) FROM mcu_results) as total_results,
                    (SELECT COUNT(*) FROM mcu_results WHERE is_published IS TRUE) as published_count,
                    (SELECT COUNT(*) FROM mcu_results WHERE is_published IS NOT TRUE) as unpublished_count,
                    (SELECT COUNT(*) FROM participants p
                        WHERE p.status_mcu = ?
                        AND NOT EXISTS (SELECT 1 FROM mcu_results mr WHERE mr.participant_id = p.id)
                    ) as belum_upload
            ', ['Sudah MCU']);
        });
    }

    /**
     * Ringkasan skrining CKG peserta.
     */
    public static function getCkgSummary(): object
    {
        return Cache::remember('ckg_summary', 900, function () {
            return DB::selectOne('
                SELECT
                    (SELECT COUNT(*) FROM participants
                        WHERE ckg_peserta_id IS NOT NULL
                           OR (ckg_registration_code IS NOT NULL AND ckg_registration_code != \'\')
                    ) as completed,
                    (SELECT COUNT(*) FROM participants
                        WHERE ckg_peserta_id IS NULL
                          AND (ckg_registration_code IS NULL OR ckg_registration_code = \'\')
                    ) as belum
            ');
        });
    }

    /**
     * Statistik operasional jadwal hari ini.
     */
    public static function getTodayOperationalStats(): object
    {
        $today = now()->toDateString();

        return Cache::remember('today_operational_stats_' . $today, 120, function () use ($today) {
            return DB::selectOne('
                SELECT
                    (SELECT COUNT(*) FROM schedules WHERE tanggal_pemeriksaan = ?) as total,
                    (SELECT COUNT(*) FROM schedules WHERE tanggal_pemeriksaan = ? AND status = ?) as terjadwal,
                    (SELECT COUNT(*) FROM schedules WHERE tanggal_pemeriksaan = ? AND status = ?) as selesai,
                    (SELECT COUNT(*) FROM schedules WHERE tanggal_pemeriksaan = ? AND status = ?) as batal,
                    (SELECT COUNT(*) FROM schedules WHERE tanggal_pemeriksaan = ? AND status = ?) as ditolak,
                    (SELECT COUNT(*) FROM schedules WHERE tanggal_pemeriksaan = ? AND participant_confirmed IS TRUE) as confirmed,
                    (SELECT COUNT(*) FROM schedules WHERE tanggal_pemeriksaan = ? AND status = ? AND participant_confirmed IS NOT TRUE) as belum_konfirmasi,
                    (SELECT COUNT(*) FROM schedules WHERE tanggal_pemeriksaan = ? AND reschedule_requested IS TRUE) as reschedule_pending,
                    (SELECT COUNT(*) FROM schedules WHERE tanggal_pemeriksaan >= ? AND tanggal_pemeriksaan <= ? AND status = ?) as upcoming_week
            ', [
                $today,
                $today, 'Terjadwal',
                $today, 'Selesai',
                $today, 'Batal',
                $today, 'Ditolak',
                $today,
                $today, 'Terjadwal',
                $today,
                $today, now()->addDays(7)->toDateString(), 'Terjadwal',
            ]);
        });
    }

    /**
     * Statistik bulan berjalan (peserta baru, hasil MCU, jadwal selesai).
     */
    public static function getThisMonthStats(): object
    {
        $monthKey = now()->format('Y-m');

        return Cache::remember('this_month_stats_' . $monthKey, 900, function () {
            $start = now()->startOfMonth()->toDateTimeString();
            $end = now()->endOfMonth()->toDateTimeString();

            return DB::selectOne('
                SELECT
                    (SELECT COUNT(*) FROM participants WHERE created_at BETWEEN ? AND ?) as new_participants,
                    (SELECT COUNT(*) FROM mcu_results WHERE COALESCE(tanggal_pemeriksaan, created_at) BETWEEN ? AND ?) as mcu_results,
                    (SELECT COUNT(*) FROM schedules WHERE status = ? AND updated_at BETWEEN ? AND ?) as schedules_completed
            ', [$start, $end, $start, $end, 'Selesai', $start, $end]);
        });
    }

    /**
     * Get optimized SKPD statistics with pagination
     */
    public static function getSkpdStats(int $limit = 5): array
    {
        return Cache::remember("skpd_stats_{$limit}", 1800, function () use ($limit) {
            $startTime = microtime(true);
            
            $stats = DB::select('
                SELECT *
                FROM (
                    SELECT
                        p.skpd,
                        COUNT(DISTINCT p.id) as total_participants,
                        COUNT(DISTINCT CASE WHEN s.status = ? THEN s.id END) as scheduled_count,
                        COUNT(DISTINCT CASE WHEN s.status = ? THEN s.id END) as completed_count,
                        COUNT(DISTINCT mr.id) as mcu_results_count,
                        ROUND(
                            (COUNT(DISTINCT CASE WHEN s.status = ? THEN s.id END) /
                             NULLIF(COUNT(DISTINCT p.id), 0)) * 100, 2
                        ) as completion_rate
                    FROM participants p
                    LEFT JOIN schedules s ON p.id = s.participant_id
                    LEFT JOIN mcu_results mr ON p.id = mr.participant_id
                    GROUP BY p.skpd
                ) skpd_stats
                WHERE total_participants > 0
                ORDER BY total_participants DESC, completion_rate DESC
                LIMIT ?
            ', ['Terjadwal', 'Selesai', 'Selesai', $limit]);
            
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
            $participantMonth = SqlDialect::monthBucket('created_at');
            $mcuMonth = SqlDialect::monthBucket('COALESCE(tanggal_pemeriksaan, created_at)');

            $data = DB::select("
                SELECT 
                    {$participantMonth} as month,
                    'participants' as type,
                    COUNT(*) as count
                FROM participants 
                WHERE created_at BETWEEN ? AND ?
                GROUP BY 1
                
                UNION ALL
                
                SELECT 
                    {$mcuMonth} as month,
                    'mcu_results' as type,
                    COUNT(*) as count
                FROM mcu_results 
                WHERE COALESCE(tanggal_pemeriksaan, created_at) BETWEEN ? AND ?
                GROUP BY 1
                
                ORDER BY 1
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

            $status = SqlFilter::enum(
                isset($filters['status']) ? (string) $filters['status'] : null,
                ['Terjadwal', 'Selesai', 'Batal', 'Ditolak'],
            );
            if ($status !== null) {
                $query->where('schedules.status', $status);
            }

            if (isset($filters['skpd']) && is_string($filters['skpd']) && $filters['skpd'] !== '') {
                $query->where('participants.skpd', mb_substr($filters['skpd'], 0, 255));
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
            'database_metrics',
            'mcu_result_summary',
            'ckg_summary',
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
