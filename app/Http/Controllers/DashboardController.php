<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Support\ScheduleStatuses;
use App\Services\QueryOptimizationService;
use App\Support\McuDailyQuota;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Satu dashboard untuk semua role: tampilan berbeda untuk admin vs peserta.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasStaffAccess()) {
            return $this->adminDashboard();
        }

        return $this->participantDashboard();
    }

    /**
     * Data dan view untuk dashboard admin (tanpa Filament).
     */
    protected function adminDashboard()
    {
        $stats = QueryOptimizationService::getDashboardStats();
        $topSkpds = QueryOptimizationService::getSkpdStats(5);
        $chartData = QueryOptimizationService::getChartData(6);
        $mcuResultSummary = QueryOptimizationService::getMcuResultSummary();
        $ckgSummary = QueryOptimizationService::getCkgSummary();
        $todayOperational = QueryOptimizationService::getTodayOperationalStats();
        $monthStats = QueryOptimizationService::getThisMonthStats();
        $healthDistribution = QueryOptimizationService::getHealthStatusDistribution();
        $quotaToday = McuDailyQuota::snapshot(now()->toDateString());

        $totalParticipants = max(1, (int) ($stats->total_participants ?? 0));
        $percentages = (object) [
            'sudah_mcu' => round(((int) ($stats->sudah_mcu_status ?? 0) / $totalParticipants) * 100, 1),
            'belum_mcu' => round(((int) ($stats->belum_mcu_status ?? 0) / $totalParticipants) * 100, 1),
            'ditolak' => round(((int) ($stats->ditolak_mcu_status ?? 0) / $totalParticipants) * 100, 1),
            'ckg' => round(((int) ($ckgSummary->completed ?? 0) / $totalParticipants) * 100, 1),
        ];
        // Data untuk line chart (6 bulan)
        $months = collect();
        $participantsData = $chartData['participantsData'];
        $mcuResultsData = $chartData['mcuResultsData'];
        $participantsByMonth = [];
        $mcuResultsByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $months->push($date->format('M Y'));
            $participantsByMonth[] = $participantsData->get($monthKey, 0);
            $mcuResultsByMonth[] = $mcuResultsData->get($monthKey, 0);
        }

        // Tabel konfirmasi hadir hari ini
        $participantCkgColumns = 'participant:id,nama_lengkap,nik_ktp,ckg_peserta_id,ckg_registration_code,ckg_synced_at';

        $confirmedToday = Schedule::query()
            ->with([$participantCkgColumns])
            ->whereDate('tanggal_pemeriksaan', now()->toDateString())
            ->where('status', 'Terjadwal')
            ->where('participant_confirmed', true)
            ->orderBy('jam_pemeriksaan')
            ->limit(30)
            ->get();

        // Statistik konfirmasi & reschedule (ConfirmRescheduleStatsWidget)
        $confirmedTodayCount = Schedule::whereDate('tanggal_pemeriksaan', now()->toDateString())
            ->where('participant_confirmed', true)->count();
        $pendingRescheduleToday = Schedule::whereDate('reschedule_requested_at', now()->toDateString())
            ->where('reschedule_requested', true)->count();

        // Antrian lengkap hari ini (TodayQueueTable)
        $todayQueue = Schedule::query()
            ->with([$participantCkgColumns])
            ->whereDate('tanggal_pemeriksaan', now()->toDateString())
            ->orderBy('jam_pemeriksaan')
            ->limit(50)
            ->get();

        // Grafik antrian per jam (DailyQueueChart)
        $today = now()->toDateString();
        $hours = range(0, 23);
        $dailyQueueData = [
            'labels' => array_map(fn ($h) => sprintf('%02d:00', $h), $hours),
            'terjadwal' => [],
            'selesai' => [],
            'batal' => [],
            'ditolak' => [],
        ];
        foreach (['Terjadwal' => 'terjadwal', 'Selesai' => 'selesai', 'Batal' => 'batal', 'Ditolak' => 'ditolak'] as $status => $key) {
            $map = array_fill_keys($hours, 0);
            Schedule::whereDate('tanggal_pemeriksaan', $today)
                ->where('status', $status)
                ->get()
                ->each(function ($s) use (&$map) {
                    try {
                        $h = (int) \Carbon\Carbon::parse($s->jam_pemeriksaan ?? '00:00:00')->format('H');
                        $map[$h] = ($map[$h] ?? 0) + 1;
                    } catch (\Throwable $e) {
                        $map[0] = ($map[0] ?? 0) + 1;
                    }
                });
            $dailyQueueData[$key] = array_values($map);
        }

        // Pengajuan jadwal MCU oleh peserta yang menunggu konfirmasi admin (30 hari terakhir)
        $recentScheduleRequests = Schedule::query()
            ->with([$participantCkgColumns])
            ->where('status', ScheduleStatuses::PENDING_ADMIN)
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('dashboard.admin', [
            'stats' => $stats,
            'topSkpds' => $topSkpds,
            'chartLabels' => $months->toArray(),
            'participantsByMonth' => $participantsByMonth,
            'mcuResultsByMonth' => $mcuResultsByMonth,
            'confirmedToday' => $confirmedToday,
            'confirmedTodayCount' => $confirmedTodayCount,
            'pendingRescheduleToday' => $pendingRescheduleToday,
            'todayQueue' => $todayQueue,
            'recentScheduleRequests' => $recentScheduleRequests,
            'dailyQueueData' => $dailyQueueData,
            'mcuResultSummary' => $mcuResultSummary,
            'ckgSummary' => $ckgSummary,
            'todayOperational' => $todayOperational,
            'monthStats' => $monthStats,
            'healthDistribution' => $healthDistribution,
            'quotaToday' => $quotaToday,
            'percentages' => $percentages,
        ]);
    }

    /**
     * Data dan view untuk dashboard peserta (reuse logic ClientController).
     */
    protected function participantDashboard()
    {
        return redirect()->route('client.dashboard');
    }
}
