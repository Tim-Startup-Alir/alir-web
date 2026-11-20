<?php

namespace Database\Seeders;

use App\Models\FotoLaporan;
use App\Models\Wilayah;
use Database\Factories\LaporanBanjirFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class LaporanDemoSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk('local');
        $disk->put('demo-foto/demo.jpg', base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAABP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL8A/9k=', true));
        $wilayah = collect(['Tirto', 'Pasirkratonkramat', 'Degayu'])->mapWithKeys(fn ($nama) => [$nama => Wilayah::where('nama_kelurahan', $nama)->firstOrFail()]);

        foreach ($wilayah as $nama => $area) {
            $pusat = LaporanBanjirFactory::new()->create([
                'wilayah_id' => $area->id, 'latitude' => (float) $area->latitude, 'longitude' => (float) $area->longitude,
                'alamat_singkat' => "Laporan demo {$nama}", 'created_at' => now()->subMinutes(15),
            ]);
            $jumlah = $nama === 'Tirto' ? 4 : 0;
            for ($i = 0; $i < $jumlah; $i++) {
                $tambahan = LaporanBanjirFactory::new()->dekatDari($pusat, 80 + 25 * $i)->create([
                    'wilayah_id' => $area->id, 'created_at' => now()->subMinutes(30 + $i),
                ]);
                FotoLaporan::create(['laporan_banjir_id' => $tambahan->id, 'foto_path' => 'demo-foto/demo.jpg', 'urutan' => 1]);
            }
            FotoLaporan::create(['laporan_banjir_id' => $pusat->id, 'foto_path' => 'demo-foto/demo.jpg', 'urutan' => 1]);
        }

        $tirto = $wilayah['Tirto'];
        $lama = LaporanBanjirFactory::new()->create([
            'wilayah_id' => $tirto->id, 'latitude' => (float) $tirto->latitude, 'longitude' => (float) $tirto->longitude,
            'alamat_singkat' => 'Laporan demo lebih dari 24 jam', 'created_at' => now()->subHours(30),
        ]);
        FotoLaporan::create(['laporan_banjir_id' => $lama->id, 'foto_path' => 'demo-foto/demo.jpg', 'urutan' => 1]);
    }
}
