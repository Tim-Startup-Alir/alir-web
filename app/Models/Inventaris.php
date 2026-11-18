<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stok tidak disimpan sebagai kolom; dihitung dari mutasi_stok (masuk - keluar).
 */
#[Table('inventaris')]
#[Fillable(['komunitas_relawan_id', 'nama_barang', 'kategori', 'satuan', 'stok_minimum'])]
class Inventaris extends Model
{
    protected function casts(): array
    {
        return [
            'stok_minimum' => 'integer',
        ];
    }

    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }

    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class);
    }

    public function detailPermintaan(): HasMany
    {
        return $this->hasMany(DetailPermintaan::class);
    }

    /**
     * Stok tersedia saat ini.
     */
    protected function stok(): Attribute
    {
        return Attribute::get(function (): int {
            $masuk = (int) $this->mutasiStok()->where('jenis', 'masuk')->sum('jumlah');
            $keluar = (int) $this->mutasiStok()->where('jenis', 'keluar')->sum('jumlah');

            return $masuk - $keluar;
        });
    }

    /**
     * Level stok untuk indikator di halaman Inventaris: aman, menipis, atau kritis.
     * kritis  : stok habis, atau stok <= persen_kritis% dari stok_minimum (config/alir.php)
     * menipis : stok <= stok_minimum
     * aman    : selain itu
     */
    protected function levelStok(): Attribute
    {
        return Attribute::get(function (): string {
            $stok = $this->stok;
            $batasKritis = intdiv($this->stok_minimum * config('alir.stok.persen_kritis'), 100);

            return match (true) {
                $stok <= 0 || $stok <= $batasKritis => 'kritis',
                $stok <= $this->stok_minimum => 'menipis',
                default => 'aman',
            };
        });
    }

    /**
     * cekStokMinimum() pada class diagram.
     */
    public function isStokMenipis(): bool
    {
        return $this->level_stok !== 'aman';
    }
}
