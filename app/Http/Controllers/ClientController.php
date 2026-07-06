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
use App\Support\ScheduleExaminationTime;
use App\Support\ScheduleParticipantNotifier;
use App\Rules\BookableExaminationDate;
use App\Support\McuDailyQuota;
use App\Support\ParticipantMcuScheduleEligibility;
use App\Notifications\NewRegistrationNotification;
use App\Support\ParticipantEducation;
use App\Support\UserRole;
use App\Support\ValidationMessages;
use Illuminate\Validation\Rule;

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
			'pendidikan_terakhir' => ['required', Rule::in(ParticipantEducation::levels())],
			'no_telp' => 'required|string|max:20',
			'email' => 'required|email|max:255|unique:users,email,' . $user->id,
		], ValidationMessages::participantForm());

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
		User::query()->whereIn('role', UserRole::notifiableStaffRoles())->get()->each(function (User $admin) use ($schedule) {
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
        User::query()->whereIn('role', UserRole::notifiableStaffRoles())->get()->each(function (User $admin) use ($schedule, $request) {
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

		$eligibility = new ParticipantMcuScheduleEligibility($participant);
		$eligible = $eligibility->canRequest();
		$reason = $eligibility->blockingReason();
		$infoNotes = $eligibility->infoNotes();
		$hasCkgScreening = $eligibility->hasCkgScreening();
		$requiresAdminConfirmation = $eligibility->requiresAdminConfirmation();
		$dailyQuota = McuDailyQuota::limit();

		return view('client.request-schedule', compact(
			'participant',
			'eligible',
			'reason',
			'infoNotes',
			'hasCkgScreening',
			'requiresAdminConfirmation',
			'dailyQuota',
		));
	}

	public function scheduleQuota(Request $request)
	{
		$user = Auth::user();
		Participant::where('nik_ktp', $user->nik_ktp)->firstOrFail();

		$valid = $request->validate([
			'date' => ['required', 'date', 'after_or_equal:'.now()->startOfDay()->toDateString()],
		]);

		return response()->json(McuDailyQuota::snapshot($valid['date']));
	}

	public function scheduleQuotaMonth(Request $request)
	{
		$user = Auth::user();
		Participant::where('nik_ktp', $user->nik_ktp)->firstOrFail();

		$valid = $request->validate([
			'year' => ['required', 'integer', 'min:'.now()->year, 'max:'.(now()->year + 2)],
			'month' => ['required', 'integer', 'min:1', 'max:12'],
		]);

		$requested = \Carbon\Carbon::createFromDate((int) $valid['year'], (int) $valid['month'], 1)->startOfMonth();
		$earliest = now()->startOfMonth();

		if ($requested->lt($earliest)) {
			abort(422, 'Bulan tidak valid.');
		}

		return response()->json(McuDailyQuota::monthCalendar((int) $valid['year'], (int) $valid['month']));
	}

	public function storeScheduleRequest(Request $request)
	{
		$user = Auth::user();
		$participant = Participant::where('nik_ktp', $user->nik_ktp)->firstOrFail();

		$eligibility = new ParticipantMcuScheduleEligibility($participant);
		if (! $eligibility->canRequest()) {
			return back()->withErrors(['request' => $eligibility->blockingReason()]);
		}

        $request->validate([
            'tanggal_pemeriksaan' => [
                'required',
                'date',
                'after_or_equal:'.now()->startOfDay()->toDateString(),
                new BookableExaminationDate,
            ],
			'jam_pemeriksaan' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! ScheduleExaminationTime::isAllowed((string) $value)) {
                        $fail(ScheduleExaminationTime::allowedRangeMessage());
                    }
                },
            ],
			'catatan' => ['nullable', 'string', 'max:1000'],
		]);

        $lokasiPemeriksaan = ScheduleExaminationTime::defaultLocation();

		if (! McuDailyQuota::isAvailable($request->tanggal_pemeriksaan, $lokasiPemeriksaan)) {
			$limit = McuDailyQuota::limit();
			$snapshot = McuDailyQuota::snapshot($request->tanggal_pemeriksaan, $lokasiPemeriksaan);

			if (! $snapshot['bookable']) {
				return back()
					->withInput()
					->withErrors([
						'tanggal_pemeriksaan' => $snapshot['bookable_reason'],
					]);
			}

			return back()
				->withInput()
				->withErrors([
					'tanggal_pemeriksaan' => 'Kuota pemeriksaan pada tanggal tersebut sudah penuh (maksimal '.$limit.' peserta per hari). Pilih tanggal lain.',
				]);
		}

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
			'lokasi_pemeriksaan' => $lokasiPemeriksaan,
			'status' => $eligibility->requiresAdminConfirmation()
				? \App\Support\ScheduleStatuses::PENDING_ADMIN
				: 'Terjadwal',
			'catatan' => $request->catatan,
		]);

		if ($schedule->isConfirmedSchedule()) {
			$schedule->update([
				'queue_number' => Schedule::getNextQueueNumber(
					$schedule->tanggal_pemeriksaan->format('Y-m-d'),
					$lokasiPemeriksaan,
				),
			]);

			ScheduleParticipantNotifier::notify($schedule->fresh(), 'schedule_confirmed');
		} else {
			ScheduleParticipantNotifier::notify($schedule, 'schedule_pending');
		}

		$adminNotificationType = $schedule->isPendingAdminConfirmation() ? 'menunggu_konfirmasi' : 'ulang';

		// Notify admins about schedule request
		User::query()->whereIn('role', UserRole::notifiableStaffRoles())->get()->each(function (User $admin) use ($participant, $schedule, $adminNotificationType) {
			$admin->notify(new NewRegistrationNotification($adminNotificationType, [
				'participant_name' => $participant->nama_lengkap,
				'nik_ktp' => $participant->nik_ktp,
				'nrk_pegawai' => $participant->nrk_pegawai,
				'tanggal_pemeriksaan' => $schedule->tanggal_pemeriksaan?->format('Y-m-d'),
				'jam_pemeriksaan' => $schedule->jam_pemeriksaan?->format('H:i'),
				'lokasi_pemeriksaan' => $schedule->lokasi_pemeriksaan,
			]));
		});

		$successMessage = $schedule->isPendingAdminConfirmation()
			? 'Permintaan jadwal MCU berhasil diajukan. Menunggu konfirmasi admin karena Anda belum melakukan CKG di tahun berjalan.'
			: 'Jadwal MCU Anda telah dikonfirmasi. Silakan cek detail jadwal dan nomor antrian.';

		return redirect()->route('client.schedules')->with('success', $successMessage);
	}
}
