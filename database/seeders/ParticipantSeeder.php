<?php

namespace Database\Seeders;

use App\Models\Participant;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ParticipantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $skpdList = [
            'Dinas Kesehatan',
            'Dinas Pendidikan',
            'Dinas Perhubungan',
            'Sekretariat Daerah',
            'Bappeda',
        ];

        $ukpdList = [
            'UPT 1',
            'UPT 2',
            'UPT 3',
            'UPT 4',
            'UPT 5',
        ];

        $statusPegawaiOptions = ['CPNS', 'PNS', 'PPPK'];
        $statusMcuOptions = ['Belum MCU', 'Sudah MCU', 'Ditolak'];

        for ($i = 0; $i < 100; $i++) {
            $tanggalLahir = Carbon::instance($faker->dateTimeBetween('-60 years', '-20 years'))->startOfDay();
            $jenisKelamin = $faker->randomElement(['L', 'P']);
            $statusPegawai = $faker->randomElement($statusPegawaiOptions);
            $statusMcu = $faker->randomElement($statusMcuOptions);

            $tanggalMcuTerakhir = null;
            if ($statusMcu !== 'Belum MCU') {
                $tanggalMcuTerakhir = Carbon::instance($faker->dateTimeBetween('-5 years', 'now'))->startOfDay();
            }

            Participant::create([
                'nik_ktp' => $faker->unique()->numerify('################'),
                'nrk_pegawai' => $faker->unique()->numerify('NRK########'),
                'nama_lengkap' => $faker->name($jenisKelamin === 'L' ? 'male' : 'female'),
                'tempat_lahir' => $faker->city(),
                'tanggal_lahir' => $tanggalLahir,
                'jenis_kelamin' => $jenisKelamin,
                'skpd' => $faker->randomElement($skpdList),
                'ukpd' => $faker->randomElement($ukpdList),
                'no_telp' => $faker->unique()->numerify('08##########'),
                'email' => $faker->unique()->safeEmail(),
                'status_pegawai' => $statusPegawai,
                'status_mcu' => $statusMcu,
                'tanggal_mcu_terakhir' => $tanggalMcuTerakhir,
                'catatan' => $faker->boolean(20) ? $faker->sentence() : null,
            ]);
        }
    }
}



