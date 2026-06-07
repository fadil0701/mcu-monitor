<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\ParticipantsImport;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $query = Participant::query()->orderBy('nama_lengkap');
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('nama_lengkap', 'like', "%{$q}%")
                    ->orWhere('nik_ktp', 'like', "%{$q}%")
                    ->orWhere('nrk_pegawai', 'like', "%{$q}%")
                    ->orWhere('skpd', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status_mcu')) {
            $query->where('status_mcu', $request->status_mcu);
        }
        $participants = $query->paginate(15)->withQueryString();
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
            'no_telp' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'status_mcu' => 'nullable|in:Belum MCU,Sudah MCU,Ditolak',
            'tanggal_mcu_terakhir' => 'nullable|date|before_or_equal:today',
            'catatan' => 'nullable|string',
        ]);
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
            'no_telp' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'status_mcu' => 'nullable|in:Belum MCU,Sudah MCU,Ditolak',
            'tanggal_mcu_terakhir' => 'nullable|date|before_or_equal:today',
            'catatan' => 'nullable|string',
        ]);
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
            Excel::import(new ParticipantsImport, $fullPath);
            return redirect()->route('admin.participants.index')->with('success', 'Import peserta berhasil.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.participants.index')
                ->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }
}
