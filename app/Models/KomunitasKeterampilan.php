<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('komunitas_keterampilan')]
#[Fillable(['komunitas_relawan_id', 'nama_keterampilan', 'jumlah_anggota_terampil'])]
class KomunitasKeterampilan extends Model
{
    protected function casts(): array
    {
        return [
            'jumlah_anggota_terampil' => 'integer',
        ];
    }

    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }
}
