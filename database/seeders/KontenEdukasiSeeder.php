<?php

namespace Database\Seeders;

use App\Models\KontenEdukasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KontenEdukasiSeeder extends Seeder
{
    /** Konten awal per kategori (PBI-024). Aman dijalankan ulang: dicocokkan lewat slug. */
    public function run(): void
    {
        $konten = [
            ['Jaga saluran air dan jangan buang sampah ke sungai', 'pencegahan', "Sampah yang menyumbat saluran membuat air hujan dan air pasang tidak bisa mengalir. Bersihkan selokan di depan rumah secara rutin, terutama sebelum musim hujan.\n\nAjak tetangga kerja bakti dan pisahkan sampah agar tidak berakhir di sungai."],
            ['Menanam mangrove sebagai penahan rob', 'pencegahan', 'Hutan mangrove meredam gelombang dan menahan abrasi di pesisir. Dukung penanaman mangrove di sekitar kelurahanmu.'],
            ['Isi tas siaga bencana', 'kesiapsiagaan', "Siapkan air minum, makanan tahan lama, senter, obat pribadi, power bank, dan salinan dokumen penting dalam wadah kedap air.\n\nLetakkan tas di tempat yang mudah dijangkau seluruh anggota keluarga."],
            ['Kenali jalur evakuasi dan titik kumpul', 'kesiapsiagaan', 'Tentukan titik kumpul keluarga dan dua jalur menuju tempat yang lebih tinggi. Latihan sederhana setiap beberapa bulan membuat semua orang tenang saat kejadian.'],
            ['Yang harus dilakukan saat banjir datang', 'saat_banjir', "Matikan listrik dari saklar utama, naikkan barang penting, dan segera menuju tempat yang lebih tinggi. Jangan berjalan atau berkendara di air yang mengalir deras.\n\nLapor lewat ALIR agar relawan terdekat bisa bergerak."],
            ['Membersihkan rumah setelah banjir', 'setelah_banjir', "Kenakan sepatu bot dan sarung tangan. Buang makanan yang terendam, cuci perabot dengan air bersih dan disinfektan, lalu keringkan ruangan agar tidak berjamur.\n\nPeriksa instalasi listrik sebelum menyalakannya kembali."],
            ['Rob dan penurunan tanah di Pekalongan', 'fun_fact', 'Sebagian wilayah pesisir Pekalongan turun beberapa sentimeter setiap tahun sehingga air pasang laut makin mudah masuk ke permukiman.'],
            ['Mitos: air banjir yang tampak jernih pasti bersih', 'mitos_fakta', 'Fakta: air banjir bisa tercemar limbah dan bakteri meski tampak jernih. Hindari kontak langsung dan selalu cuci tangan dengan sabun setelahnya.'],
        ];

        foreach ($konten as [$judul, $kategori, $isi]) {
            KontenEdukasi::updateOrCreate(
                ['slug' => Str::slug($judul)],
                [
                    'judul' => $judul,
                    'kategori' => $kategori,
                    'tipe' => 'artikel',
                    'isi' => $isi,
                    'status' => 'terbit',
                    'diterbitkan_pada' => now(),
                ],
            );
        }
    }
}
