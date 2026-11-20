<?php

namespace Database\Factories;

use App\Models\LaporanBanjir;
use App\Models\Wilayah;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class LaporanBanjirFactory extends Factory
{
    protected $model = LaporanBanjir::class;

    public function definition(): array
    {
        $wilayah = Wilayah::query()->firstOrFail();

        return [
            'wilayah_id' => $wilayah->id,
            'kode_pelacakan' => 'ALR-'.strtoupper(fake()->unique()->bothify('??????')),
            'alamat_singkat' => fake()->streetAddress(),
            'latitude' => (float) $wilayah->latitude,
            'longitude' => (float) $wilayah->longitude,
            'severity' => fake()->randomElement(['ringan', 'sedang', 'berat']),
            'jumlah_warga' => fake()->numberBetween(0, 100),
            'status' => 'menunggu_verifikasi',
            'nomor_wa_pelapor' => fake()->numerify('08##########'),
            'setuju_dihubungi' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function koordinat(float $latitude, float $longitude): self
    {
        return $this->state(fn () => ['latitude' => $latitude, 'longitude' => $longitude]);
    }

    public function statusLaporan(string $status): self
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function severity(string $severity): self
    {
        return $this->state(fn () => ['severity' => $severity]);
    }

    public function dibuatPada(Carbon $waktu): self
    {
        return $this->state(fn () => ['created_at' => $waktu, 'updated_at' => $waktu]);
    }

    public function tingkatKepercayaan(?string $level): self
    {
        return $this->state(fn () => ['tingkat_kepercayaan' => $level]);
    }

    public function nomorWa(string $nomor): self
    {
        return $this->state(fn () => ['nomor_wa_pelapor' => $nomor]);
    }

    public function dekatDari(LaporanBanjir $laporan, float $jarakM): self
    {
        $rad = $jarakM / 6_371_000;
        $lat1 = deg2rad((float) $laporan->latitude);
        do {
            $lat2 = asin(sin($lat1) * cos($rad) + cos($lat1) * sin($rad));
            $lng2 = deg2rad((float) $laporan->longitude) + atan2(sin($rad) * cos($lat1), cos($rad) - sin($lat1) * sin($lat2));
            $latitude = round(rad2deg($lat2), 7);
            $longitude = round(rad2deg($lng2), 7);
            $rad -= 0.00000001;
        } while (Geo::jarakMeter((float) $laporan->latitude, (float) $laporan->longitude, $latitude, $longitude) > $jarakM && $rad > 0);

        return $this->koordinat($latitude, $longitude);
    }
}
