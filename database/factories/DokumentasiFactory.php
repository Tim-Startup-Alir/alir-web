<?php

namespace Database\Factories;

use App\Models\Dokumentasi;
use App\Models\KomunitasRelawan;
use Illuminate\Database\Eloquent\Factories\Factory;

class DokumentasiFactory extends Factory
{
    protected $model = Dokumentasi::class;

    public function definition(): array
    {
        return [
            'komunitas_relawan_id' => KomunitasRelawan::query()->firstOrFail()->id,
            'laporan_banjir_id' => LaporanBanjirFactory::new(),
            'keterangan' => fake()->sentence(),
            'jenis_media' => 'foto',
            'media_path' => 'dokumentasi/contoh.jpg',
            'status_moderasi' => 'menunggu',
        ];
    }

    public function status(string $status, ?string $alasan = null): self
    {
        return $this->state(fn () => ['status_moderasi' => $status, 'alasan_tolak' => $alasan]);
    }
}
