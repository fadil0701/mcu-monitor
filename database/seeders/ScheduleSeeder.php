<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\Schedule;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $locations = [
            'Klinik Utama Balaikota',
        ];

        $participants = Participant::query()->get();

        foreach ($participants as $participant) {
            $numSchedules = $faker->numberBetween(0, 3);

            for ($i = 0; $i < $numSchedules; $i++) {
                // Tentukan status dengan bobot: Selesai (50%), Terjadwal (40%), Batal (10%)
                $rand = $faker->numberBetween(1, 100);
                if ($rand <= 50) {
                    $status = 'Selesai';
                    $tanggalPemeriksaan = Carbon::instance($faker->dateTimeBetween('-2 years', 'now'))->startOfDay();
                    $jamPemeriksaan = $faker->time('H:i');
                } elseif ($rand <= 90) {
                    $status = 'Terjadwal';
                    $tanggalPemeriksaan = Carbon::instance($faker->dateTimeBetween('now', '+6 months'))->startOfDay();
                    $jamPemeriksaan = $faker->time('H:i');
                } else {
                    $status = 'Batal';
                    $tanggalPemeriksaan = Carbon::instance($faker->dateTimeBetween('-1 years', '+3 months'))->startOfDay();
                    $jamPemeriksaan = $faker->time('H:i');
                }

                $emailSent = $status !== 'Terjadwal' && $faker->boolean(70);
                $waSent = $status !== 'Terjadwal' && $faker->boolean(70);

                Schedule::create([
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
                    'tanggal_pemeriksaan' => $tanggalPemeriksaan,
                    'jam_pemeriksaan' => $jamPemeriksaan,
                    'lokasi_pemeriksaan' => $faker->randomElement($locations),
                    'status' => $status,
                    'email_sent' => $emailSent,
                    'whatsapp_sent' => $waSent,
                    'email_sent_at' => $emailSent ? Carbon::now()->subDays($faker->numberBetween(0, 30)) : null,
                    'whatsapp_sent_at' => $waSent ? Carbon::now()->subDays($faker->numberBetween(0, 30)) : null,
                    'catatan' => $faker->boolean(15) ? $faker->sentence() : null,
                ]);
            }
        }
    }
}






