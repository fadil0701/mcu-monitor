<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\McuResult;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use App\Models\Participant;
use Illuminate\Http\Request;

class McuResultController extends Controller
{
    public function index(Request $request)
    {
        $query = McuResult::query()->with(['participant', 'schedule'])->orderBy('tanggal_pemeriksaan', 'desc');
        if ($request->filled('search')) {
            $q = $request->search;
            $query->whereHas('participant', function ($qry) use ($q) {
                $qry->where('nama_lengkap', 'like', "%{$q}%")->orWhere('nik_ktp', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status_kesehatan')) {
            $query->where('status_kesehatan', $request->status_kesehatan);
        }
        $results = $query->paginate(15)->withQueryString();
        return view('admin.mcu-results.index', compact('results'));
    }

    public function create(Request $request)
    {
        $participants = Participant::orderBy('nama_lengkap')->get();
        $participantId = $request->get('participant_id');
        return view('admin.mcu-results.create', compact('participants', 'participantId'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'participant_id' => 'required|exists:participants,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'tanggal_pemeriksaan' => 'required|date|before_or_equal:today',
            'file_hasil' => 'required|array',
            'file_hasil.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,bmp,tiff|max:10240',
            'is_published' => 'nullable|boolean',
        ]);
        $valid['is_published'] = (bool) ($valid['is_published'] ?? false);
        $valid['uploaded_by'] = auth()->id();
        $valid['schedule_id'] = $valid['schedule_id'] ?? null;
        $valid['status_kesehatan'] = 'Sehat';
        $valid['hasil_pemeriksaan'] = '';
        $valid['rekomendasi'] = null;
        $valid['diagnosis'] = null;
        $valid['diagnosis_list'] = null;
        $valid['specialist_doctor_ids'] = [];
        if ($request->hasFile('file_hasil')) {
            $paths = [];
            foreach ($request->file('file_hasil') as $file) {
                $paths[] = $file->store('mcu-results', 'public');
            }
            $valid['file_hasil_files'] = $paths;
            $valid['file_hasil'] = $paths[0] ?? null;
        }
        McuResult::create($valid);
        return redirect()->route('admin.mcu-results.index')->with('success', 'Hasil MCU berhasil ditambahkan.');
    }

    public function edit(McuResult $mcu_result)
    {
        $mcu_result->load(['participant', 'schedule']);
        $participants = Participant::orderBy('nama_lengkap')->get();
        return view('admin.mcu-results.edit', [
            'mcuResult' => $mcu_result,
            'participants' => $participants,
        ]);
    }

    public function update(Request $request, McuResult $mcu_result)
    {
        $valid = $request->validate([
            'participant_id' => 'required|exists:participants,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'tanggal_pemeriksaan' => 'required|date|before_or_equal:today',
            'file_hasil' => 'nullable|array',
            'file_hasil.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,bmp,tiff|max:10240',
            'is_published' => 'nullable|boolean',
        ]);
        $valid['is_published'] = (bool) ($valid['is_published'] ?? false);
        if ($request->hasFile('file_hasil')) {
            $paths = [];
            foreach ($request->file('file_hasil') as $file) {
                $paths[] = $file->store('mcu-results', 'public');
            }
            $existing = $mcu_result->file_hasil_files ?? [];
            $valid['file_hasil_files'] = array_merge($existing, $paths);
            $valid['file_hasil'] = $valid['file_hasil_files'][0] ?? null;
        }
        $mcu_result->update($valid);
        return redirect()->route('admin.mcu-results.index')->with('success', 'Hasil MCU berhasil diubah.');
    }

    public function destroy(McuResult $mcu_result)
    {
        $mcu_result->delete();
        return redirect()->route('admin.mcu-results.index')->with('success', 'Hasil MCU berhasil dihapus.');
    }

    public function sendEmail(McuResult $mcu_result)
    {
        $participant = $mcu_result->participant;
        if (!$participant || empty($participant->email)) {
            return redirect()->back()->withErrors(['send' => 'Email peserta tidak tersedia.']);
        }
        $emailService = new EmailService();
        if ($emailService->sendMcuResult($mcu_result)) {
            return redirect()->back()->with('success', 'Email hasil MCU berhasil dikirim ke ' . $participant->email . '.');
        }
        return redirect()->back()->withErrors(['send' => 'Gagal mengirim email. Periksa pengaturan SMTP.']);
    }

    public function sendWhatsApp(McuResult $mcu_result)
    {
        $participant = $mcu_result->participant;
        if (!$participant || empty($participant->no_telp)) {
            return redirect()->back()->withErrors(['send' => 'Nomor telepon peserta tidak tersedia.']);
        }
        $whatsappService = new WhatsAppService();
        if ($whatsappService->sendMcuResult($mcu_result)) {
            return redirect()->back()->with('success', 'WhatsApp hasil MCU berhasil dikirim ke ' . $participant->nama_lengkap . '.');
        }
        return redirect()->back()->withErrors(['send' => 'Gagal mengirim WhatsApp. Periksa pengaturan di Settings.']);
    }
}
