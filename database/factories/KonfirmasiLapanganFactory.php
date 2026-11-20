<?php

namespace Database\Factories;

use App\Models\KomunitasRelawan;
use App\Models\KonfirmasiLapangan;
use Illuminate\Database\Eloquent\Factories\Factory;

class KonfirmasiLapanganFactory extends Factory
{
    protected $model = KonfirmasiLapangan::class;

    public function definition(): array
    {
        return [
            'laporan_banjir_id' => LaporanBanjirFactory::new(),
            'komunitas_relawan_id' => KomunitasRelawan::query()->firstOrFail()->id,
            'status' => 'diminta', 'diminta_pada' => now(),
        ];
    }
}
