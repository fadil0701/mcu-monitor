<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\NewRegistrationNotification;
use App\Support\ParticipantEducation;
use App\Support\UserRole;
use App\Support\ValidationMessages;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

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
            'nik_ktp' => ['nullable', 'string', 'size:16', 'unique:participants,nik_ktp', 'unique:users,nik_ktp'],
            'nrk_pegawai' => ['nullable', 'string', 'max:255', 'unique:participants,nrk_pegawai', 'unique:users,nrk_pegawai'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'status_pegawai' => ['required', 'in:CPNS,PNS,PPPK'],
            'pendidikan_terakhir' => ['required', Rule::in(ParticipantEducation::levels())],
            'skpd' => ['nullable', 'string', 'max:255'],
            'ukpd' => ['nullable', 'string', 'max:255'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'status_pernikahan' => ['nullable', Rule::in(['Belum Menikah', 'Menikah', 'Cerai Mati', 'Cerai Hidup'])],
            'alamat_domisili' => ['nullable', 'string', 'max:1000'],
            'email_personal' => ['nullable', 'email', 'max:255'],
        ], ValidationMessages::registration());

        // Check if employee status is valid for MCU
        if (! in_array($request->status_pegawai, ['CPNS', 'PNS', 'PPPK'])) {
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

        // Create user and participant atomically
        try {
            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'user',
                    'nik_ktp' => $request->nik_ktp,
                    'nrk_pegawai' => $request->nrk_pegawai,
                    'is_active' => true,
                ]);

                if ($request->nik_ktp) {
                    Participant::create([
                        'nik_ktp' => $request->nik_ktp,
                        'nrk_pegawai' => $request->nrk_pegawai,
                        'nama_lengkap' => $request->name,
                        'tempat_lahir' => $request->tempat_lahir,
                        'tanggal_lahir' => $request->tanggal_lahir,
                        'jenis_kelamin' => $request->jenis_kelamin,
                        'skpd' => $request->skpd,
                        'ukpd' => $request->ukpd,
                        'no_telp' => $request->no_telp,
                        'alamat_domisili' => $request->alamat_domisili,
                        'status_pernikahan' => $request->status_pernikahan,
                        'email' => $request->email_personal ?: $request->email,
                        'status_pegawai' => $request->status_pegawai,
                        'pendidikan_terakhir' => $request->pendidikan_terakhir,
                        'status_mcu' => 'Belum MCU',
                        'catatan' => 'Pendaftaran melalui sistem online',
                    ]);
                }

                return $user;
            });
        } catch (UniqueConstraintViolationException $e) {
            $column = $this->resolveDuplicateColumn($e->getMessage());

            return back()->withErrors([
                $column => match ($column) {
                    'nik_ktp' => 'NIK KTP sudah terdaftar di sistem. Gunakan NIK lain atau hubungi admin.',
                    'nrk_pegawai' => 'NRK Pegawai sudah terdaftar di sistem. Gunakan NRK lain atau hubungi admin.',
                    'email' => 'Email sudah terdaftar di sistem. Gunakan email lain atau login jika sudah punya akun.',
                    default => 'Data sudah terdaftar di sistem. Periksa kembali input Anda.',
                },
            ])->withInput($request->only(['name', 'nik_ktp', 'nrk_pegawai', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'status_pegawai', 'skpd', 'ukpd', 'pendidikan_terakhir', 'no_telp', 'email_personal', 'status_pernikahan', 'alamat_domisili']));
        }

        event(new Registered($user));

        // Notify admins about new registration
        User::query()->whereIn('role', UserRole::notifiableStaffRoles())->get()->each(function (User $admin) use ($user) {
            $admin->notify(new NewRegistrationNotification('baru', [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'nik_ktp' => $user->nik_ktp,
                'nrk_pegawai' => $user->nrk_pegawai,
            ]));
        });

        Auth::login($user);

        return redirect()->route('client.dashboard')->with('success', 'Pendaftaran MCU berhasil! Selamat datang di sistem monitoring MCU PPKP DKI Jakarta.');
    }

    private function resolveDuplicateColumn(string $message): string
    {
        if (str_contains($message, 'nik_ktp')) {
            return 'nik_ktp';
        }
        if (str_contains($message, 'nrk_pegawai')) {
            return 'nrk_pegawai';
        }
        if (str_contains($message, 'email')) {
            return 'email';
        }

        return 'general';
    }
}
