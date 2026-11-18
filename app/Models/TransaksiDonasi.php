<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Setiap donasi wajib memilih satu wishlist (tidak ada donasi umum).
 */
#[Table('transaksi_donasi')]
#[Fillable([
    'wishlist_donasi_id', 'order_id', 'snap_token', 'nama', 'nominal', 'anonim', 'metode_pembayaran',
    'status_pembayaran', 'waktu_bayar',
])]
class TransaksiDonasi extends Model
{
    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'anonim' => 'boolean',
            'waktu_bayar' => 'datetime',
        ];
    }

    public function wishlistDonasi(): BelongsTo
    {
        return $this->belongsTo(WishlistDonasi::class);
    }

    /**
     * Nama yang aman ditampilkan di daftar donatur publik.
     */
    public function namaTampil(): string
    {
        return $this->anonim || blank($this->nama) ? 'Anonim' : $this->nama;
    }
}
