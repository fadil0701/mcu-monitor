<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

use App\Models\Participant;
use App\Models\Schedule;
use App\Models\McuResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\NewScheduleRequest;
use App\Notifications\NewRegistrationNotification;

class ClientController extends Controller
{
	public function dashboard()
	{
		$user = Auth::user();
		$participant = null;
		$schedules = collect();
		$mcuResults = collect();
		// Total antrian harian yang aktif (belum selesai/batal/ditolak) - cached for 5 minutes
		$todayQueueTotal = cache()->remember('today_queue_total_' . now()->toDateString(), 300, function () {
			return Schedule::whereDate('tanggal_pemeriksaan', now()->toDateString())
				->where('status', 'Terjadwal')
				->count();
		});

		if ($user->nik_ktp) {
			$participant = Participant::where('nik_ktp', $user->nik_ktp)->first();
			
			if ($participant) {
				$schedules = $participant->schedules()->orderBy('tanggal_pemeriksaan', 'desc')->get();
				$mcuResults = $participant->mcuResults()
					->where('is_published', true)
					->orderBy('tanggal_pemeriksaan', 'desc')
					->get();
			}
		}

		return view('client.dashboard', compact('participant', 'schedules', 'mcuResults', 'todayQueueTotal'));
	}

	public function profile()
	{
		$user = Auth::user();
		$participant = null;

		if ($user->nik_ktp) {
			$participant = Participant::where('nik_ktp', $user->nik_ktp)->first();
		}

		return view('client.profile', compact('participant', 'user'));
	}

	public function updateProfile(Request $request)
	{
		$user = Auth::user();

		if (!$user->nik_ktp) {
			return back()->with('error', 'Akun Anda belum terhubung dengan data peserta.');
		}

		$participant = Participant::where('nik_ktp', $user->nik_ktp)->firstOrFail();

		$valid = $request->validate([
			'nrk_pegawai' => 'required|string|max:255|unique:participants,nrk_pegawai,' . $participant->id . '|unique:users,nrk_pegawai,' . $user->id,
			'nama_lengkap' => 'required|string|max:255',
			'tempat_lahir' => 'required|string|max:255',
			'tanggal_lahir' => 'required|date|before_or_equal:today',
			'jenis_kelamin' => 'required|in:L,P',
			'skpd' => 'required|string|max:255',
			'ukpd' => 'required|string|max:255',
			'status_pegawai' => 'required|in:CPNS,PNS,PPPK',
			'no_telp' => 'required|string|max:20',
			'email' => 'required|email|max:255|unique:users,email,' . $user->id,
		]);

		$participant->update($valid);

		Schedule::where('participant_id', $participant->id)->update([
			'nrk_pegawai' => $valid['nrk_pegawai'],
			'nama_lengkap' => $valid['nama_lengkap'],
			'tanggal_lahir' => $valid['tanggal_lahir'],
			'jenis_kelamin' => $valid['jenis_kelamin'],
			'skpd' => $valid['skpd'],
			'ukpd' => $valid['ukpd'],
			'no_telp' => $valid['no_telp'],
			'email' => $valid['email'],
		]);

		$user->update([
			'name' => $valid['nama_lengkap'],
			'email' => $valid['email'],
			'nrk_pegawai' => $valid['nrk_pegawai'],
		]);

		return redirect()->route('client.profile')->with('success', 'Data profile berhasil diperbarui.');
	}

	public function schedules()
	{
		$user = Auth::user();
		$schedules = collect();

		if ($user->nik_ktp) {
			$participant = Participant::where('nik_ktp', $user->nik_ktp)->first();
			
			if ($participant) {
				$schedules = $participant->schedules()->orderBy('tanggal_pemeriksaan', 'desc')->paginate(10);
			}
		}

		return view('client.schedules', compact('schedules'));
	}

	public function confirmAttendance($id)
	{
		$user = Auth::user();
		$schedule = Schedule::findOrFail($id);
		if (!($user->nik_ktp && $schedule->nik_ktp === $user->nik_ktp)) {
			abort(403);
		}
		$schedule->update([
			'participant_confirmed' => true,
			'participant_confirmed_at' => now(),
		]);
		return back()->with('success', 'Kehadiran berhasil dikonfirmasi.');
	}

