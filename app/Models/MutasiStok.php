<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('mutasi_stok')]
#[Fillable(['inventaris_id', 'permintaan_logistik_id', 'jenis', 'jumlah', 'keterangan', 'tanggal'])]
class MutasiStok extends Model
{
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal' => 'datetime',
        ];
    }

    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(Inventaris::class);
    }

    public function permintaanLogistik(): BelongsTo
    {
        return $this->belongsTo(PermintaanLogistik::class);
    }
}
