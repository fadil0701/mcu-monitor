<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\McuWorkCalendarClosure;
use App\Models\McuWorkCalendarSetting;
use App\Support\McuDailyQuota;
use App\Support\McuWorkCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class McuWorkCalendarController extends Controller
{
    public function __construct(
        private readonly McuWorkCalendar $workCalendar,
    ) {}

    public function index(Request $request): View
    {
        $year = (int) $request->integer('year', now()->year);
        $month = (int) $request->integer('month', now()->month);
        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $closures = McuWorkCalendarClosure::query()
            ->whereBetween('closure_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('closure_date')
            ->get();

        return view('admin.mcu-work-calendar.index', [
            'settings' => McuWorkCalendarSetting::current(),
            'closures' => $closures,
            'calendar' => $this->workCalendar->monthPayload($year, $month),
            'closureTypes' => config('mcu_work_calendar.closure_types', []),
            'year' => $year,
            'month' => $month,
            'monthLabel' => McuDailyQuota::indonesianMonthLabel($month).' '.$year,
        ]);
    }

    public function storeClosure(Request $request): RedirectResponse
    {
        $types = array_keys(config('mcu_work_calendar.closure_types', []));

        $data = $request->validate([
            'closure_date' => 'required|date',
            'type' => ['required', 'string', Rule::in($types)],
            'label' => 'required|string|max:255',
        ]);

        $date = Carbon::parse($data['closure_date'])->toDateString();

        McuWorkCalendarClosure::query()->updateOrCreate(
            ['closure_date' => $date],
            [
                'type' => $data['type'],
                'label' => $data['label'],
            ],
        );

        $this->workCalendar->clearCache();

        return back()->with('success', 'Libur tanggal '.Carbon::parse($date)->locale('id')->translatedFormat('d F Y').' berhasil disimpan.');
    }

    public function destroyClosure(McuWorkCalendarClosure $closure): RedirectResponse
    {
        $date = $closure->closure_date->toDateString();
        $closure->delete();

        $this->workCalendar->clearCache();

        return back()->with('success', 'Libur tanggal '.Carbon::parse($date)->locale('id')->translatedFormat('d F Y').' dihapus.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $settings = McuWorkCalendarSetting::current();
        $settings->update([
            'block_weekends' => $request->boolean('block_weekends'),
        ]);

        $this->workCalendar->clearCache();

        return back()->with('success', 'Pengaturan kalender kerja berhasil disimpan.');
    }
}
