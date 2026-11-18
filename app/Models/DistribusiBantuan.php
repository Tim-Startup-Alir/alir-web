<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sumber: realisasi_pembelian_id dan/atau permintaan_logistik_id (minimal satu).
 * Tujuan: laporan_banjir_id dan/atau posko_id (minimal satu).
 */
#[Table('distribusi_bantuan')]
#[Fillable([
    'komunitas_relawan_id', 'laporan_banjir_id', 'posko_id', 'realisasi_pembelian_id',
    'permintaan_logistik_id', 'item', 'jumlah', 'tanggal',
])]
class DistribusiBantuan extends Model
{
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }

    public function laporanBanjir(): BelongsTo
    {
        return $this->belongsTo(LaporanBanjir::class);
    }

    public function posko(): BelongsTo
    {
        return $this->belongsTo(Posko::class);
    }

    public function realisasiPembelian(): BelongsTo
    {
        return $this->belongsTo(RealisasiPembelian::class);
    }

    public function permintaanLogistik(): BelongsTo
    {
        return $this->belongsTo(PermintaanLogistik::class);
    }

    public function dokumentasi(): HasMany
    {
        return $this->hasMany(Dokumentasi::class);
    }
}
