<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Donasi hanya lewat wishlist (tidak ada donasi umum).
 * Dana terkumpul tidak disimpan; dihitung dari SUM(transaksi_donasi.nominal) yang berhasil.
 */
#[Table('wishlist_donasi')]
#[Fillable([
    'komunitas_relawan_id', 'nama_barang', 'gambar_path', 'urgensi', 'harga_satuan', 'target_jumlah',
    'satuan', 'kategori', 'status', 'tenggat',
])]
class WishlistDonasi extends Model
{
    protected function casts(): array
    {
        return [
            'harga_satuan' => 'integer',
            'target_jumlah' => 'integer',
            'tenggat' => 'date',
        ];
    }

    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }

    public function transaksiDonasi(): HasMany
    {
        return $this->hasMany(TransaksiDonasi::class);
    }

    /**
     * Daftar donatur publik (FR-16): donasi berhasil, terbaru dulu.
     */
    public function donasiBerhasil(): HasMany
    {
        return $this->transaksiDonasi()->where('status_pembayaran', 'berhasil')->latest('waktu_bayar');
    }

    public function realisasiPembelian(): HasMany
    {
        return $this->hasMany(RealisasiPembelian::class);
    }

    protected function targetDana(): Attribute
    {
        return Attribute::get(fn (): int => $this->harga_satuan * $this->target_jumlah);
    }

    protected function danaTerkumpul(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->transaksiDonasi()->where('status_pembayaran', 'berhasil')->sum('nominal'));
    }

    protected function danaTerpakai(): Attribute
    {
        return Attribute::get(fn (): int => (int) $this->realisasiPembelian()->sum(
            DB::raw('jumlah * harga_satuan')
        ));
    }

    protected function sisaDana(): Attribute
    {
        return Attribute::get(fn (): int => $this->dana_terkumpul - $this->dana_terpakai);
    }
}
