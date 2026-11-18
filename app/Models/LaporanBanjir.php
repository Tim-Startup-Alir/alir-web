<?php

namespace App\Models;

use App\Support\Geo;
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
        $radius = config('alir.kepercayaan.radius_m');
        $jam = config('alir.kepercayaan.rentang_jam');
        $lat = (float) $this->latitude;
        $lng = (float) $this->longitude;
        $waktu = $this->created_at ?? now();
        $dLat = $radius / 111_320;
        $dLng = $radius / (111_320 * max(cos(deg2rad($lat)), 0.01));

        $sekitar = self::query()
            ->where('status', '!=', 'ditolak')
            ->whereBetween('latitude', [$lat - $dLat, $lat + $dLat])
            ->whereBetween('longitude', [$lng - $dLng, $lng + $dLng])
            ->whereBetween('created_at', [$waktu->copy()->subHours($jam), $waktu->copy()->addHours($jam)])
            ->get(['id', 'latitude', 'longitude', 'nomor_wa_pelapor'])
            ->filter(fn ($l) => Geo::jarakMeter($lat, $lng, (float) $l->latitude, (float) $l->longitude) <= $radius);

        $digabung = $this->duplikat()->where('status', '!=', 'ditolak')->get(['id', 'nomor_wa_pelapor']);

        return $sekitar->concat($digabung)->push($this)->pluck('nomor_wa_pelapor')->filter()->unique()->count();
    }

    /**
     * FR-26 + FR-27: tingkat kepercayaan.
     *  - Konfirmasi lapangan terakhir yang dijawab menentukan: sesuai -> tinggi; tidak sesuai/tidak ditemukan -> rendah
     *  - Tanpa konfirmasi: >= laporan_tinggi pelapor independen -> tinggi, >= laporan_sedang -> sedang, selain itu rendah
     * Foto wajib di setiap laporan (FR-05), jadi kriteria lama "1 laporan berfoto" tidak dipakai lagi.
     */
    public function hitungKepercayaan(): string
    {
        $konfirmasi = $this->konfirmasiLapangan()->where('status', 'dijawab')->latest('dijawab_pada')->first();

        if ($konfirmasi !== null) {
            return $konfirmasi->hasil === 'sesuai' ? 'tinggi' : 'rendah';
        }

        $jumlah = $this->jumlahPelaporIndependen();

        return match (true) {
            $jumlah >= config('alir.kepercayaan.laporan_tinggi') => 'tinggi',
            $jumlah >= config('alir.kepercayaan.laporan_sedang') => 'sedang',
            default => 'rendah',
        };
    }

    public function perbaruiKepercayaan(): void
    {
        $this->update(['tingkat_kepercayaan' => $this->hitungKepercayaan()]);
    }
}
