<?php

namespace Database\Factories;

use App\Models\FotoLaporan;
use Illuminate\Database\Eloquent\Factories\Factory;

class FotoLaporanFactory extends Factory
{
    protected $model = FotoLaporan::class;

    public function definition(): array
    {
        return ['laporan_banjir_id' => LaporanBanjirFactory::new(), 'foto_path' => 'demo-foto/demo.jpg', 'urutan' => 1];
    }
}
