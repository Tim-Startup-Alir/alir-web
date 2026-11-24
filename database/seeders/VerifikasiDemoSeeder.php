<?php

namespace Database\Seeders;

use App\Models\FotoLaporan;
use App\Models\LaporanBanjir;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class VerifikasiDemoSeeder extends Seeder
{
    /**
     * 4 data laporan dummy untuk uji coba halaman Verifikasi Laporan (PBI-010, 011, 012).
     */
    public function run(): void
    {
        if (Wilayah::count() === 0) {
            $this->call(WilayahSeeder::class);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists('demo-foto/banjir-demo.jpg')) {
            // 1x1 JPEG valid minimal agar preview foto tidak 404 saat uji coba
            $disk->put('demo-foto/banjir-demo.jpg', base64_decode(
                '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAABP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL8A/9k=',
                true
            ));
        }

        $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
        $pasir = Wilayah::where('nama_kelurahan', 'Pasirkratonkramat')->firstOrFail();
        $degayu = Wilayah::where('nama_kelurahan', 'Degayu')->firstOrFail();

        $daftar = [
            [
                'kode_pelacakan' => 'ALR-DM1043',
                'wilayah_id' => $tirto->id,
                'nama_pelapor' => 'Budi Santoso',
                'alamat_singkat' => 'Jl. Tirto Raya Gg. 3 RT 02',
                'latitude' => -6.8928121,
                'longitude' => 109.6515333,
                'sumber_lokasi' => 'gps',
                'severity' => 'berat',
                'jumlah_warga' => 45,
                'status' => 'menunggu_verifikasi',
                'tingkat_kepercayaan' => 'sedang',
                'catatan' => 'Air setinggi lutut orang dewasa, jalan gang tertutup dan butuh perahu.',
                'nomor_wa_pelapor' => '085712344432',
                'setuju_dihubungi' => true,
                'created_at' => now()->subHours(2),
            ],
            [
                'kode_pelacakan' => 'ALR-DM1044',
                'wilayah_id' => $tirto->id,
                'nama_pelapor' => null, // Anonim
                'alamat_singkat' => 'Simpang Jl. Tirto Gg. 4 (dekat pos ronda)',
                'latitude' => -6.8921500, // ~85 meter dari ALR-DM1043 untuk uji Duplikat
                'longitude' => 109.6519000,
                'sumber_lokasi' => 'pin',
                'severity' => 'sedang',
                'jumlah_warga' => 30,
                'status' => 'menunggu_verifikasi',
                'tingkat_kepercayaan' => 'sedang',
                'catatan' => 'Genangan rob mulai masuk ke rumah warga.',
                'nomor_wa_pelapor' => '085298761122',
                'setuju_dihubungi' => true,
                'created_at' => now()->subHour(),
            ],
            [
                'kode_pelacakan' => 'ALR-DM1045',
                'wilayah_id' => $pasir->id,
                'nama_pelapor' => 'Siti Aminah',
                'alamat_singkat' => 'Jl. Pasirkratonkramat No. 18',
                'latitude' => -6.8839083,
                'longitude' => 109.6567382,
                'sumber_lokasi' => 'gps',
                'severity' => 'ringan',
                'jumlah_warga' => 12,
                'status' => 'menunggu_verifikasi',
                'tingkat_kepercayaan' => 'rendah',
                'catatan' => 'Air menggenang di halaman depan sekitar 15 cm.',
                'nomor_wa_pelapor' => '081345678901',
                'setuju_dihubungi' => false,
                'created_at' => now()->subHours(3),
            ],
            [
                'kode_pelacakan' => 'ALR-DM1046',
                'wilayah_id' => $degayu->id,
                'nama_pelapor' => 'Ahmad Fauzi',
                'alamat_singkat' => 'Pesisir Degayu Blok B RT 05',
                'latitude' => -6.8770370,
                'longitude' => 109.7031140,
                'sumber_lokasi' => 'gps',
                'severity' => 'berat',
                'jumlah_warga' => 60,
                'status' => 'menunggu_verifikasi',
                'tingkat_kepercayaan' => 'rendah',
                'catatan' => 'Tanggul meluap, beberapa lansia perlu evakuasi segera.',
                'nomor_wa_pelapor' => '087822334455',
                'setuju_dihubungi' => true,
                'created_at' => now()->subMinutes(30),
            ],
        ];

        foreach ($daftar as $data) {
            $laporan = LaporanBanjir::updateOrCreate(
                ['kode_pelacakan' => $data['kode_pelacakan']],
                $data
            );

            if ($laporan->foto()->count() === 0) {
                FotoLaporan::create([
                    'laporan_banjir_id' => $laporan->id,
                    'foto_path' => 'demo-foto/banjir-demo.jpg',
                    'urutan' => 1,
                ]);
            }
        }

        Cache::forget('admin.sidebar.verifikasi_count');
        Cache::forget('admin.sidebar.penugasan_count');
    }
}
