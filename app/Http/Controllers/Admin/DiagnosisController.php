<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\DiagnosesImport;
use App\Models\Diagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class DiagnosisController extends Controller
{
    public function index(Request $request)
    {
        $query = Diagnosis::query()->orderBy('name');
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }
        if ($request->filled('is_active')) {
            if ($request->is_active === '1') {
                $query->where('is_active', true);
            } elseif ($request->is_active === '0') {
                $query->where('is_active', false);
            }
        }
        $diagnoses = $query->paginate(15)->withQueryString();
        return view('admin.diagnoses.index', compact('diagnoses'));
    }

    public function create()
    {
        return view('admin.diagnoses.create');
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $valid['is_active'] = (bool) ($request->boolean('is_active', true));
        Diagnosis::create($valid);
        return redirect()->route('admin.diagnoses.index')->with('success', 'Diagnosis berhasil ditambahkan.');
    }

    public function edit(Diagnosis $diagnosis)
    {
        return view('admin.diagnoses.edit', compact('diagnosis'));
    }

    public function update(Request $request, Diagnosis $diagnosis)
    {
        $valid = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
        $valid['is_active'] = (bool) ($request->boolean('is_active', true));
        $diagnosis->update($valid);
        return redirect()->route('admin.diagnoses.index')->with('success', 'Diagnosis berhasil diubah.');
    }

    public function destroy(Diagnosis $diagnosis)
    {
        $diagnosis->delete();
        return redirect()->route('admin.diagnoses.index')->with('success', 'Diagnosis berhasil dihapus.');
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        $columns = [
            ['code', 'name', 'description', 'is_active'],
            ['D01', 'Diabetes Melitus Tipe 2', 'Deskripsi singkat diagnosis', '1'],
            ['H01', 'Hipertensi Esensial', 'Deskripsi singkat diagnosis', '1'],
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'diagnoses_tpl_') . '.xlsx';
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($columns as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 1, $value);
            }
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tmp);
        return response()->download($tmp, 'template_import_diagnosis.xlsx', $headers)->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = ['xlsx', 'xls', 'csv'];
        if (!in_array($extension, $allowed, true)) {
            return redirect()->route('admin.diagnoses.index')
                ->with('error', 'Format file tidak didukung. Gunakan XLSX, XLS, atau CSV.');
        }

        try {
            $stored = $file->storeAs('imports', 'diagnoses_' . now()->format('Ymd_His') . '.' . $extension, 'public');
            $fullPath = Storage::disk('public')->path($stored);
            $import = new DiagnosesImport();
            Excel::import($import, $fullPath);
            $stats = $import->getImportStats();
            $msg = sprintf('Import selesai. Ditambahkan: %d, Diubah: %d, Dilewati: %d, Error: %d.', $stats['imported'], $stats['updated'], $stats['skipped'], count($stats['errors'] ?? []));
            return redirect()->route('admin.diagnoses.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('admin.diagnoses.index')
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }
}
