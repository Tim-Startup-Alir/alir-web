<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    /**
     * Tiga kelurahan pilot ALIR di Kota Pekalongan.
     *
     * Sumber kode_adm4, nama, kecamatan, dan koordinat: respons resmi API BMKG
     * https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={kode} (field "lokasi"), dicek 14-11-2026.
     * Kode ini sama dengan kode wilayah Kemendagri dan dipakai langsung sebagai parameter API BMKG.
     * Koordinat = titik pusat kelurahan versi BMKG (juga dipakai untuk parameter lat/lon OpenWeather).
     *
     * batas_wilayah (GeoJSON) belum diisi karena belum ada data batas kelurahan resmi. Sampai diisi,
     * FR-24 memakai radius_cakupan_m dari titik pusat (Wilayah::cekKoordinat). Radius 1500 m sengaja
     * longgar; bila radius dua kelurahan tumpang tindih, dipilih pusat terdekat (Wilayah::temukanDari).
     */
    public function run(): void
    {
        $wilayah = [
            [
                'kode_adm4' => '33.75.01.1009',
                'nama_kelurahan' => 'Tirto',
                'kecamatan' => 'Pekalongan Barat',
                'latitude' => -6.8928121,
                'longitude' => 109.6515333,
            ],
            [
                'kode_adm4' => '33.75.01.1008',
                'nama_kelurahan' => 'Pasirkratonkramat',
                'kecamatan' => 'Pekalongan Barat',
                'latitude' => -6.8839083,
                'longitude' => 109.6567382,
            ],
            [
                'kode_adm4' => '33.75.03.1007',
                'nama_kelurahan' => 'Degayu',
                'kecamatan' => 'Pekalongan Utara',
                'latitude' => -6.8770370,
                'longitude' => 109.7031140,
            ],
        ];

        foreach ($wilayah as $data) {
            Wilayah::updateOrCreate(
                ['nama_kelurahan' => $data['nama_kelurahan'], 'kecamatan' => $data['kecamatan']],
                $data + ['radius_cakupan_m' => 1500, 'is_aktif' => true],
            );
        }
    }
}