	public function requestReschedule(Request $request, $id)
	{
		$user = Auth::user();
		$schedule = Schedule::findOrFail($id);
		if (!($user->nik_ktp && $schedule->nik_ktp === $user->nik_ktp)) {
			abort(403);
		}
		$request->validate([
			'new_date' => ['required', 'date', 'after_or_equal:' . now()->startOfDay()->toDateString()],
			'new_time' => ['required', 'date_format:H:i'],
			'reason' => ['required', 'string', 'max:1000'],
		]);

		$updateResult = $schedule->update([
			'reschedule_requested' => true,
			'reschedule_new_date' => $request->new_date,
			'reschedule_new_time' => $request->new_time,
			'reschedule_reason' => $request->reason,
			'reschedule_requested_at' => now(),
		]);

		// \Log::info('Reschedule request update', [
		// 	'schedule_id' => $schedule->id,
		// 	'update_result' => $updateResult,
		// 	'attributes' => $schedule->getAttributes(),
		// ]);

		// Notify admins
		User::query()->whereIn('role', ['admin','super_admin'])->get()->each(function (User $admin) use ($schedule) {
			$admin->notify(new NewRegistrationNotification('ulang', [
				'type' => 'reschedule_request',
				'participant_name' => $schedule->nama_lengkap,
				'nik_ktp' => $schedule->nik_ktp,
				'new_date' => $schedule->reschedule_new_date,
				'new_time' => $schedule->reschedule_new_time,
				'reason' => $schedule->reschedule_reason,
			]));
		});

		return back()->with('success', 'Permintaan reschedule telah dikirim ke admin.');
	}

    public function cancelSchedule(Request $request, $id)
    {
        $user = Auth::user();
        $schedule = Schedule::findOrFail($id);
        if (!($user->nik_ktp && $schedule->nik_ktp === $user->nik_ktp)) {
            abort(403);
        }

        $request->validate([
            'cancel_reason' => ['required', 'string', 'max:1000'],
        ]);

        $schedule->update([
            'status' => 'Batal',
            'catatan' => trim(($schedule->catatan ? $schedule->catatan."\n" : '') . 'Pembatalan oleh peserta: ' . $request->cancel_reason),
        ]);

        // Notify admins about cancellation
        User::query()->whereIn('role', ['admin','super_admin'])->get()->each(function (User $admin) use ($schedule, $request) {
            $admin->notify(new NewRegistrationNotification('batal', [
                'type' => 'cancellation',
                'participant_name' => $schedule->nama_lengkap,
                'nik_ktp' => $schedule->nik_ktp,
                'tanggal_pemeriksaan' => $schedule->tanggal_pemeriksaan?->format('Y-m-d'),
                'jam_pemeriksaan' => $schedule->jam_pemeriksaan?->format('H:i'),
                'reason' => $request->cancel_reason,
            ]));
        });

        return back()->with('success', 'Jadwal MCU berhasil dibatalkan.');
    }

	public function results()
	{
		$user = Auth::user();
		$mcuResults = collect();

		if ($user->nik_ktp) {
			$participant = Participant::where('nik_ktp', $user->nik_ktp)->first();
			
			if ($participant) {
				$mcuResults = $participant->mcuResults()
					->where('is_published', true)
					->orderBy('tanggal_pemeriksaan', 'desc')
					->paginate(10);
			}
		}

		return view('client.results', compact('mcuResults'));
	}

	public function downloadResult($id)
	{
		$user = Auth::user();
		$mcuResult = McuResult::findOrFail($id);

		// Check if user has access to this result and if it's published
		if ($user->nik_ktp && $mcuResult->participant->nik_ktp === $user->nik_ktp && $mcuResult->is_published) {
			if ($mcuResult->hasFile()) {
				$mcuResult->markAsDownloaded();
				// Prefer first file from multi if available
				$path = null;
				if (is_array($mcuResult->file_hasil_files) && !empty($mcuResult->file_hasil_files)) {
					$path = $mcuResult->file_hasil_files[0];
				} elseif (!empty($mcuResult->file_hasil)) {
					$path = $mcuResult->file_hasil;
				}
				if ($path && Storage::disk('public')->exists($path)) {
					return response()->download(Storage::disk('public')->path($path));
				}
			}
		}

		abort(404);
	}

	public function downloadAllResult($id)
	{
		$user = Auth::user();
		$mcuResult = McuResult::findOrFail($id);

		// Authorization: ensure result belongs to logged-in participant and is published
		if (!($user->nik_ktp && $mcuResult->participant->nik_ktp === $user->nik_ktp && $mcuResult->is_published)) {
			abort(403);
		}

		$files = $mcuResult->file_hasil_files ?? [];
		if (empty($files) && !empty($mcuResult->file_hasil)) {
			$files = [$mcuResult->file_hasil];
		}

		$existingFiles = [];
		foreach ($files as $relativePath) {
			if (Storage::disk('public')->exists($relativePath)) {
				$existingFiles[] = $relativePath;
			}
		}

		if (empty($existingFiles)) {
			return back()->withErrors(['download' => 'Tidak ada file yang dapat diunduh.']);
		}

		$zip = new ZipArchive();
		$zipFileName = 'mcu-result-' . $mcuResult->id . '.zip';
		$tmpPath = storage_path('app/tmp');
		if (!is_dir($tmpPath)) {
			mkdir($tmpPath, 0775, true);
		}
		$zipPath = $tmpPath . DIRECTORY_SEPARATOR . $zipFileName;

		if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			return back()->withErrors(['download' => 'Gagal membuat arsip ZIP.']);
		}

