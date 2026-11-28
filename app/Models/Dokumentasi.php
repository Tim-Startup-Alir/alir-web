<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('dokumentasi')]
#[Fillable([
    'komunitas_relawan_id', 'laporan_banjir_id', 'penugasan_id', 'distribusi_bantuan_id', 'keterangan', 'jenis_media',
    'media_path', 'status_moderasi', 'alasan_tolak',
])]
class Dokumentasi extends Model
{
    public const STATUS = ['menunggu', 'disetujui', 'ditolak'];

    public const LABEL_STATUS = [
        'menunggu' => 'Menunggu Moderasi',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
    ];

    /** Default yang sama dengan database. */
    protected $attributes = ['status_moderasi' => 'menunggu'];

    #[Scope]
    protected function disetujui(Builder $query): void
    {
        $query->where('status_moderasi', 'disetujui');
    }

    #[Scope]
    protected function menunggu(Builder $query): void
    {
        $query->where('status_moderasi', 'menunggu');
    }

    public function labelStatus(): string
    {
        return self::LABEL_STATUS[$this->status_moderasi] ?? $this->status_moderasi;
    }

    public function isVideo(): bool
    {
        return $this->jenis_media === 'video';
    }

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
