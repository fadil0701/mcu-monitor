<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SqlFilter;
use App\Support\SqlLike;
use App\Models\McuResult;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class McuResultController extends Controller
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly WhatsAppService $whatsappService,
    ) {}

    public function index(Request $request)
    {
        $query = Participant::query()
            ->where('status_mcu', 'Sudah MCU')
            ->with([
                'schedules' => fn ($q) => $q->orderByDesc('tanggal_pemeriksaan'),
                'mcuResults' => fn ($q) => $q->orderByDesc('tanggal_pemeriksaan'),
            ]);

        if ($request->filled('search')) {
            $pattern = SqlLike::contains((string) $request->search);
            $query->where(function ($qry) use ($pattern) {
                $qry->where('nama_lengkap', 'like', $pattern)
                    ->orWhere('nik_ktp', 'like', $pattern);
            });
        }

        $this->applyStatusHasilFilter($query, $request);
        $this->applyPublikasiFilter($query, $request);

        [$period, $periodValue, $usingDefaultPeriod] = $this->resolvePeriodFilter($request);
        $this->applyPeriodFilterValues($query, $period, $periodValue);

        $participants = $query
            ->orderByDesc('tanggal_mcu_terakhir')
            ->orderBy('nama_lengkap')
            ->paginate(15)
            ->withQueryString();

        return view('admin.mcu-results.index', [
            'participants' => $participants,
            'periodFilter' => $period,
            'periodValueFilter' => $periodValue,
            'usingDefaultPeriod' => $usingDefaultPeriod,
        ]);
    }

    public function create(Request $request)
    {
        $participants = Participant::orderBy('nama_lengkap')->get();
        $participantId = $request->get('participant_id');
        $tanggalPemeriksaan = $request->get('tanggal_pemeriksaan');

        return view('admin.mcu-results.create', compact('participants', 'participantId', 'tanggalPemeriksaan'));
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
        $valid['uploaded_by'] = Auth::id();
        $valid['schedule_id'] = $valid['schedule_id'] ?? null;
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
        if ($this->emailService->sendMcuResult($mcu_result)) {
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
        if ($this->whatsappService->sendMcuResult($mcu_result)) {
            return redirect()->back()->with('success', 'WhatsApp hasil MCU berhasil dikirim ke ' . $participant->nama_lengkap . '.');
        }
        return redirect()->back()->withErrors(['send' => 'Gagal mengirim WhatsApp. Periksa pengaturan di Settings.']);
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: bool}
     */
    private function resolvePeriodFilter(Request $request): array
    {
        if (! $request->has('period')) {
            return ['bulan', Carbon::now()->format('Y-m'), true];
        }

        if (! $request->filled('period')) {
            return [null, null, false];
        }

        $period = SqlFilter::enum((string) $request->period, ['hari', 'bulan', 'tahun']);
        $value = $request->filled('period_value') ? trim((string) $request->period_value) : null;

        return [$period, $value, false];
    }

    private function applyPeriodFilterValues($query, ?string $period, ?string $value): void
    {
        if ($period === null || $value === null || $value === '') {
            return;
        }

        $query->where(function ($q) use ($period, $value) {
            match ($period) {
                'hari' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
                    ? $q->whereDate('tanggal_mcu_terakhir', $value)
                        ->orWhereHas('schedules', fn ($s) => $s->whereDate('tanggal_pemeriksaan', $value))
                        ->orWhereHas('mcuResults', fn ($r) => $r->whereDate('tanggal_pemeriksaan', $value))
                    : null,
                'bulan' => preg_match('/^\d{4}-\d{2}$/', $value) === 1
                    ? $q->where(function ($inner) use ($value) {
                        $year = (int) substr($value, 0, 4);
                        $month = (int) substr($value, 5, 2);
                        $inner->whereYear('tanggal_mcu_terakhir', $year)->whereMonth('tanggal_mcu_terakhir', $month)
                            ->orWhereHas('schedules', fn ($s) => $s->whereYear('tanggal_pemeriksaan', $year)->whereMonth('tanggal_pemeriksaan', $month))
                            ->orWhereHas('mcuResults', fn ($r) => $r->whereYear('tanggal_pemeriksaan', $year)->whereMonth('tanggal_pemeriksaan', $month));
                    })
                    : null,
                'tahun' => preg_match('/^\d{4}$/', $value) === 1
                    ? $q->whereYear('tanggal_mcu_terakhir', (int) $value)
                        ->orWhereHas('schedules', fn ($s) => $s->whereYear('tanggal_pemeriksaan', (int) $value))
                        ->orWhereHas('mcuResults', fn ($r) => $r->whereYear('tanggal_pemeriksaan', (int) $value))
                    : null,
                default => null,
            };
        });
    }

    private function applyStatusHasilFilter($query, Request $request): void
    {
        $statusHasil = SqlFilter::enum(
            $request->filled('status_hasil') ? (string) $request->status_hasil : null,
            ['uploaded', 'not_uploaded'],
        );

        if ($statusHasil === null) {
            return;
        }

        if ($statusHasil === 'uploaded') {
            $query->whereHas('mcuResults', $this->hasUploadedFileConstraint());

            return;
        }

        $query->whereDoesntHave('mcuResults', $this->hasUploadedFileConstraint());
    }

    private function applyPublikasiFilter($query, Request $request): void
    {
        $publikasi = SqlFilter::enum(
            $request->filled('publikasi') ? (string) $request->publikasi : null,
            ['ya', 'tidak'],
        );

        if ($publikasi === null) {
            return;
        }

        if ($publikasi === 'ya') {
            $query->whereHas('mcuResults', fn ($q) => $q->where('is_published', true));

            return;
        }

        $query->where(function ($q) {
            $q->whereDoesntHave('mcuResults')
                ->orWhereHas('mcuResults', fn ($r) => $r->where('is_published', false));
        });
    }

    private function hasUploadedFileConstraint(): callable
    {
        return function ($query) {
            $query->where(function ($fileQ) {
                $fileQ->where(function ($q) {
                    $q->whereNotNull('file_hasil')
                        ->where('file_hasil', '!=', '');
                })->orWhere(function ($q) {
                    $q->whereNotNull('file_hasil_files')
                        ->where('file_hasil_files', '!=', '[]')
                        ->where('file_hasil_files', '!=', 'null');
                });
            });
        };
    }
}
