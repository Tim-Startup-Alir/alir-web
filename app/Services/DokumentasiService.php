<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DistribusiBantuan;
use App\Models\Dokumentasi;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DokumentasiService
{
    private const VIDEO_EXT = ['mp4', 'webm'];

    /** Laporan yang pernah ditangani komunitas (penugasan belum ditolak/dibatalkan). */
    public function laporanTersedia(KomunitasRelawan $komunitas): Collection
    {
        return LaporanBanjir::query()
            ->whereIn('id', $komunitas->penugasan()
                ->whereNotIn('stage', ['ditolak', 'dibatalkan'])
                ->select('laporan_banjir_id'))
            ->with('wilayah:id,nama_kelurahan')
            ->orderByDesc('id')
            ->get(['id', 'kode_pelacakan', 'wilayah_id', 'alamat_singkat']);
    }

    /** Item logistik yang disalurkan komunitas. */
    public function logistikTersedia(KomunitasRelawan $komunitas): Collection
    {
        return DistribusiBantuan::query()
            ->where('komunitas_relawan_id', $komunitas->getKey())
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get(['id', 'item', 'jumlah', 'tanggal']);
    }

    public function laporanMilikKomunitas(KomunitasRelawan $komunitas, int $laporanId): bool
    {
        return $this->laporanTersedia($komunitas)->contains('id', $laporanId);
    }

    public function logistikMilikKomunitas(KomunitasRelawan $komunitas, int $distribusiId): bool
    {
        return DistribusiBantuan::query()
            ->whereKey($distribusiId)
            ->where('komunitas_relawan_id', $komunitas->getKey())
            ->exists();
    }

    /**
     * UC-18: simpan dokumentasi berstatus Menunggu Moderasi.
     *
     * @param  array{laporan_banjir_id:int, distribusi_bantuan_id?:int|null, keterangan?:string|null}  $data
     *
     * @throws DomainException bila tautan bukan milik komunitas atau berkas gagal disimpan
     */
    public function unggah(KomunitasRelawan $komunitas, array $data, UploadedFile $berkas, User $pelaku): Dokumentasi
    {
        $laporanId = (int) $data['laporan_banjir_id'];
        $distribusiId = ! empty($data['distribusi_bantuan_id']) ? (int) $data['distribusi_bantuan_id'] : null;

        if (! $this->laporanMilikKomunitas($komunitas, $laporanId)) {
            throw new DomainException('Laporan yang dipilih bukan laporan yang ditangani komunitas Anda.');
        }
        if ($distribusiId !== null && ! $this->logistikMilikKomunitas($komunitas, $distribusiId)) {
            throw new DomainException('Item logistik yang dipilih bukan milik komunitas Anda.');
        }

        $jenis = in_array(strtolower($berkas->getClientOriginalExtension()), self::VIDEO_EXT, true) ? 'video' : 'foto';
        $path = $berkas->store('dokumentasi', 'local');
        if (! is_string($path) || $path === '') {
            throw new DomainException('Berkas gagal disimpan. Coba lagi.');
        }

        try {
            return DB::transaction(function () use ($komunitas, $laporanId, $distribusiId, $data, $jenis, $path, $pelaku): Dokumentasi {
                $dokumentasi = Dokumentasi::create([
                    'komunitas_relawan_id' => $komunitas->getKey(),
                    'laporan_banjir_id' => $laporanId,
                    'distribusi_bantuan_id' => $distribusiId,
                    'keterangan' => $data['keterangan'] ?? null,
                    'jenis_media' => $jenis,
                    'media_path' => $path,
                    'status_moderasi' => 'menunggu',
                ]);

                AuditLog::catat('dokumentasi.unggah', $dokumentasi, "Komunitas {$komunitas->nama} mengunggah dokumentasi {$jenis}", $pelaku);

                return $dokumentasi;
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }

    /**
     * Perbaikan dokumentasi yang ditolak: ubah catatan/tautan dan (opsional) ganti berkas,
     * lalu status kembali Menunggu Moderasi. Alasan penolakan lama dicatat di audit log.
     *
     * @param  array{laporan_banjir_id:int, distribusi_bantuan_id?:int|null, keterangan?:string|null}  $data
     *
     * @throws DomainException bila bukan dokumentasi ditolak milik komunitas, atau tautan tidak valid
     */
    public function perbaiki(Dokumentasi $dokumentasi, KomunitasRelawan $komunitas, array $data, ?UploadedFile $berkas, User $pelaku): Dokumentasi
    {
        if ($dokumentasi->komunitas_relawan_id !== $komunitas->getKey()) {
            throw new DomainException('Dokumentasi ini bukan milik komunitas Anda.');
        }

        $laporanId = (int) $data['laporan_banjir_id'];
        $distribusiId = ! empty($data['distribusi_bantuan_id']) ? (int) $data['distribusi_bantuan_id'] : null;
        if (! $this->laporanMilikKomunitas($komunitas, $laporanId)) {
            throw new DomainException('Laporan yang dipilih bukan laporan yang ditangani komunitas Anda.');
        }
        if ($distribusiId !== null && ! $this->logistikMilikKomunitas($komunitas, $distribusiId)) {
            throw new DomainException('Item logistik yang dipilih bukan milik komunitas Anda.');
        }

        $pathBaru = null;
        if ($berkas !== null) {
            $pathBaru = $berkas->store('dokumentasi', 'local');
            if (! is_string($pathBaru) || $pathBaru === '') {
                throw new DomainException('Berkas gagal disimpan. Coba lagi.');
            }
        }

        try {
            $pathLama = DB::transaction(function () use ($dokumentasi, $laporanId, $distribusiId, $data, $berkas, $pathBaru, $pelaku): ?string {
                // kunci baris: moderasi admin dan perbaikan tidak boleh saling menimpa
                $terkini = Dokumentasi::query()->whereKey($dokumentasi->getKey())->lockForUpdate()->firstOrFail();
                if ($terkini->status_moderasi !== 'ditolak') {
                    throw new DomainException('Hanya dokumentasi yang ditolak yang dapat diperbaiki.');
                }

                $alasanLama = $terkini->alasan_tolak;
                $lama = $pathBaru !== null ? $terkini->media_path : null;

                $perubahan = [
                    'laporan_banjir_id' => $laporanId,
                    'distribusi_bantuan_id' => $distribusiId,
                    'keterangan' => $data['keterangan'] ?? null,
                    'status_moderasi' => 'menunggu',
                    'alasan_tolak' => null,
                ];
                if ($pathBaru !== null) {
                    $perubahan['media_path'] = $pathBaru;
                    $perubahan['jenis_media'] = in_array(strtolower($berkas->getClientOriginalExtension()), self::VIDEO_EXT, true) ? 'video' : 'foto';
                }
                $terkini->update($perubahan);
                $dokumentasi->setRawAttributes($terkini->getAttributes(), true);

                AuditLog::catat('dokumentasi.perbaiki', $terkini, 'Dokumentasi diperbaiki dan diajukan ulang. Alasan penolakan sebelumnya: '.$alasanLama, $pelaku);

                return $lama;
            });
        } catch (Throwable $e) {
            if ($pathBaru !== null) {
                Storage::disk('local')->delete($pathBaru);
            }
            throw $e;
        }

        if ($pathLama !== null) {
            Storage::disk('local')->delete($pathLama);
        }

        return $dokumentasi;
    }

    /**
     * Komunitas menghapus dokumentasinya sendiri selama belum disetujui.
     *
     * @throws DomainException bila dokumentasi sudah disetujui (sudah tampil publik)
     */
    public function hapus(Dokumentasi $dokumentasi, User $pelaku): void
    {
        $path = DB::transaction(function () use ($dokumentasi, $pelaku): string {
            $terkini = Dokumentasi::query()->whereKey($dokumentasi->getKey())->lockForUpdate()->firstOrFail();
            if ($terkini->status_moderasi === 'disetujui') {
                throw new DomainException('Dokumentasi yang sudah disetujui tidak dapat dihapus. Hubungi Tim Startup ALIR.');
            }

            AuditLog::catat('dokumentasi.hapus', $terkini, "Dokumentasi {$terkini->jenis_media} berstatus {$terkini->status_moderasi} dihapus oleh komunitas", $pelaku);
            $terkini->delete();

            return $terkini->media_path;
        });

        Storage::disk('local')->delete($path);
    }

    /** @throws DomainException bila dokumentasi sudah dimoderasi */
    public function setujui(Dokumentasi $dokumentasi, User $admin): void
    {
        $this->pastikanMenunggu($dokumentasi);

        $dokumentasi->update(['status_moderasi' => 'disetujui', 'alasan_tolak' => null]);
        AuditLog::catat('dokumentasi.setujui', $dokumentasi, 'Dokumentasi disetujui dan tampil di galeri publik', $admin);
    }

    /** @throws DomainException bila alasan kosong atau dokumentasi sudah dimoderasi */
    public function tolak(Dokumentasi $dokumentasi, string $alasan, User $admin): void
    {
        $this->pastikanMenunggu($dokumentasi);
        if (blank($alasan)) {
            throw new DomainException('Penolakan dokumentasi wajib disertai alasan.');
        }

        $dokumentasi->update(['status_moderasi' => 'ditolak', 'alasan_tolak' => trim($alasan)]);
        AuditLog::catat('dokumentasi.tolak', $dokumentasi, 'Dokumentasi ditolak: '.trim($alasan), $admin);
    }

    public const PER_HALAMAN_GALERI = 24;

    /**
     * PBI-023: dokumentasi disetujui, terbaru dulu, dipaginasi. Pengelompokan per wilayah/komunitas
     * dilakukan pada isi halaman aktif lewat kelompokkan().
     *
     * @return LengthAwarePaginator<int, Dokumentasi>
     */
    public function galeriPublik(?int $wilayahId = null, int $perHalaman = self::PER_HALAMAN_GALERI): LengthAwarePaginator
    {
        return Dokumentasi::query()
            ->disetujui()
            ->with(['komunitasRelawan.wilayah:id,nama_kelurahan', 'laporanBanjir:id,kode_pelacakan,alamat_singkat', 'distribusiBantuan:id,item,jumlah'])
            ->when($wilayahId, fn ($q) => $q->whereHas('komunitasRelawan', fn ($k) => $k->where('wilayah_id', $wilayahId)))
            ->latest()
            ->latest('id')
            ->paginate($perHalaman)
            ->withQueryString();
    }

    /**
     * Kelompokkan dokumentasi per wilayah komunitas lalu per komunitas.
     *
     * @param  Collection<int, Dokumentasi>  $items
     * @return Collection<int, array{wilayah:string, komunitas:Collection}>
     */
    public function kelompokkan(Collection $items): Collection
    {
        return $items
            ->groupBy(fn (Dokumentasi $d) => $d->komunitasRelawan?->wilayah?->nama_kelurahan ?? 'Wilayah lain')
            ->sortKeys()
            ->map(fn (Collection $perWilayah, string $wilayah) => [
                'wilayah' => $wilayah,
                'komunitas' => $perWilayah
                    ->groupBy('komunitas_relawan_id')
                    ->map(fn (Collection $d) => ['nama' => $d->first()->komunitasRelawan?->nama ?? 'Komunitas', 'items' => $d])
                    ->sortBy('nama')
                    ->values(),
            ])
            ->values();
    }

    private function pastikanMenunggu(Dokumentasi $dokumentasi): void
    {
        if ($dokumentasi->status_moderasi !== 'menunggu') {
            throw new DomainException('Dokumentasi ini sudah dimoderasi.');
        }
    }
}
