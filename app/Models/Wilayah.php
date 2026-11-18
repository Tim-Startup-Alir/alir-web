<?php

namespace App\Models;

use App\Support\Geo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('wilayah')]
#[Fillable([
    'kode_adm4', 'nama_kelurahan', 'kecamatan', 'latitude', 'longitude', 'batas_wilayah', 'radius_cakupan_m', 'is_aktif',
])]
class Wilayah extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'batas_wilayah' => 'array',
            'radius_cakupan_m' => 'integer',
            'is_aktif' => 'boolean',
        ];
    }

    public function komunitasRelawan(): HasMany
    {
        return $this->hasMany(KomunitasRelawan::class);
    }

    public function laporanBanjir(): HasMany
    {
        return $this->hasMany(LaporanBanjir::class);
    }

    public function cuaca(): HasMany
    {
        return $this->hasMany(Cuaca::class);
    }

    /**
     * cekKoordinat(lat, lng) pada class diagram (FR-24).
     * Pakai batas_wilayah (GeoJSON) bila ada; jika belum, pakai radius cakupan dari titik pusat kelurahan.
     */
    public function cekKoordinat(float $lat, float $lng): bool
    {
        if (! empty($this->batas_wilayah)) {
            return Geo::dalamGeoJson($lat, $lng, $this->batas_wilayah);
        }

        return $this->jarakDariPusat($lat, $lng) <= $this->radiusCakupan();
    }

    public function jarakDariPusat(float $lat, float $lng): float
    {
        return Geo::jarakMeter((float) $this->latitude, (float) $this->longitude, $lat, $lng);
    }

    public function radiusCakupan(): int
    {
        return $this->radius_cakupan_m ?? config('alir.wilayah.radius_cakupan_default_m');
    }

    /**
     * Kelurahan aktif yang memuat titik. Jika beberapa cocok (radius cadangan bisa tumpang tindih),
     * dipilih yang pusatnya paling dekat. Null = di luar wilayah cakupan; pelapor diminta mengoreksi lokasi.
     */
    public static function temukanDari(float $lat, float $lng): ?self
    {
        return self::query()->where('is_aktif', true)->get()
            ->filter(fn (self $w) => $w->cekKoordinat($lat, $lng))
            ->sortBy(fn (self $w) => $w->jarakDariPusat($lat, $lng))
            ->first();
    }
}
