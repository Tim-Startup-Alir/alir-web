<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('realisasi_pembelian')]
#[Fillable([
    'wishlist_donasi_id', 'nama_barang', 'jumlah', 'harga_satuan', 'vendor', 'nomor_invoice', 'tanggal',
    'foto_nota_path',
])]
class RealisasiPembelian extends Model
{
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'harga_satuan' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function wishlistDonasi(): BelongsTo
    {
        return $this->belongsTo(WishlistDonasi::class);
    }

    public function distribusiBantuan(): HasMany
    {
        return $this->hasMany(DistribusiBantuan::class);
    }

    protected function total(): Attribute
    {
        return Attribute::get(fn (): int => $this->jumlah * $this->harga_satuan);
    }
}
