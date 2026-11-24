<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\User;
use App\Notifications\PenugasanBaruNotification;
use App\Support\Geo;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PenugasanService
{
    /**
     * Hitung rekomendasi komunitas aktif & terverifikasi untuk sebuah laporan banjir.
     * Diurutkan berdasarkan jarak Haversine terdekat (ETA 15 km/jam, dibulatkan ke atas).
     */
    public function rekomendasiUntuk(LaporanBanjir $laporan, ?int $kecualikanKomunitasId = null): Collection
    {
        $komunitasList = KomunitasRelawan::aktif()
            ->with([
                'user',
                'wilayah',
                'posko' => fn ($q) => $q->orderBy('id'),
                'jadwalKesiapsiagaan' => fn ($q) => $q->siagaPada(),
                'penugasan' => fn ($q) => $q->whereIn('stage', Penugasan::STAGE_AKTIF),
            ])
            ->when($kecualikanKomunitasId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->get();

        $rekomendasi = $komunitasList->map(function (KomunitasRelawan $k) use ($laporan): array {
            $posko = $k->posko->first();
            $latAsal = (float) ($posko?->latitude ?? $k->wilayah?->latitude ?? $laporan->latitude);
            $lngAsal = (float) ($posko?->longitude ?? $k->wilayah?->longitude ?? $laporan->longitude);

            $jarakM = Geo::jarakMeter($latAsal, $lngAsal, (float) $laporan->latitude, (float) $laporan->longitude);
            $jarakKm = round($jarakM / 1000, 1);
            $etaMenit = max(1, (int) ceil(($jarakM / 1000) / 15 * 60));

            $terpakaiPersonel = (int) $k->penugasan->sum('jumlah_personil_tugas');
            $kapasitasMax = (int) $k->kapasitas_max;
            $sisaKapasitas = max(0, $kapasitasMax - $terpakaiPersonel);

            $terpakaiPerahu = (int) $k->penugasan->sum('armada_perahu_tugas');
            $jumlahPerahu = (int) $k->jumlah_perahu;
            $sisaPerahu = max(0, $jumlahPerahu - $terpakaiPerahu);

            $siaga = $k->jadwalKesiapsiagaan->isNotEmpty();
            $sesuaiWilayah = (int) $k->wilayah_id === (int) $laporan->wilayah_id;

            // Skor 0..100: jarak (40) + wilayah (25) + kapasitas (20) + kesiapsiagaan (15)
            $skorJarak = max(0, 40 * (1 - min($jarakKm, 10) / 10));
            $skorWilayah = $sesuaiWilayah ? 25 : 0;
            $skorKapasitas = $kapasitasMax > 0 ? 20 * ($sisaKapasitas / $kapasitasMax) : 0;
            $skorSiaga = $siaga ? 15 : 0;
            $skor = (int) round($skorJarak + $skorWilayah + $skorKapasitas + $skorSiaga);

            $statusKode = match (true) {
                $sisaKapasitas <= 0 => 'sibuk',
                $siaga => 'siaga',
                default => 'belum_siaga',
            };

            $statusLabel = match ($statusKode) {
                'siaga' => 'Siaga',
                'sibuk' => 'Sibuk',
                default => 'Belum siaga',
            };

            $alasanDisabled = match (true) {
                $sisaKapasitas <= 0 => 'Kapasitas penuh',
                ! $siaga => 'Belum siaga',
                default => null,
            };

            $bisaDitugaskan = $siaga && $sisaKapasitas > 0;

            return [
                'komunitas_id' => $k->id,
                'nama' => $k->nama,
                'kelurahan' => $k->wilayah?->nama_kelurahan ?? '—',
                'sesuai_wilayah' => $sesuaiWilayah,
                'jarak_km' => $jarakKm,
                'eta_menit' => $etaMenit,
                'terpakai' => $terpakaiPersonel,
                'kapasitas_max' => $kapasitasMax,
                'sisa_kapasitas' => $sisaKapasitas,
                'sisa_perahu' => $sisaPerahu,
                'jumlah_perahu' => $jumlahPerahu,
                'skor' => $skor,
                'siaga' => $siaga,
                'status_kode' => $statusKode,
                'status_label' => $statusLabel,
                'bisa_ditugaskan' => $bisaDitugaskan,
                'alasan_disabled' => $alasanDisabled,
                'is_terbaik' => false,
            ];
        })->sortBy('jarak_km')->values();

        // Sorot 1 rekomendasi terbaik: siaga + kapasitas cukup + skor tertinggi (jarak terdekat sebagai pembeda)
        $terbaik = $rekomendasi
            ->filter(fn (array $item) => $item['bisa_ditugaskan'])
            ->sort(fn (array $a, array $b) => ($b['skor'] <=> $a['skor']) ?: ($a['jarak_km'] <=> $b['jarak_km']))
            ->first();

        if ($terbaik) {
            $rekomendasi = $rekomendasi->map(function (array $item) use ($terbaik) {
                if ($item['komunitas_id'] === $terbaik['komunitas_id']) {
                    $item['is_terbaik'] = true;
                }

                return $item;
            });
        }

        return $rekomendasi;
    }

    /**
     * PBI-013: Tugaskan komunitas ke laporan terverifikasi dalam 1 transaksi.
     */
    public function tugaskan(
        LaporanBanjir $laporan,
        KomunitasRelawan $komunitas,
        User $admin,
        int $personel,
        int $perahu,
        ?string $catatan = null,
    ): Penugasan {
        $penugasan = DB::transaction(function () use ($laporan, $komunitas, $admin, $personel, $perahu, $catatan): Penugasan {
            $laporanTerkunci = LaporanBanjir::query()->lockForUpdate()->findOrFail($laporan->getKey());
            $komunitasTerkunci = KomunitasRelawan::query()->with(['user', 'posko', 'wilayah'])->lockForUpdate()->findOrFail($komunitas->getKey());

            if ($laporanTerkunci->status !== 'terverifikasi') {
                throw new DomainException('Laporan hanya dapat ditugaskan jika berstatus Terverifikasi.');
            }

            $sudahAdaAktif = Penugasan::query()
                ->where('laporan_banjir_id', $laporanTerkunci->id)
                ->whereIn('stage', Penugasan::STAGE_AKTIF)
                ->exists();

            if ($sudahAdaAktif) {
                throw new DomainException('Laporan ini sudah memiliki penugasan aktif.');
            }

            if ($komunitasTerkunci->status_pendaftaran !== 'disetujui' || ! $komunitasTerkunci->user?->is_active) {
                throw new DomainException('Komunitas relawan belum terverifikasi atau tidak aktif.');
            }

            $sisaKapasitas = $komunitasTerkunci->sisaKapasitas();
            if ($personel < 1 || $personel > $sisaKapasitas) {
                throw new DomainException("Jumlah personel harus antara 1 hingga {$sisaKapasitas}.");
            }

            $perahuTerpakai = (int) $komunitasTerkunci->penugasan()->whereIn('stage', Penugasan::STAGE_AKTIF)->sum('armada_perahu_tugas');
            $sisaPerahu = max(0, (int) $komunitasTerkunci->jumlah_perahu - $perahuTerpakai);
            if ($perahu < 0 || $perahu > $sisaPerahu) {
                throw new DomainException("Jumlah perahu melebihi armada tersedia ({$sisaPerahu}).");
            }

            $posko = $komunitasTerkunci->poskoUtama();
            $latAsal = (float) ($posko?->latitude ?? $komunitasTerkunci->wilayah?->latitude ?? $laporanTerkunci->latitude);
            $lngAsal = (float) ($posko?->longitude ?? $komunitasTerkunci->wilayah?->longitude ?? $laporanTerkunci->longitude);
            $jarakKm = round(Geo::jarakMeter($latAsal, $lngAsal, (float) $laporanTerkunci->latitude, (float) $laporanTerkunci->longitude) / 1000, 3);

            $penugasan = Penugasan::create([
                'laporan_banjir_id' => $laporanTerkunci->id,
                'komunitas_relawan_id' => $komunitasTerkunci->id,
                'ditugaskan_oleh' => $admin->id,
                'jumlah_personil_tugas' => $personel,
                'armada_perahu_tugas' => $perahu,
                'jarak_km' => $jarakKm,
                'skor_rekomendasi' => $this->hitungSkorSnapshot($komunitasTerkunci, $laporanTerkunci, $jarakKm, $sisaKapasitas),
                'stage' => 'menunggu',
                'catatan' => filled($catatan) ? trim($catatan) : null,
            ]);

            AuditLog::catat(
                'penugasan.buat',
                $penugasan,
                "Menugaskan {$komunitasTerkunci->nama} (TK-{$penugasan->id}) untuk laporan {$laporanTerkunci->kode_pelacakan} dengan {$personel} personel dan {$perahu} perahu.",
                $admin,
            );

            return $penugasan;
        });

        $penugasan->load(['laporanBanjir.wilayah', 'komunitasRelawan.user', 'penugas']);
        $penugasan->komunitasRelawan->user?->notify(new PenugasanBaruNotification($penugasan));
        $this->hapusCacheBadge();

        return $penugasan;
    }

    /**
     * PBI-013: Alihkan penugasan (hanya jika stage masih menunggu) dalam 1 transaksi.
     */
    public function ganti(
        Penugasan $penugasan,
        KomunitasRelawan $komunitasBaru,
        User $admin,
        int $personel,
        int $perahu,
        ?string $catatan = null,
    ): Penugasan {
        $penugasanBaru = DB::transaction(function () use ($penugasan, $komunitasBaru, $admin, $personel, $perahu, $catatan): Penugasan {
            $lama = Penugasan::query()->with(['laporanBanjir', 'komunitasRelawan'])->lockForUpdate()->findOrFail($penugasan->getKey());

            if ($lama->stage !== 'menunggu') {
                throw new DomainException('Penugasan hanya dapat diganti selama belum diterima komunitas (stage Menunggu).');
            }

            if ((int) $lama->komunitas_relawan_id === (int) $komunitasBaru->id) {
                throw new DomainException('Pilih komunitas lain untuk mengalihkan penugasan.');
            }

            $lama->perbaruiStage('dibatalkan', 'Dialihkan ke komunitas lain.');

            AuditLog::catat(
                'penugasan.alihkan_batal',
                $lama,
                "Penugasan TK-{$lama->id} ({$lama->komunitasRelawan?->nama}) dibatalkan untuk dialihkan ke {$komunitasBaru->nama}.",
                $admin,
            );

            return $this->tugaskan($lama->laporanBanjir, $komunitasBaru, $admin, $personel, $perahu, $catatan);
        });

        $this->hapusCacheBadge();

        return $penugasanBaru;
    }

    /**
     * PBI-013: Batalkan penugasan (hanya jika stage masih menunggu).
     */
    public function batalkan(Penugasan $penugasan, User $admin, ?string $alasan = null): Penugasan
    {
        $hasil = DB::transaction(function () use ($penugasan, $admin, $alasan): Penugasan {
            $terkunci = Penugasan::query()->with(['laporanBanjir', 'komunitasRelawan'])->lockForUpdate()->findOrFail($penugasan->getKey());

            if ($terkunci->stage !== 'menunggu') {
                throw new DomainException('Penugasan yang sudah diterima komunitas tidak dapat dibatalkan.');
            }

            $terkunci->perbaruiStage('dibatalkan', filled($alasan) ? trim($alasan) : 'Dibatalkan oleh Admin.');

            AuditLog::catat(
                'penugasan.batalkan',
                $terkunci,
                "Penugasan TK-{$terkunci->id} untuk laporan {$terkunci->laporanBanjir?->kode_pelacakan} dibatalkan oleh {$admin->name}.",
                $admin,
            );

            return $terkunci;
        });

        $this->hapusCacheBadge();

        return $hasil->fresh(['laporanBanjir', 'komunitasRelawan', 'penugas']);
    }

    public function hapusCacheBadge(): void
    {
        Cache::forget('admin.sidebar.penugasan_count');
        Cache::forget('admin.sidebar.verifikasi_count');
    }

    private function hitungSkorSnapshot(KomunitasRelawan $komunitas, LaporanBanjir $laporan, float $jarakKm, int $sisaKapasitas): float
    {
        $skorJarak = max(0, 40 * (1 - min($jarakKm, 10) / 10));
        $skorWilayah = (int) $komunitas->wilayah_id === (int) $laporan->wilayah_id ? 25 : 0;
        $skorKapasitas = $komunitas->kapasitas_max > 0 ? 20 * ($sisaKapasitas / $komunitas->kapasitas_max) : 0;
        $skorSiaga = $komunitas->jadwalKesiapsiagaan()->siagaPada()->exists() ? 15 : 0;

        return round($skorJarak + $skorWilayah + $skorKapasitas + $skorSiaga, 2);
    }
}
