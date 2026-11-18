<?php

namespace Database\Seeders;

use App\Models\JadwalKesiapsiagaan;
use App\Models\KomunitasRelawan;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KomunitasDefaultSeeder extends Seeder
{
    /**
     * FR-03: komunitas default di wilayah pilot, sudah terverifikasi, wajib ganti sandi saat login pertama.
     * Nama dan nomor di bawah adalah contoh; ganti dengan data komunitas yang sudah memberi izin.
     */
    public function run(): void
    {
        // Posko contoh diletakkan di titik pusat kelurahan versi BMKG (lihat WilayahSeeder).
        // Ganti dengan koordinat posko sebenarnya dari komunitas.
        $komunitas = [
            'Tirto' => ['slug' => 'tirto'],
            'Pasirkratonkramat' => ['slug' => 'pasirkratonkramat'],
            'Degayu' => ['slug' => 'degayu'],
        ];

        // Contoh pola 3 shift. Shift bersifat dinamis: komunitas bebas mengubah jumlah, jam, dan nama shift.
        $shift = [
            ['nama' => 'Pagi', 'mulai' => '06:00', 'selesai' => '14:00'],
            ['nama' => 'Siang', 'mulai' => '14:00', 'selesai' => '22:00'],
            // jam_selesai < jam_mulai: shift melewati tengah malam (berlaku sampai 06:00 hari berikutnya).
            ['nama' => 'Malam', 'mulai' => '22:00', 'selesai' => '06:00'],
        ];

        $nomorUrut = 1;

        DB::transaction(function () use ($komunitas, $shift, &$nomorUrut) {
            foreach ($komunitas as $kelurahan => $info) {
                $wilayah = Wilayah::where('nama_kelurahan', $kelurahan)->firstOrFail();

                $user = User::updateOrCreate(
                    ['email' => "komunitas.{$info['slug']}@alir.test"],
                    [
                        'name' => "Komunitas Relawan {$kelurahan}",
                        'password' => env('KOMUNITAS_DEFAULT_PASSWORD', 'password'),
                        'role' => User::ROLE_KOMUNITAS,
                        'is_active' => true,
                        'wajib_ganti_sandi' => true,
                        'email_verified_at' => now(),
                    ],
                );

                $profil = KomunitasRelawan::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'wilayah_id' => $wilayah->id,
                        'nama' => "Komunitas Relawan {$kelurahan}",
                        'nama_pic' => "PIC Komunitas {$kelurahan}",
                        'nomor_whatsapp_pic' => sprintf('62812000000%02d', $nomorUrut++),
                        'total_anggota' => 20,
                        'kapasitas_max' => 15,
                        'jumlah_perahu' => 2,
                        'status_pendaftaran' => 'disetujui',
                        'izin_tampil_publik' => true, // FR-03: komunitas default sudah memberi izin
                        'menyetujui_syarat_pada' => now(),
                        'diverifikasi_pada' => now(),
                    ],
                );

                $profil->posko()->updateOrCreate(
                    ['nama' => "Posko Utama {$kelurahan}"],
                    [
                        'alamat' => "Kelurahan {$kelurahan}, Kota Pekalongan",
                        'latitude' => $wilayah->latitude,
                        'longitude' => $wilayah->longitude,
                        'radius_m' => 1500,
                    ],
                );

                foreach (['Evakuasi air' => 8, 'Pertolongan pertama (P3K)' => 5, 'Dapur umum' => 6] as $nama => $jumlah) {
                    $profil->keterampilan()->updateOrCreate(
                        ['nama_keterampilan' => $nama],
                        ['jumlah_anggota_terampil' => $jumlah],
                    );
                }

                $profil->jadwalKesiapsiagaan()->delete();
                foreach (JadwalKesiapsiagaan::HARI as $hari) {
                    foreach ($shift as $s) {
                        $profil->jadwalKesiapsiagaan()->create([
                            'hari' => $hari,
                            'nama_shift' => $s['nama'],
                            'jam_mulai' => $s['mulai'],
                            'jam_selesai' => $s['selesai'],
                            'status_ketersediaan' => 'tersedia',
                            'nama_pic' => "PIC Shift {$s['nama']} {$kelurahan}",
                            'nomor_whatsapp_pic' => sprintf('62813000000%02d', $nomorUrut++ % 100),
                        ]);
                    }
                }
            }
        });
    }
}
