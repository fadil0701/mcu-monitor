<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\User;
use App\Models\Schedule;
use App\Support\ParticipantEducation;
use App\Support\ValidationMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PesertaActivationController extends Controller
{
    public function showVerificationForm()
    {
        return view('auth.peserta-verifikasi');
    }

    public function verifyParticipant(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ], [
            'identifier.required' => 'NIK, NRK, atau nomor telepon wajib diisi.',
        ]);

        $identifier = $request->input('identifier');
        $participant = Participant::where('nik_ktp', $identifier)
            ->orWhere('nrk_pegawai', $identifier)
            ->orWhere('no_telp', $identifier)
            ->first();

        if (!$participant) {
            return back()->withErrors(['identifier' => 'Data peserta tidak ditemukan.']);
        }

        $user = User::where('nik_ktp', $participant->nik_ktp)
            ->orWhere('nrk_pegawai', $participant->nrk_pegawai)
            ->first();
        if ($user) {
            return back()->withErrors(['identifier' => 'Peserta ini sudah memiliki akun login. Silakan login menggunakan email terdaftar.']);
        }

        session(['aktivasi_peserta_id' => $participant->id]);

        return redirect()->route('peserta.aktivasi.register')->with('success', 'Data peserta ditemukan. Lengkapi data berikut dan buat akun login.');
    }

    public function showRegisterForm(Request $request)
    {
        $participantId = session('aktivasi_peserta_id');
        if (!$participantId) {
            return redirect()->route('peserta.aktivasi');
        }
        $participant = Participant::find($participantId);
        if (!$participant) {
            return redirect()->route('peserta.aktivasi');
        }

        return view('auth.peserta-register', compact('participant'));
    }

    public function registerAccount(Request $request)
    {
        $participantId = session('aktivasi_peserta_id');
        if (!$participantId) {
            return redirect()->route('peserta.aktivasi');
        }
        $participant = Participant::find($participantId);
        if (!$participant) {
            return redirect()->route('peserta.aktivasi');
        }

        $valid = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date|before_or_equal:today',
            'jenis_kelamin' => 'required|in:L,P',
            'nrk_pegawai' => 'required|string|max:255|unique:participants,nrk_pegawai,'.$participant->id.'|unique:users,nrk_pegawai',
            'skpd' => 'required|string|max:255',
            'ukpd' => 'required|string|max:255',
            'status_pegawai' => 'required|in:CPNS,PNS,PPPK',
            'pendidikan_terakhir' => ['required', Rule::in(ParticipantEducation::levels())],
            'no_telp' => 'required|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
        ], ValidationMessages::merge(
            ValidationMessages::participantForm(),
            ValidationMessages::passwordActivation(),
        ));

        $participant->update([
            'nama_lengkap' => $valid['nama_lengkap'],
            'tempat_lahir' => $valid['tempat_lahir'],
            'tanggal_lahir' => $valid['tanggal_lahir'],
            'jenis_kelamin' => $valid['jenis_kelamin'],
            'nrk_pegawai' => $valid['nrk_pegawai'],
            'skpd' => $valid['skpd'],
            'ukpd' => $valid['ukpd'],
            'status_pegawai' => $valid['status_pegawai'],
            'pendidikan_terakhir' => $valid['pendidikan_terakhir'],
            'no_telp' => $valid['no_telp'],
            'email' => $valid['email'],
        ]);

        $user = User::create([
            'name' => $valid['nama_lengkap'],
            'email' => $valid['email'],
            'password' => Hash::make($valid['password']),
            'role' => 'user',
            'nik_ktp' => $participant->nik_ktp,
            'nrk_pegawai' => $valid['nrk_pegawai'],
            'is_active' => true,
        ]);

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

        $request->session()->forget('aktivasi_peserta_id');

        Auth::login($user);

        return redirect('/client/dashboard')->with('success', 'Akun berhasil dibuat. Selamat datang!');
    }
}
