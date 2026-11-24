<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('foto_laporan')]
#[Fillable(['laporan_banjir_id', 'id_laporan_asal', 'foto_path', 'urutan'])]
class FotoLaporan extends Model
{
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function laporanBanjir(): BelongsTo
    {
        return $this->belongsTo(LaporanBanjir::class);
    }

    public function laporanAsal(): BelongsTo
    {
        return $this->belongsTo(LaporanBanjir::class, 'id_laporan_asal');
    }
}
