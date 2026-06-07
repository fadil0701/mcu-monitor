<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Participant;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::query()->with('participant')->orderBy('tanggal_pemeriksaan', 'desc');
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('nama_lengkap', 'like', "%{$q}%")
                    ->orWhere('nik_ktp', 'like', "%{$q}%")
                    ->orWhere('lokasi_pemeriksaan', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $query->whereDate('tanggal_pemeriksaan', $request->date);
        }
        $schedules = $query->paginate(15)->withQueryString();
        return view('admin.schedules.index', compact('schedules'));
    }

    public function create(Request $request)
    {
        $participants = Participant::orderBy('nama_lengkap')->get();
        $participantId = $request->get('participant_id');
        return view('admin.schedules.create', compact('participants', 'participantId'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'participant_id' => 'required|exists:participants,id',
            'tanggal_pemeriksaan' => 'required|date|after_or_equal:today',
            'jam_pemeriksaan' => 'required|string|max:10',
            'lokasi_pemeriksaan' => 'required|string|max:500',
            'status' => 'nullable|in:Terjadwal,Selesai,Batal,Ditolak',
            'catatan' => 'nullable|string',
        ]);
        $p = Participant::findOrFail($valid['participant_id']);
        $valid['nik_ktp'] = $p->nik_ktp;
        $valid['nrk_pegawai'] = $p->nrk_pegawai;
        $valid['nama_lengkap'] = $p->nama_lengkap;
        $valid['tanggal_lahir'] = $p->tanggal_lahir;
        $valid['jenis_kelamin'] = $p->jenis_kelamin;
        $valid['skpd'] = $p->skpd;
        $valid['ukpd'] = $p->ukpd;
        $valid['no_telp'] = $p->no_telp;
        $valid['email'] = $p->email;
        $valid['status'] = $valid['status'] ?? 'Terjadwal';
        $valid['jam_pemeriksaan'] = \Carbon\Carbon::parse($valid['jam_pemeriksaan'])->format('H:i:s');
        $valid['lokasi_pemeriksaan'] = $valid['lokasi_pemeriksaan'] ?? config('mcu.default_location');

        if (!Schedule::hasQuotaAvailable($valid['tanggal_pemeriksaan'], $valid['lokasi_pemeriksaan'])) {
            return back()->withErrors(['tanggal_pemeriksaan' => 'Kuota untuk tanggal dan lokasi ini sudah penuh.']);
        }

        $schedule = Schedule::create($valid);
        $schedule->update(['queue_number' => Schedule::getNextQueueNumber(
            $schedule->tanggal_pemeriksaan,
            $schedule->lokasi_pemeriksaan,
            $schedule->id
        )]);

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule)
    {
        $schedule->load('participant');
        $participants = Participant::orderBy('nama_lengkap')->get();
        return view('admin.schedules.edit', compact('schedule', 'participants'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $valid = $request->validate([
            'participant_id' => 'required|exists:participants,id',
            'tanggal_pemeriksaan' => 'required|date',
            'jam_pemeriksaan' => 'required|string|max:10',
            'lokasi_pemeriksaan' => 'required|string|max:500',
            'status' => 'required|in:Terjadwal,Selesai,Batal,Ditolak',
            'catatan' => 'nullable|string',
        ]);
        $p = Participant::findOrFail($valid['participant_id']);
        $schedule->nik_ktp = $p->nik_ktp;
        $schedule->nrk_pegawai = $p->nrk_pegawai;
        $schedule->nama_lengkap = $p->nama_lengkap;
        $schedule->tanggal_lahir = $p->tanggal_lahir;
        $schedule->jenis_kelamin = $p->jenis_kelamin;
        $schedule->skpd = $p->skpd;
        $schedule->ukpd = $p->ukpd;
        $schedule->no_telp = $p->no_telp;
        $schedule->email = $p->email;
        $schedule->tanggal_pemeriksaan = $valid['tanggal_pemeriksaan'];
        $schedule->jam_pemeriksaan = \Carbon\Carbon::parse($valid['jam_pemeriksaan'])->format('H:i:s');
        $schedule->lokasi_pemeriksaan = $valid['lokasi_pemeriksaan'] ?? config('mcu.default_location');
        $schedule->status = $valid['status'];
        $schedule->catatan = $valid['catatan'] ?? null;

        if (in_array($schedule->status, ['Terjadwal', 'Selesai'])) {
            if (!Schedule::hasQuotaAvailable(
                $schedule->tanggal_pemeriksaan,
                $schedule->lokasi_pemeriksaan,
                $schedule->id
            )) {
                return back()->withErrors(['tanggal_pemeriksaan' => 'Kuota untuk tanggal dan lokasi ini sudah penuh.']);
            }
            $schedule->queue_number = Schedule::getNextQueueNumber(
                $schedule->tanggal_pemeriksaan,
                $schedule->lokasi_pemeriksaan,
                $schedule->id
            );
        } else {
            $schedule->queue_number = null;
        }

        $schedule->save();
        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil diubah.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer|exists:schedules,id']);
        $count = Schedule::whereIn('id', $request->ids)->delete();
        return redirect()->route('admin.schedules.index', $request->only(['search', 'date', 'status']))
            ->with('success', "{$count} jadwal berhasil dihapus.");
    }

    public function quickStatus(Request $request, Schedule $schedule)
    {
        $request->validate(['status' => 'required|in:Terjadwal,Selesai,Batal,Ditolak']);
        $schedule->update(['status' => $request->status]);
        return redirect()->back()->with('success', 'Status jadwal berhasil diubah.');
    }

    public function sendEmail(Schedule $schedule)
    {
        $emailService = new EmailService();
        if ($emailService->sendMcuInvitation($schedule)) {
            return redirect()->back()->with('success', 'Email undangan berhasil dikirim ke ' . ($schedule->email ?: $schedule->nama_lengkap) . '.');
        }
        return redirect()->back()->withErrors(['send' => 'Gagal mengirim email. Periksa pengaturan SMTP.']);
    }

    public function sendWhatsApp(Schedule $schedule)
    {
        if (empty($schedule->no_telp)) {
            return redirect()->back()->withErrors(['send' => 'Nomor telepon peserta tidak tersedia.']);
        }
        $whatsappService = new WhatsAppService();
        if ($whatsappService->sendMcuInvitation($schedule)) {
            return redirect()->back()->with('success', 'WhatsApp undangan berhasil dikirim ke ' . $schedule->nama_lengkap . '.');
        }
        return redirect()->back()->withErrors(['send' => 'Gagal mengirim WhatsApp. Periksa pengaturan di Settings.']);
    }
}
