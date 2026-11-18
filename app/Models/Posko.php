<?php

namespace App\Models;

use App\Support\Geo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Posko boleh berada di luar 3 kelurahan pilot (mis. karena kelurahan sedang terendam), dengan syarat
 * komunitas menyatakan siap menanggung risikonya (pernyataan_luar_cakupan_pada). Laporan banjir warga
 * tetap wajib di dalam cakupan (FR-24).
 */
#[Table('posko')]
#[Fillable([
    'komunitas_relawan_id', 'nama', 'alamat', 'latitude', 'longitude', 'radius_m', 'pernyataan_luar_cakupan_pada',
])]
class Posko extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'radius_m' => 'integer',
            'pernyataan_luar_cakupan_pada' => 'datetime',
        ];
    }

    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }

    public function distribusiBantuan(): HasMany
    {
        return $this->hasMany(DistribusiBantuan::class);
    }

    /**
     * Kelurahan pilot tempat posko berada, atau null bila di luar cakupan.
     */
    public function wilayahCakupan(): ?Wilayah
    {
        return Wilayah::temukanDari((float) $this->latitude, (float) $this->longitude);
    }

    /**
     * Ditampilkan sebagai tanda "di luar cakupan" di panel Tim Startup ALIR.
     */
    public function diLuarCakupan(): bool
    {
        return $this->wilayahCakupan() === null;
    }

    /**
     * Posko di luar cakupan hanya sah bila komunitas sudah menyetujui pernyataan risiko.
     */
    public function lokasiSah(): bool
    {
        return ! $this->diLuarCakupan() || $this->pernyataan_luar_cakupan_pada !== null;
    }

    /**
     * hitungJarak(lat, lng) pada class diagram, dalam kilometer.
     */
    public function hitungJarak(float $lat, float $lng): float
    {
        return round(Geo::jarakMeter((float) $this->latitude, (float) $this->longitude, $lat, $lng) / 1000, 3);
    }
}
