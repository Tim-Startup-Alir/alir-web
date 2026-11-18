<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FR-27 / UC-23 A3. Alur: Tim Startup ALIR meminta (status diminta) -> komunitas menjawab (status dijawab).
 * Komunitas juga dapat mengirim konfirmasi atas inisiatif sendiri (langsung dijawab, diminta_oleh null).
 */
#[Table('konfirmasi_lapangan')]
#[Fillable([
    'laporan_banjir_id', 'komunitas_relawan_id', 'status', 'diminta_oleh', 'diminta_pada', 'hasil',
    'foto_path', 'catatan', 'dijawab_pada',
])]
class KonfirmasiLapangan extends Model
{
    protected function casts(): array
    {
        return [
            'diminta_pada' => 'datetime',
            'dijawab_pada' => 'datetime',
        ];
    }

    public function laporanBanjir(): BelongsTo
    {
        return $this->belongsTo(LaporanBanjir::class);
    }

    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }

    public function peminta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diminta_oleh');
    }

    /**
     * kirimKonfirmasi() pada class diagram: komunitas menjawab permintaan konfirmasi.
     */
    public function jawab(string $hasil, ?string $fotoPath = null, ?string $catatan = null): void
    {
        $this->update([
            'status' => 'dijawab',
            'hasil' => $hasil,
            'foto_path' => $fotoPath,
            'catatan' => $catatan,
            'dijawab_pada' => now(),
        ]);
    }

    public function isTerkonfirmasi(): bool
    {
        return $this->status === 'dijawab' && $this->hasil === 'sesuai';
    }
}
