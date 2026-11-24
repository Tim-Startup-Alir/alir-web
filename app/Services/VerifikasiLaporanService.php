<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\FotoLaporan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Support\Geo;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VerifikasiLaporanService
{
    public function __construct(
        private readonly LaporanSekitarService $laporanSekitar,
        private readonly KepercayaanLaporanService $kepercayaan,
    ) {}

    /**
     * PBI-011: Menandai laporan sebagai valid (Terverifikasi).
     */
    public function tandaiValid(LaporanBanjir $laporan, User $user): LaporanBanjir
    {
        $hasil = DB::transaction(function () use ($laporan, $user): LaporanBanjir {
            $terkunci = LaporanBanjir::query()->lockForUpdate()->findOrFail($laporan->getKey());

            if ($terkunci->status !== 'menunggu_verifikasi') {
                throw new DomainException('Laporan ini sudah diproses sebelumnya dan tidak lagi berstatus Menunggu Verifikasi.');
            }

            $statusLama = $terkunci->status;

            $terkunci->update([
                'status' => 'terverifikasi',
                'diverifikasi_oleh' => $user->id,
                'diverifikasi_pada' => now(),
                'sudah_dikabari' => false,
            ]);

            AuditLog::catat(
                'laporan.verifikasi_valid',
                $terkunci,
                "Laporan {$terkunci->kode_pelacakan} diverifikasi valid oleh {$user->name} (dari status {$statusLama} ke terverifikasi).",
                $user,
            );

            return $terkunci;
        });

        $this->kepercayaan->hitungUlang($hasil);
        $this->hapusCacheBadge();

        return $hasil->fresh(['wilayah', 'foto', 'verifikator']);
    }

    /**
     * PBI-011: Menandai laporan tidak valid (Ditolak) dengan alasan wajib.
     */
    public function tandaiTidakValid(LaporanBanjir $laporan, User $user, string $alasan): LaporanBanjir
    {
        $alasanBersih = trim($alasan);
        if ($alasanBersih === '') {
            throw new DomainException('Alasan penolakan wajib diisi.');
        }

        $hasil = DB::transaction(function () use ($laporan, $user, $alasanBersih): LaporanBanjir {
            $terkunci = LaporanBanjir::query()->lockForUpdate()->findOrFail($laporan->getKey());

            if ($terkunci->status !== 'menunggu_verifikasi') {
                throw new DomainException('Laporan ini sudah diproses sebelumnya dan tidak lagi berstatus Menunggu Verifikasi.');
            }

            $statusLama = $terkunci->status;

            $terkunci->update([
                'status' => 'ditolak',
                'alasan_tolak' => $alasanBersih,
                'diverifikasi_oleh' => $user->id,
                'diverifikasi_pada' => now(),
                'sudah_dikabari' => false,
            ]);

            AuditLog::catat(
                'laporan.verifikasi_tolak',
                $terkunci,
                "Laporan {$terkunci->kode_pelacakan} ditolak oleh {$user->name} (dari {$statusLama} ke ditolak). Alasan: {$alasanBersih}",
                $user,
            );

            return $terkunci;
        });

        $this->kepercayaan->hitungUlangSekitar($hasil);
        $this->hapusCacheBadge();

        return $hasil->fresh(['wilayah', 'foto', 'verifikator']);
    }

    /**
     * PBI-012: Menandai laporan duplikat dan menggabungkan foto serta jumlah terdampak ke laporan induk dalam satu transaksi.
     */
    public function tandaiDuplikat(LaporanBanjir $laporan, LaporanBanjir $induk, User $user): LaporanBanjir
    {
        if ($laporan->getKey() === $induk->getKey()) {
            throw new DomainException('Laporan tidak dapat digabungkan dengan dirinya sendiri.');
        }

        $radiusMaks = (int) config('alir.kepercayaan.radius_m', 500);
        $jarakM = Geo::jarakMeter(
            (float) $laporan->latitude,
            (float) $laporan->longitude,
            (float) $induk->latitude,
            (float) $induk->longitude,
        );

        if ($jarakM > $radiusMaks) {
            throw new DomainException("Laporan induk berada di luar radius {$radiusMaks} meter ({$jarakM} m).");
        }

        $hasil = DB::transaction(function () use ($laporan, $induk, $user, $jarakM): LaporanBanjir {
            // Kunci kedua baris dengan urutan ID konsisten untuk mencegah deadlock
            $ids = [$laporan->getKey(), $induk->getKey()];
            sort($ids);
            $locked = LaporanBanjir::query()->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            $terkunci = $locked->get($laporan->getKey());
            $indukTerkunci = $locked->get($induk->getKey());

            if (! $terkunci || ! $indukTerkunci) {
                throw new DomainException('Laporan atau laporan induk tidak ditemukan.');
            }

            if ($terkunci->status !== 'menunggu_verifikasi') {
                throw new DomainException('Laporan ini sudah diproses sebelumnya dan tidak lagi berstatus Menunggu Verifikasi.');
            }

            if (in_array($indukTerkunci->status, ['digabung', 'ditolak'], true)) {
                throw new DomainException('Laporan induk yang dipilih sudah berstatus digabung atau ditolak.');
            }

            // Pindahkan foto dari laporan duplikat ke laporan induk
            $urutanMaks = (int) FotoLaporan::query()
                ->where('laporan_banjir_id', $indukTerkunci->id)
                ->max('urutan');

            $fotos = FotoLaporan::query()
                ->where('laporan_banjir_id', $terkunci->id)
                ->orderBy('urutan')
                ->get();

            $jumlahFotoDipindah = $fotos->count();
            foreach ($fotos as $foto) {
                $urutanMaks++;
                $foto->update([
                    'id_laporan_asal' => $foto->id_laporan_asal ?? $terkunci->id,
                    'laporan_banjir_id' => $indukTerkunci->id,
                    'urutan' => $urutanMaks,
                ]);
            }

            // Tambahkan jumlah warga terdampak ke laporan induk
            $tambahanWarga = (int) $terkunci->jumlah_warga;
            $wargaIndukSebelum = (int) $indukTerkunci->jumlah_warga;
            $wargaIndukSesudah = $wargaIndukSebelum + $tambahanWarga;

            $indukTerkunci->update([
                'jumlah_warga' => $wargaIndukSesudah,
            ]);

            $terkunci->update([
                'laporan_banjir_induk_id' => $indukTerkunci->id,
                'status' => 'digabung',
                'diverifikasi_oleh' => $user->id,
                'diverifikasi_pada' => now(),
                'sudah_dikabari' => false,
            ]);

            AuditLog::catat(
                'laporan.verifikasi_duplikat',
                $terkunci,
                "Laporan {$terkunci->kode_pelacakan} digabung ke induk {$indukTerkunci->kode_pelacakan} (jarak {$jarakM} m) oleh {$user->name}. Foto dipindah: {$jumlahFotoDipindah}, warga induk: {$wargaIndukSebelum} -> {$wargaIndukSesudah}.",
                $user,
            );

            return $terkunci;
        });

        $this->kepercayaan->hitungUlang($induk->fresh());
        $this->hapusCacheBadge();

        return $hasil->fresh(['wilayah', 'induk', 'verifikator']);
    }

    public function hapusCacheBadge(): void
    {
        Cache::forget('admin.sidebar.verifikasi_count');
        Cache::forget('admin.sidebar.penugasan_count');
    }
}
