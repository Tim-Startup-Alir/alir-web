<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('penugasan')]
#[Fillable([
    'laporan_banjir_id', 'komunitas_relawan_id', 'ditugaskan_oleh', 'jumlah_personil_tugas',
    'armada_perahu_tugas', 'jarak_km', 'skor_rekomendasi', 'stage', 'catatan', 'catatan_tahap', 'alasan_tolak',
    'waktu_diterima', 'waktu_menuju_lokasi', 'waktu_ditangani', 'waktu_selesai',
])]
class Penugasan extends Model
{
    public const STAGE = ['menunggu', 'diterima', 'ditolak', 'menuju_lokasi', 'ditangani', 'selesai', 'dibatalkan'];

    /**
     * Perpindahan tahap yang sah (FR-08, FR-09). Tahap tidak boleh dilompati (UC-12 A2).
     * Pembatalan hanya untuk penugasan yang belum diterima (FR-08).
     */
    public const TRANSISI = [
        'menunggu' => ['diterima', 'ditolak', 'dibatalkan'],
        'diterima' => ['menuju_lokasi'],
        'menuju_lokasi' => ['ditangani'],
        'ditangani' => ['selesai'],
        'ditolak' => [],
        'selesai' => [],
        'dibatalkan' => [],
    ];

    /** Kolom waktu yang diisi saat tahap tercapai. */
    private const KOLOM_WAKTU = [
        'diterima' => 'waktu_diterima',
        'menuju_lokasi' => 'waktu_menuju_lokasi',
        'ditangani' => 'waktu_ditangani',
        'selesai' => 'waktu_selesai',
    ];

    /** Default yang sama dengan database, agar model baru langsung punya stage tanpa refresh. */
    protected $attributes = ['stage' => 'menunggu'];

    /** Tahap yang masih memakai kapasitas personel komunitas. */
    public const STAGE_AKTIF = ['menunggu', 'diterima', 'menuju_lokasi', 'ditangani'];

    /**
     * FR-09: setiap penugasan dibuat atau berpindah tahap, status laporan ikut disinkronkan.
     */
    protected static function booted(): void
    {
        static::saved(function (Penugasan $penugasan) {
            if ($penugasan->wasRecentlyCreated || $penugasan->wasChanged('stage')) {
                $penugasan->laporanBanjir->sinkronStatusPenanganan();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'jumlah_personil_tugas' => 'integer',
            'armada_perahu_tugas' => 'integer',
            'jarak_km' => 'decimal:3',
            'skor_rekomendasi' => 'decimal:2',
            'catatan_tahap' => 'array',
            'waktu_diterima' => 'datetime',
            'waktu_menuju_lokasi' => 'datetime',
            'waktu_ditangani' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    public function laporanBanjir(): BelongsTo
    {
        return $this->belongsTo(LaporanBanjir::class);
    }

    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }

    public function penugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditugaskan_oleh');
    }

    public function permintaanLogistik(): HasMany
    {
        return $this->hasMany(PermintaanLogistik::class);
    }

    public function dokumentasi(): HasMany
    {
        return $this->hasMany(Dokumentasi::class);
    }

    /**
     * Catatan per tahap dalam urutan tahap (kolom JSON MySQL tidak menjaga urutan key).
     *
     * @return array<string, string>
     */
    public function catatanPerTahap(): array
    {
        $catatan = $this->catatan_tahap ?? [];

        return array_filter(array_replace(array_fill_keys(self::STAGE, null), $catatan), fn ($v) => filled($v));
    }

    public function bisaPindahKe(string $stage): bool
    {
        return in_array($stage, self::TRANSISI[$this->stage] ?? [], true);
    }

    /**
     * perbaruiStage(stage, catatan) pada class diagram. Mencatat waktu tiap tahap dan catatan per tahap.
     * Status laporan disinkronkan otomatis (booted). Notifikasi dan audit log dilakukan di lapisan service.
     *
     * @throws DomainException bila melompati tahap atau penolakan tanpa alasan
     */
    public function perbaruiStage(string $stage, ?string $catatan = null): void
    {
        if (! $this->bisaPindahKe($stage)) {
            throw new DomainException("Penugasan tidak dapat berpindah dari '{$this->stage}' ke '{$stage}'.");
        }

        if ($stage === 'ditolak') {
            if (blank($catatan)) {
                throw new DomainException('Penolakan penugasan wajib disertai alasan.');
            }
            $this->alasan_tolak = $catatan;
        } elseif (filled($catatan)) {
            $this->catatan_tahap = array_merge($this->catatan_tahap ?? [], [$stage => $catatan]);
        }

        if (isset(self::KOLOM_WAKTU[$stage])) {
            $this->{self::KOLOM_WAKTU[$stage]} = now();
        }

        $this->stage = $stage;
        $this->save();
    }
}
