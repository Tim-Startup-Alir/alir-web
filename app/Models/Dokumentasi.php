<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('dokumentasi')]
#[Fillable([
    'komunitas_relawan_id', 'laporan_banjir_id', 'penugasan_id', 'distribusi_bantuan_id', 'keterangan', 'jenis_media',
    'media_path', 'status_moderasi', 'alasan_tolak',
])]
class Dokumentasi extends Model
{
    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }

    public function laporanBanjir(): BelongsTo
    {
        return $this->belongsTo(LaporanBanjir::class);
    }

    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(Penugasan::class);
    }

    public function distribusiBantuan(): BelongsTo
    {
        return $this->belongsTo(DistribusiBantuan::class);
    }
}
