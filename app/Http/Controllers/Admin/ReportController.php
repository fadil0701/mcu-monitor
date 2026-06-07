<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ParticipantsExport;
use App\Exports\SchedulesExport;
use App\Exports\McuResultsExport;
use App\Exports\DiagnosesExport;

class ReportController extends Controller
{
    public function index()
    {
        $skpds = Participant::distinct()->pluck('skpd', 'skpd');
        return view('admin.reports.index', compact('skpds'));
    }

    public function download(Request $request, string $type)
    {
        $filters = [
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'skpd' => $request->get('skpd'),
            'status_pegawai' => $request->get('status_pegawai'),
        ];

        return match ($type) {
            'participants' => Excel::download(
                new ParticipantsExport($filters),
                'participants-' . now()->format('Ymd_His') . '.xlsx'
            ),
            'schedules' => Excel::download(
                new SchedulesExport($filters),
                'schedules-' . now()->format('Ymd_His') . '.xlsx'
            ),
            'mcu' => Excel::download(
                new McuResultsExport($filters),
                'mcu-results-' . now()->format('Ymd_His') . '.xlsx'
            ),
            'diagnoses' => Excel::download(
                new DiagnosesExport($filters),
                'diagnoses-' . now()->format('Ymd_His') . '.xlsx'
            ),
            default => abort(404, 'Tipe laporan tidak valid.'),
        };
    }
}