		foreach ($existingFiles as $relativePath) {
			$fullPath = Storage::disk('public')->path($relativePath);
			$zip->addFile($fullPath, basename($fullPath));
		}

		$zip->close();

		return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
	}

	public function requestScheduleForm()
	{
		$user = Auth::user();
		$participant = Participant::where('nik_ktp', $user->nik_ktp)->first();
		if (!$participant) {
			abort(404);
		}

		$eligible = true;
		$reason = null;
		if ($participant->tanggal_mcu_terakhir) {
			$eligible = Carbon::parse($participant->tanggal_mcu_terakhir)->lte(Carbon::now()->subYears(config('mcu.interval_years', 3)));
			if (!$eligible) {
				$reason = 'Anda belum memenuhi syarat pendaftaran ulang (belum ' . config('mcu.interval_years', 3) . ' tahun). Silakan hubungi admin jika ada kondisi khusus.';
			}
		}

		return view('client.request-schedule', compact('participant', 'eligible', 'reason'));
	}

	public function storeScheduleRequest(Request $request)
	{
		$user = Auth::user();
		$participant = Participant::where('nik_ktp', $user->nik_ktp)->firstOrFail();

		// Eligibility check: must be >= N years since last MCU unless admin overrides (not here)
		if ($participant->tanggal_mcu_terakhir && Carbon::parse($participant->tanggal_mcu_terakhir)->gt(Carbon::now()->subYears(config('mcu.interval_years', 3)))) {
			return back()->withErrors(['request' => 'Anda belum memenuhi syarat pendaftaran ulang. Hubungi admin untuk pengecualian.']);
		}

        $request->validate([
            'tanggal_pemeriksaan' => ['required', 'date', 'after_or_equal:' . now()->startOfDay()->toDateString()],
			'jam_pemeriksaan' => ['required', 'date_format:H:i'],
			'lokasi_pemeriksaan' => ['required', 'string', 'max:255'],
			'catatan' => ['nullable', 'string', 'max:1000'],
		]);

		$schedule = Schedule::create([
			'participant_id' => $participant->id,
			'nik_ktp' => $participant->nik_ktp,
			'nrk_pegawai' => $participant->nrk_pegawai,
			'nama_lengkap' => $participant->nama_lengkap,
			'tanggal_lahir' => $participant->tanggal_lahir,
			'jenis_kelamin' => $participant->jenis_kelamin,
			'skpd' => $participant->skpd,
			'ukpd' => $participant->ukpd,
			'no_telp' => $participant->no_telp,
			'email' => $participant->email,
			'tanggal_pemeriksaan' => $request->tanggal_pemeriksaan,
			'jam_pemeriksaan' => $request->jam_pemeriksaan,
			'lokasi_pemeriksaan' => $request->lokasi_pemeriksaan,
			'status' => 'Terjadwal',
			'catatan' => $request->catatan,
		]);

        // Assign queue number for the date
        $max = Schedule::whereDate('tanggal_pemeriksaan', $schedule->tanggal_pemeriksaan)->max('queue_number');
        $schedule->update(['queue_number' => ((int) $max) + 1]);

		// Notify admins about repeat registration (schedule request)
		User::query()->whereIn('role', ['admin','super_admin'])->get()->each(function (User $admin) use ($participant, $schedule) {
			$admin->notify(new NewRegistrationNotification('ulang', [
				'participant_name' => $participant->nama_lengkap,
				'nik_ktp' => $participant->nik_ktp,
				'nrk_pegawai' => $participant->nrk_pegawai,
				'tanggal_pemeriksaan' => $schedule->tanggal_pemeriksaan?->format('Y-m-d'),
				'jam_pemeriksaan' => $schedule->jam_pemeriksaan?->format('H:i'),
				'lokasi_pemeriksaan' => $schedule->lokasi_pemeriksaan,
			]));
		});

		return redirect()->route('client.schedules')->with('success', 'Permintaan jadwal MCU berhasil dibuat. Menunggu konfirmasi admin.');
	}
}
