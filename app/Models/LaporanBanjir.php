<?php

namespace App\Models;

use App\Services\KepercayaanLaporanService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('laporan_banjir')]
#[Fillable([
    'laporan_banjir_induk_id', 'wilayah_id', 'diverifikasi_oleh', 'kode_pelacakan', 'nama_pelapor',
    'alamat_singkat', 'latitude', 'longitude', 'sumber_lokasi', 'severity', 'jumlah_warga', 'status', 'tingkat_kepercayaan',
    'catatan', 'nomor_wa_pelapor', 'setuju_dihubungi', 'sudah_dikabari', 'alasan_tolak', 'diverifikasi_pada',
])]
#[Hidden(['nomor_wa_pelapor'])] // privasi pelapor (NFR-09/NFR-12)
class LaporanBanjir extends Model
{
    public const STATUS = [
        'menunggu_verifikasi', 'terverifikasi', 'ditugaskan', 'ditangani', 'selesai', 'ditolak', 'digabung',
    ];

    /** FR-05: foto wajib, minimal 1 per laporan (divalidasi di form request). */
    public const FOTO_MINIMAL = 1;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'jumlah_warga' => 'integer',
            'setuju_dihubungi' => 'boolean',
            'sudah_dikabari' => 'boolean',
            'diverifikasi_pada' => 'datetime',
        ];
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'laporan_banjir_induk_id');
    }

    public function duplikat(): HasMany
    {
        return $this->hasMany(self::class, 'laporan_banjir_induk_id');
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(FotoLaporan::class)->orderBy('urutan');
    }

    public function fotos(): HasMany
    {
        return $this->foto();
    }

    public function konfirmasiLapangan(): HasMany
    {
        return $this->hasMany(KonfirmasiLapangan::class);
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(Penugasan::class);
    }

    public function distribusiBantuan(): HasMany
    {
        return $this->hasMany(DistribusiBantuan::class);
    }

    public function dokumentasi(): HasMany
    {
        return $this->hasMany(Dokumentasi::class);
    }

    public function kabarWhatsapp(): HasMany
    {
        return $this->hasMany(KabarWhatsapp::class);
    }

    /**
     * FR-09: status laporan mengikuti seluruh penugasannya (satu laporan bisa ditangani beberapa komunitas).
     *  - Selesai     : semua penugasan yang masih berlaku sudah selesai
     *  - Ditangani   : minimal satu komunitas sudah mulai menangani (ditangani/selesai)
     *  - Ditugaskan  : ada penugasan yang menunggu/diterima/menuju lokasi
     *  - Terverifikasi: semua penugasan ditolak/dibatalkan -> kembali ke antrean untuk dialihkan
     * Laporan yang belum diverifikasi, ditolak, atau digabung tidak disentuh.
     * Setiap perubahan status mereset sudah_dikabari agar muncul badge "belum dikabari" (FR-30).
     */
    public function sinkronStatusPenanganan(): void
    {
        if (! in_array($this->status, ['terverifikasi', 'ditugaskan', 'ditangani', 'selesai'], true)) {
            return;
        }

        $stage = $this->penugasan()->whereNotIn('stage', ['ditolak', 'dibatalkan'])->pluck('stage');

        $statusBaru = match (true) {
            $stage->isEmpty() => 'terverifikasi',
            $stage->every(fn ($s) => $s === 'selesai') => 'selesai',
            $stage->contains(fn ($s) => in_array($s, ['ditangani', 'selesai'], true)) => 'ditangani',
            default => 'ditugaskan',
        };

        if ($statusBaru !== $this->status) {
            $this->update(['status' => $statusBaru, 'sudah_dikabari' => false]);
        }
    }

    /**
     * FR-26: jumlah pelapor independen di sekitar laporan ini.
     * Independen = nomor WhatsApp pelapor berbeda. Dihitung dari laporan dalam radius & rentang waktu
     * (config alir.kepercayaan), ditambah laporan yang sudah digabung ke laporan ini. Laporan ditolak tidak dihitung.
     */
    public function jumlahPelaporIndependen(): int
    {
        return app(KepercayaanLaporanService::class)->jumlahPelaporIndependen($this);
    }

    /** FR-26 + FR-27: aturan kepercayaan terpusat di KepercayaanLaporanService. */
    public function hitungKepercayaan(): string
    {
        return app(KepercayaanLaporanService::class)->hitung($this);
    }

    public function perbaruiKepercayaan(): void
    {
        app(KepercayaanLaporanService::class)->hitungUlang($this);
    }
}
