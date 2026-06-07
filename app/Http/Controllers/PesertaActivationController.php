<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PesertaActivationController extends Controller
{
    // Tampilkan form verifikasi peserta
    public function showVerificationForm()
    {
        return view('auth.peserta-verifikasi');
    }

    // Proses verifikasi peserta
    public function verifyParticipant(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = $request->input('identifier');
        $participant = Participant::where('nik_ktp', $identifier)
            ->orWhere('nrk_pegawai', $identifier)
            ->orWhere('no_telp', $identifier)
            ->first();

        if (!$participant) {
            return back()->withErrors(['identifier' => 'Data peserta tidak ditemukan.']);
        }

        // Cek apakah sudah punya akun user
        $user = User::where('nik_ktp', $participant->nik_ktp)
            ->orWhere('nrk_pegawai', $participant->nrk_pegawai)
            ->first();
        if ($user) {
            return back()->withErrors(['identifier' => 'Peserta ini sudah memiliki akun login. Silakan login menggunakan email terdaftar.']);
        }

        // Simpan ID peserta ke session untuk proses berikutnya
        session(['aktivasi_peserta_id' => $participant->id]);
        return redirect()->route('peserta.aktivasi.register')->with('success', 'Data peserta ditemukan. Silakan buat akun login.');
    }

    // Tampilkan form pembuatan akun login
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

    // Proses pembuatan akun login
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

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
            'nrk_pegawai' => 'required|string|max:255|unique:participants,nrk_pegawai,' . $participant->id . '|unique:users,nrk_pegawai',
        ]);

        $participant->update([
            'nrk_pegawai' => $request->nrk_pegawai,
        ]);

        $user = User::create([
            'name' => $participant->nama_lengkap,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'nik_ktp' => $participant->nik_ktp,
            'nrk_pegawai' => $request->nrk_pegawai,
            'is_active' => true,
        ]);

        Schedule::where('participant_id', $participant->id)->update([
            'nrk_pegawai' => $request->nrk_pegawai,
        ]);

        // Hapus session aktivasi
        $request->session()->forget('aktivasi_peserta_id');

        Auth::login($user);
        return redirect('/client/dashboard')->with('success', 'Akun berhasil dibuat. Selamat datang!');
    }
}

