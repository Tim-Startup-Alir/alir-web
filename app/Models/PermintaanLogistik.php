<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Stok TIDAK dipesan saat permintaan diajukan. Ketersediaan stok dicek ulang saat disetujui (FR-12)
 * dan sekali lagi saat distribusi dicatat, di dalam transaksi dengan kunci baris inventaris,
 * sehingga dua permintaan untuk stok yang sama tidak bisa sama-sama lolos.
 */
#[Table('permintaan_logistik')]
#[Fillable([
    'komunitas_relawan_id', 'penugasan_id', 'disetujui_oleh', 'tenggat', 'status', 'alasan', 'diproses_pada',
])]
class PermintaanLogistik extends Model
{
    public const STATUS = ['diajukan', 'disetujui', 'disetujui_sebagian', 'ditolak', 'dibatalkan', 'didistribusikan'];

    protected $attributes = ['status' => 'diajukan'];

    protected function casts(): array
    {
        return [
            'tenggat' => 'datetime',
            'diproses_pada' => 'datetime',
        ];
    }

    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }

    public function penugasan(): BelongsTo
    {
        return $this->belongsTo(Penugasan::class);
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(DetailPermintaan::class);
    }

    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class);
    }

    public function distribusiBantuan(): HasMany
    {
        return $this->hasMany(DistribusiBantuan::class);
    }

    /**
     * FR-12: Tim Startup ALIR menyetujui penuh atau sebagian.
     *
     * @param  array<int, int>  $jumlahDisetujui  [detail_permintaan_id => jumlah]; item yang tidak disebut = disetujui penuh
     *
     * @throws DomainException bila status bukan diajukan, jumlah melebihi permintaan, atau stok tidak cukup
     */
    public function setujui(User $admin, array $jumlahDisetujui = []): void
    {
        DB::transaction(function () use ($admin, $jumlahDisetujui) {
            $this->pastikanStatus('diajukan');
            $sebagian = false;

            foreach ($this->detail()->get() as $detail) {
                $jumlah = $jumlahDisetujui[$detail->id] ?? $detail->jumlah_diminta;

                if ($jumlah < 0 || $jumlah > $detail->jumlah_diminta) {
                    throw new DomainException("Jumlah disetujui untuk item #{$detail->inventaris_id} harus 0 s.d. {$detail->jumlah_diminta}.");
                }

                $this->pastikanStokCukup($detail->inventaris_id, $jumlah);
                $detail->update(['jumlah_disetujui' => $jumlah]);
                $sebagian = $sebagian || $jumlah < $detail->jumlah_diminta;
            }

            $this->update([
                'status' => $sebagian ? 'disetujui_sebagian' : 'disetujui',
                'disetujui_oleh' => $admin->id,
                'diproses_pada' => now(),
            ]);
        });
    }

    /**
     * FR-12: komunitas mencatat distribusi. Stok keluar (mutasi) dan catatan distribusi dibuat dalam
     * satu transaksi; seluruhnya gagal bila ada item yang stoknya sudah tidak cukup.
     *
     * @throws DomainException bila belum disetujui atau stok tidak cukup
     */
    public function catatDistribusi(): void
    {
        DB::transaction(function () {
            $this->pastikanStatus('disetujui', 'disetujui_sebagian');
            $laporanId = $this->penugasan->laporan_banjir_id;

            foreach ($this->detail()->with('inventaris')->where('jumlah_disetujui', '>', 0)->get() as $detail) {
                $this->pastikanStokCukup($detail->inventaris_id, $detail->jumlah_disetujui);

                $this->mutasiStok()->create([
                    'inventaris_id' => $detail->inventaris_id,
                    'jenis' => 'keluar',
                    'jumlah' => $detail->jumlah_disetujui,
                    'keterangan' => "Distribusi permintaan #{$this->id}",
                    'tanggal' => now(),
                ]);

                $this->distribusiBantuan()->create([
                    'komunitas_relawan_id' => $this->komunitas_relawan_id,
                    'laporan_banjir_id' => $laporanId,
                    'item' => $detail->inventaris->nama_barang,
                    'jumlah' => $detail->jumlah_disetujui,
                    'tanggal' => now(),
                ]);
            }

            $this->update(['status' => 'didistribusikan']);
        });
    }

    private function pastikanStatus(string ...$status): void
    {
        if (! in_array($this->status, $status, true)) {
            throw new DomainException("Permintaan berstatus '{$this->status}' tidak dapat diproses.");
        }
    }

    /**
     * Kunci baris inventaris sampai transaksi selesai, lalu hitung stok terkini.
     */
    private function pastikanStokCukup(int $inventarisId, int $jumlah): void
    {
        $inventaris = Inventaris::lockForUpdate()->findOrFail($inventarisId);

        if ($jumlah > $inventaris->stok) {
            throw new DomainException("Stok {$inventaris->nama_barang} tidak cukup (tersedia {$inventaris->stok}, dibutuhkan {$jumlah}).");
        }
    }
}
