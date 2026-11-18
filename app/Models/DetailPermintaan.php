<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('detail_permintaan')]
#[Fillable(['permintaan_logistik_id', 'inventaris_id', 'jumlah_diminta', 'jumlah_disetujui'])]
class DetailPermintaan extends Model
{
    protected function casts(): array
    {
        return [
            'jumlah_diminta' => 'integer',
            'jumlah_disetujui' => 'integer',
        ];
    }

    public function permintaanLogistik(): BelongsTo
    {
        return $this->belongsTo(PermintaanLogistik::class);
    }

    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(Inventaris::class);
    }
}
