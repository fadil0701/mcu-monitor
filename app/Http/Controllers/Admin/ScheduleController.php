<?php

namespace App\Http\Controllers\Admin;

use App\Rules\BookableExaminationDate;
use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Schedule;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use App\Support\ScheduleParticipantNotifier;
use App\Support\ScheduleStatuses;
use App\Support\SqlFilter;
use App\Support\SqlLike;
use App\Support\WhatsAppSendSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::query()
            ->with('participant:id,nama_lengkap,nik_ktp,ckg_peserta_id,ckg_registration_code,ckg_synced_at')
            ->orderBy('tanggal_pemeriksaan', 'desc');
        if ($request->filled('search')) {
            $q = (string) $request->search;
            $pattern = SqlLike::contains($q);
            $query->where(function ($qry) use ($pattern) {
                $qry->where('nama_lengkap', 'like', $pattern)
                    ->orWhere('nik_ktp', 'like', $pattern)
                    ->orWhere('lokasi_pemeriksaan', 'like', $pattern);
            });
        }
        $status = SqlFilter::enum(
            $request->filled('status') ? (string) $request->status : null,
            ScheduleStatuses::ALL,
        );
        if ($status !== null) {
            $query->where('status', $status);
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
        $selectedParticipantId = old('participant_id', $participantId);
        $selectedParticipant = $selectedParticipantId
            ? $participants->firstWhere('id', (int) $selectedParticipantId)
            : null;

        return view('admin.schedules.create', compact('participants', 'participantId', 'selectedParticipant'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'participant_id' => 'required|exists:participants,id',
            'tanggal_pemeriksaan' => ['required', 'date', 'after_or_equal:today', new BookableExaminationDate],
            'jam_pemeriksaan' => 'required|string|max:10',
            'lokasi_pemeriksaan' => 'required|string|max:500',
            'status' => 'nullable|in:'.implode(',', ScheduleStatuses::ALL),
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
        $valid['jam_pemeriksaan'] = Carbon::parse($valid['jam_pemeriksaan'])->format('H:i:s');
        $valid['lokasi_pemeriksaan'] = $valid['lokasi_pemeriksaan'] ?? config('mcu.default_location');

        if (! Schedule::hasQuotaAvailable($valid['tanggal_pemeriksaan'], $valid['lokasi_pemeriksaan'])) {
            return back()->withErrors(['tanggal_pemeriksaan' => 'Kuota untuk tanggal dan lokasi ini sudah penuh.']);
        }

        $schedule = Schedule::create($valid);
        $schedule->update(['queue_number' => Schedule::getNextQueueNumber(
            $schedule->tanggal_pemeriksaan,
            $schedule->lokasi_pemeriksaan,
            $schedule->id
        )]);

        ScheduleParticipantNotifier::notify($schedule->fresh(), 'schedule_created');

        return redirect()->route('admin.schedules.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule)
    {
        $schedule->load('participant');
        $participants = Participant::orderBy('nama_lengkap')->get();
        $selectedParticipantId = old('participant_id', $schedule->participant_id);
        $selectedParticipant = $participants->firstWhere('id', (int) $selectedParticipantId)
            ?? $schedule->participant;

        return view('admin.schedules.edit', compact('schedule', 'participants', 'selectedParticipant'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $valid = $request->validate([
            'participant_id' => 'required|exists:participants,id',
            'tanggal_pemeriksaan' => ['required', 'date', new BookableExaminationDate],
            'jam_pemeriksaan' => 'required|string|max:10',
            'lokasi_pemeriksaan' => 'required|string|max:500',
            'status' => 'required|in:'.implode(',', ScheduleStatuses::ALL),
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
        $schedule->jam_pemeriksaan = Carbon::parse($valid['jam_pemeriksaan'])->format('H:i:s');
        $schedule->lokasi_pemeriksaan = $valid['lokasi_pemeriksaan'] ?? config('mcu.default_location');
        $schedule->catatan = $valid['catatan'] ?? null;
        $previousStatus = $schedule->status;

        if ($error = $this->applyStatusTransition($schedule, $valid['status'])) {
            return $error;
        }

        $this->notifyParticipantOnStatusChange($schedule->fresh(), $previousStatus);

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
        $request->validate(['status' => 'required|in:'.implode(',', ScheduleStatuses::ALL)]);
        $previousStatus = $schedule->status;
        $newStatus = $request->status;

        if ($error = $this->applyStatusTransition($schedule, $newStatus)) {
            return $error;
        }

        $this->notifyParticipantOnStatusChange($schedule->fresh(), $previousStatus);

        return redirect()->back()->with('success', 'Status jadwal berhasil diubah.');
    }

    private function applyStatusTransition(Schedule $schedule, string $newStatus): ?\Illuminate\Http\RedirectResponse
    {
        if (in_array($newStatus, ScheduleStatuses::QUOTA_COUNTED, true)) {
            if (! Schedule::hasQuotaAvailable(
                $schedule->tanggal_pemeriksaan->format('Y-m-d'),
                $schedule->lokasi_pemeriksaan,
                $schedule->id
            )) {
                return back()->withErrors(['status' => 'Kuota untuk tanggal dan lokasi ini sudah penuh.']);
            }

            $schedule->status = $newStatus;
            $schedule->queue_number = Schedule::getNextQueueNumber(
                $schedule->tanggal_pemeriksaan->format('Y-m-d'),
                $schedule->lokasi_pemeriksaan,
                $schedule->id
            );
        } else {
            $schedule->status = $newStatus;
            $schedule->queue_number = null;
        }

        $schedule->save();

        return null;
    }

    private function notifyParticipantOnStatusChange(Schedule $schedule, string $previousStatus): void
    {
        if ($schedule->status === $previousStatus) {
            return;
        }

        $type = match ($schedule->status) {
            'Terjadwal', 'Selesai' => 'schedule_confirmed',
            'Batal', 'Ditolak' => 'schedule_rejected',
            ScheduleStatuses::PENDING_ADMIN => 'schedule_pending',
            default => 'status_updated',
        };

        ScheduleParticipantNotifier::notify($schedule, $type);
    }

    public function sendEmail(Schedule $schedule)
    {
        $emailService = new EmailService;
        if ($emailService->sendMcuInvitation($schedule)) {
            return redirect()->back()->with('success', 'Email undangan berhasil dikirim ke '.($schedule->email ?: $schedule->nama_lengkap).'.');
        }

        return redirect()->back()->withErrors(['send' => 'Gagal mengirim email. Periksa pengaturan SMTP.']);
    }

    public function sendWhatsApp(Schedule $schedule)
    {
        if (! WhatsAppSendSettings::buttonsEnabled()) {
            return redirect()->back()->withErrors(['send' => 'Pengiriman WhatsApp dinonaktifkan di Pengaturan → WhatsApp.']);
        }

        if (empty($schedule->no_telp)) {
            return redirect()->back()->withErrors(['send' => 'Nomor telepon peserta tidak tersedia.']);
        }
        $whatsappService = new WhatsAppService;
        if ($whatsappService->sendMcuInvitation($schedule)) {
            return redirect()->back()->with('success', 'WhatsApp undangan berhasil dikirim ke '.$schedule->nama_lengkap.'.');
        }

        $detail = $whatsappService->getLastError();

        return redirect()->back()->withErrors([
            'send' => $detail
                ? 'Gagal mengirim WhatsApp: '.$detail
                : 'Gagal mengirim WhatsApp. Periksa pengaturan di Settings.',
        ]);
    }
}
