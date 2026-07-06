<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SqlFilter;
use App\Support\SqlLike;
use App\Exports\ParticipantsImportTemplateExport;
use App\Imports\ParticipantsImport;
use App\Models\Participant;
use App\Support\ParticipantEducation;
use App\Support\ValidationMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $query = Participant::query()->orderBy('nama_lengkap');
        if ($request->filled('search')) {
            $q = (string) $request->search;
            $pattern = SqlLike::contains($q);
            $query->where(function ($qry) use ($pattern) {
                $qry->where('nama_lengkap', 'like', $pattern)
                    ->orWhere('nik_ktp', 'like', $pattern)
                    ->orWhere('nrk_pegawai', 'like', $pattern)
                    ->orWhere('skpd', 'like', $pattern);
            });
        }
        $statusMcu = SqlFilter::enum(
            $request->filled('status_mcu') ? (string) $request->status_mcu : null,
            ['Belum MCU', 'Sudah MCU', 'Ditolak'],
        );
        if ($statusMcu !== null) {
            $query->where('status_mcu', $statusMcu);
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 50, 100], true)) {
            $perPage = 15;
        }

        $participants = $query->paginate($perPage)->withQueryString();
        return view('admin.participants.index', compact('participants'));
    }

    public function create()
    {
        return view('admin.participants.create');
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'nik_ktp' => 'required|digits:16|unique:participants,nik_ktp',
            'nrk_pegawai' => 'required|string|unique:participants,nrk_pegawai',
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date|before_or_equal:today',
            'jenis_kelamin' => 'required|in:L,P',
            'skpd' => 'required|string|max:255',
            'ukpd' => 'required|string|max:255',
            'status_pegawai' => 'required|in:CPNS,PNS,PPPK',
            'pendidikan_terakhir' => ['nullable', Rule::in(ParticipantEducation::levels())],
            'no_telp' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'status_mcu' => 'nullable|in:Belum MCU,Sudah MCU,Ditolak',
            'tanggal_mcu_terakhir' => 'nullable|date|before_or_equal:today',
            'catatan' => 'nullable|string',
        ], ValidationMessages::participantForm());
        $valid['status_mcu'] = $valid['status_mcu'] ?? 'Belum MCU';
        Participant::create($valid);
        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil ditambahkan.');
    }

    public function show(Participant $participant)
    {
        $participant->load(['schedules' => fn ($q) => $q->orderBy('tanggal_pemeriksaan', 'desc')->limit(10), 'mcuResults' => fn ($q) => $q->orderBy('tanggal_pemeriksaan', 'desc')->limit(10)]);
        return view('admin.participants.show', compact('participant'));
    }

    public function edit(Participant $participant)
    {
        return view('admin.participants.edit', compact('participant'));
    }

    public function update(Request $request, Participant $participant)
    {
        $valid = $request->validate([
            'nik_ktp' => 'required|digits:16|unique:participants,nik_ktp,' . $participant->id,
            'nrk_pegawai' => 'required|string|unique:participants,nrk_pegawai,' . $participant->id,
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date|before_or_equal:today',
            'jenis_kelamin' => 'required|in:L,P',
            'skpd' => 'required|string|max:255',
            'ukpd' => 'required|string|max:255',
            'status_pegawai' => 'required|in:CPNS,PNS,PPPK',
            'pendidikan_terakhir' => ['nullable', Rule::in(ParticipantEducation::levels())],
            'no_telp' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'status_mcu' => 'nullable|in:Belum MCU,Sudah MCU,Ditolak',
            'tanggal_mcu_terakhir' => 'nullable|date|before_or_equal:today',
            'catatan' => 'nullable|string',
        ], ValidationMessages::participantForm());
        $participant->update($valid);
        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil diubah.');
    }

    public function destroy(Participant $participant)
    {
        $participant->delete();
        return redirect()->route('admin.participants.index')->with('success', 'Peserta berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:participants,id',
        ]);

        $count = Participant::whereIn('id', $request->ids)->delete();
        return redirect()->route('admin.participants.index', $request->only(['search', 'status_mcu']))
            ->with('success', "{$count} peserta berhasil dihapus.");
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'participants_tpl_').'.xlsx';
        ParticipantsImportTemplateExport::saveTo($tmp);

        return response()->download($tmp, 'template_import_peserta.xlsx', $headers)->deleteFileAfterSend(true);
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
            return redirect()->route('admin.participants.index')
                ->with('error', 'Format file tidak didukung. Gunakan XLSX, XLS, atau CSV.');
        }

        try {
            $stored = $file->storeAs('imports', 'participants_' . now()->format('Ymd_His') . '.' . $extension, 'public');
            $fullPath = Storage::disk('public')->path($stored);
            $import = new ParticipantsImport;
            Excel::import($import, $fullPath);

            return redirect()->route('admin.participants.index')->with('success', sprintf(
                'Import peserta selesai: %d data baru, %d data diperbarui, %d data dilewati (sudah ada).',
                $import->createdCount,
                $import->updatedCount,
                $import->skippedCount,
            ));
        } catch (ValidationException $e) {
            $details = collect($e->failures())
                ->take(5)
                ->map(fn ($failure) => 'Baris '.$failure->row().': '.implode(' ', $failure->errors()))
                ->implode(' | ');

            return redirect()->route('admin.participants.index')
                ->with('error', 'Import gagal validasi. Pastikan mengisi sheet Data Peserta (bukan Referensi/Petunjuk). '.$details);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'Sheet') && str_contains($message, 'not found')) {
                $message = 'Sheet "Data Peserta" tidak ditemukan. Unduh ulang template dan isi data di sheet tersebut.';
            }

            return redirect()->route('admin.participants.index')
                ->with('error', 'Import gagal: '.$message);
        }
    }

    public function scheduleMeta(Participant $participant)
    {
        return response()->json([
            'within_mcu_interval' => $participant->isWithinMcuInterval(),
            'ckg' => [
                'label' => $participant->ckgStatusLabel(),
                'badge' => $participant->ckgStatusBadgeClass(),
                'hint' => $participant->ckgStatusHint(),
            ],
        ]);
    }
}
