<?php

namespace App\Models;

use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('komunitas_relawan')]
#[Fillable([
    'user_id', 'wilayah_id', 'nama', 'nama_pic', 'nomor_whatsapp_pic', 'total_anggota', 'kapasitas_max',
    'jumlah_perahu', 'status_pendaftaran', 'dokumen_pendukung_path', 'alasan_tolak_registrasi', 'diverifikasi_pada',
    'izin_tampil_publik', 'menyetujui_syarat_pada',
])]
class KomunitasRelawan extends Model
{
    protected function casts(): array
    {
        return [
            'total_anggota' => 'integer',
            'kapasitas_max' => 'integer',
            'jumlah_perahu' => 'integer',
            'diverifikasi_pada' => 'datetime',
            'izin_tampil_publik' => 'boolean',
            'menyetujui_syarat_pada' => 'datetime',
        ];
    }

    /**
     * Komunitas aktif: pendaftaran disetujui DAN akun user aktif (kontrak F5).
     */
    #[Scope]
    protected function aktif(Builder $query): void
    {
        $query->where('status_pendaftaran', 'disetujui')
            ->whereHas('user', fn (Builder $q) => $q->where('is_active', true));
    }

    /**
     * Komunitas aktif yang memiliki shift kesiapsiagaan berjalan pada waktu tertentu (kontrak F5).
     */
    #[Scope]
    protected function siagaPada(Builder $query, ?CarbonInterface $waktu = null): void
    {
        $query->aktif()
            ->whereHas('jadwalKesiapsiagaan', fn (Builder $q) => $q->siagaPada($waktu));
    }

    /**
     * F4 / A2: komunitas dianggap sedang dipakai bila memiliki penugasan, inventaris, atau konfirmasi lapangan.
     */
    public function isInUse(): bool
    {
        return $this->penugasan()->exists()
            || $this->inventaris()->exists()
            || $this->konfirmasiLapangan()->exists();
    }

    /**
     * A4: posko utama adalah posko dengan ID terkecil milik komunitas.
     */
    public function poskoUtama(): ?Posko
    {
        return $this->posko()->orderBy('id')->first();
    }

    /**
     * Antrean verifikasi Tim Startup ALIR (FR-02): status menunggu DAN email pendaftar sudah diverifikasi.
     */
    #[Scope]
    protected function siapDitinjau(Builder $query): void
    {
        $query->where('status_pendaftaran', 'menunggu')
            ->whereHas('user', fn (Builder $q) => $q->whereNotNull('email_verified_at'));
    }

    /**
     * Pengajuan ulang setelah ditolak, lewat link perbaikan (signed URL) di email penolakan.
     * Akun & email tetap sama; status kembali menunggu. Riwayat penolakan dicatat di audit_log.
     *
     * @param  array<string, mixed>  $data  data registrasi yang diperbaiki
     *
     * @throws DomainException bila pendaftaran tidak berstatus ditolak
     */
    public function ajukanUlang(array $data = []): void
    {
        if ($this->status_pendaftaran !== 'ditolak') {
            throw new DomainException('Hanya pendaftaran yang ditolak yang dapat diajukan ulang.');
        }

        $alasanLama = $this->alasan_tolak_registrasi;

        $this->fill($data);
        $this->status_pendaftaran = 'menunggu';
        $this->alasan_tolak_registrasi = null;
        $this->diverifikasi_pada = null;
        $this->save();

        AuditLog::catat('komunitas.ajukan_ulang', $this, "Diajukan ulang. Alasan penolakan sebelumnya: {$alasanLama}", $this->user);
    }

    /**
     * Sisa kapasitas personel untuk rekomendasi penugasan (FR-08):
     * kapasitas_max dikurangi personel yang sedang bertugas pada penugasan aktif.
     */
    public function sisaKapasitas(): int
    {
        $terpakai = (int) $this->penugasan()->whereIn('stage', Penugasan::STAGE_AKTIF)->sum('jumlah_personil_tugas');

        return max(0, $this->kapasitas_max - $terpakai);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function jadwalKesiapsiagaan(): HasMany
    {
        return $this->hasMany(JadwalKesiapsiagaan::class);
    }

    /**
     * Shift yang sedang siaga pada waktu tertentu (default: sekarang), beserta PIC-nya.
     */
    public function shiftSiaga(?CarbonInterface $waktu = null): ?JadwalKesiapsiagaan
    {
        return $this->jadwalKesiapsiagaan()->siagaPada($waktu)->orderBy('jam_mulai')->first();
    }

    /**
     * PIC yang dapat dihubungi saat kondisi gawat: PIC shift aktif, atau PIC komunitas bila tidak ada shift.
     *
     * @return array{nama: string, nomor_whatsapp: string, dari_shift: bool}
     */
    public function picSiaga(?CarbonInterface $waktu = null): array
    {
        $shift = $this->shiftSiaga($waktu);

        return $shift
            ? ['nama' => $shift->nama_pic, 'nomor_whatsapp' => $shift->nomor_whatsapp_pic, 'dari_shift' => true]
            : ['nama' => $this->nama_pic, 'nomor_whatsapp' => $this->nomor_whatsapp_pic, 'dari_shift' => false];
    }

    public function posko(): HasMany
    {
        return $this->hasMany(Posko::class);
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(Penugasan::class);
    }

    public function konfirmasiLapangan(): HasMany
    {
        return $this->hasMany(KonfirmasiLapangan::class);
    }

    public function inventaris(): HasMany
    {
        return $this->hasMany(Inventaris::class);
    }

    public function permintaanLogistik(): HasMany
    {
        return $this->hasMany(PermintaanLogistik::class);
    }

    public function wishlistDonasi(): HasMany
    {
        return $this->hasMany(WishlistDonasi::class);
    }

    public function distribusiBantuan(): HasMany
    {
        return $this->hasMany(DistribusiBantuan::class);
    }

    public function dokumentasi(): HasMany
    {
        return $this->hasMany(Dokumentasi::class);
    }
}
