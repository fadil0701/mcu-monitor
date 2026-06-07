<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Participant;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Carbon\Carbon;
use App\Notifications\NewRegistrationNotification;

class RegisteredUserController extends Controller
{
	/**
	 * Display the registration view.
	 */
	public function create(): View
	{
		return view('auth.register');
	}

	/**
	 * Handle an incoming registration request.
	 */
	public function store(Request $request): RedirectResponse
	{
		$request->validate([
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
			'password' => ['required', 'confirmed', Rules\Password::defaults()],
			'nik_ktp' => ['nullable', 'string', 'size:16', 'unique:participants,nik_ktp'],
			'nrk_pegawai' => ['nullable', 'string', 'max:255', 'unique:participants,nrk_pegawai'],
			'tempat_lahir' => ['nullable', 'string', 'max:255'],
			'tanggal_lahir' => ['nullable', 'date'],
			'jenis_kelamin' => ['nullable', 'in:L,P'],
			'status_pegawai' => ['required', 'in:CPNS,PNS,PPPK'],
			'skpd' => ['nullable', 'string', 'max:255'],
			'ukpd' => ['nullable', 'string', 'max:255'],
			'no_telp' => ['nullable', 'string', 'max:20'],
			'email_personal' => ['nullable', 'email', 'max:255'],
		]);

		// Check if employee status is valid for MCU
		if (!in_array($request->status_pegawai, ['CPNS', 'PNS', 'PPPK'])) {
			return back()->withErrors(['status_pegawai' => 'Status pegawai tidak valid untuk pendaftaran MCU.']);
		}

		// Check if NIK KTP already exists in participants table
		if ($request->nik_ktp) {
			$existingParticipant = Participant::where('nik_ktp', $request->nik_ktp)->first();
			if ($existingParticipant) {
				// Check if they had MCU in the last 3 years
				if ($existingParticipant->tanggal_mcu_terakhir) {
					$threeYearsAgo = Carbon::now()->subYears(3);
					if ($existingParticipant->tanggal_mcu_terakhir->gt($threeYearsAgo)) {
						return back()->withErrors(['nik_ktp' => 'NIK KTP ini sudah melakukan MCU dalam 3 tahun terakhir.']);
					}
				}
			}
		}

		// Create user
		$user = User::create([
			'name' => $request->name,
			'email' => $request->email,
			'password' => Hash::make($request->password),
			'role' => 'user',
			'nik_ktp' => $request->nik_ktp,
			'nrk_pegawai' => $request->nrk_pegawai,
			'is_active' => true,
		]);

		// Create participant record if NIK KTP is provided
		if ($request->nik_ktp) {
			$participant = Participant::create([
				'nik_ktp' => $request->nik_ktp,
				'nrk_pegawai' => $request->nrk_pegawai,
				'nama_lengkap' => $request->name,
				'tempat_lahir' => $request->tempat_lahir,
				'tanggal_lahir' => $request->tanggal_lahir,
				'jenis_kelamin' => $request->jenis_kelamin,
				'skpd' => $request->skpd,
				'ukpd' => $request->ukpd,
				'no_telp' => $request->no_telp,
				'email' => $request->email_personal ?: $request->email,
				'status_pegawai' => $request->status_pegawai,
				'status_mcu' => 'Belum MCU',
				'catatan' => 'Pendaftaran melalui sistem online',
			]);
		}

		event(new Registered($user));

		// Notify admins about new registration
		User::query()->whereIn('role', ['admin','super_admin'])->get()->each(function (User $admin) use ($user) {
			$admin->notify(new NewRegistrationNotification('baru', [
				'user_name' => $user->name,
				'user_email' => $user->email,
				'nik_ktp' => $user->nik_ktp,
				'nrk_pegawai' => $user->nrk_pegawai,
			]));
		});

		Auth::login($user);

		return redirect('/client/dashboard')->with('success', 'Pendaftaran MCU berhasil! Selamat datang di sistem monitoring MCU PPKP DKI Jakarta.');
	}
}
