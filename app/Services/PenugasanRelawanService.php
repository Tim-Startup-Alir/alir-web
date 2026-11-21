<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenugasanRelawanService
{
    public function __construct(
        private readonly RekomendasiPenugasanService $rekomendasi
    ) {}

    public function tugaskan(
        LaporanBanjir $laporan,
        int $komunitasId,
        int $jumlahPersonil,
        int $jumlahPerahu,
        ?string $catatan,
        User $admin
    ): Penugasan {
        return DB::transaction(function () use (
            $laporan,
            $komunitasId,
            $jumlahPersonil,
            $jumlahPerahu,
            $catatan,
            $admin
        ): Penugasan {
            $terkunci = LaporanBanjir::query()->lockForUpdate()->findOrFail($laporan->id);

            return $this->buatDalamTransaksi(
                $terkunci,
                $komunitasId,
                $jumlahPersonil,
                $jumlahPerahu,
                $catatan,
                $admin
            );
        });
    }

    public function batalkan(Penugasan $penugasan, string $alasan, User $admin): Penugasan
    {
        return DB::transaction(function () use ($penugasan, $alasan, $admin): Penugasan {
            $laporan = LaporanBanjir::query()
                ->lockForUpdate()
                ->findOrFail($penugasan->laporan_banjir_id);
            $terkunci = Penugasan::query()
                ->where('laporan_banjir_id', $laporan->id)
                ->lockForUpdate()
                ->findOrFail($penugasan->id);

            if ($terkunci->stage !== 'menunggu') {
                throw ValidationException::withMessages([
                    'penugasan' => 'Hanya penugasan yang belum diterima yang dapat dibatalkan.',
                ]);
            }

            $terkunci->perbaruiStage('dibatalkan', $alasan);
            AuditLog::catat(
                'penugasan.batalkan',
                $terkunci,
                "Membatalkan penugasan {$terkunci->id} untuk laporan {$laporan->kode_pelacakan}: {$alasan}",
                $admin
            );

            return $terkunci->fresh(['laporanBanjir', 'komunitasRelawan']);
        });
    }

    public function perbarui(
        Penugasan $penugasan,
        int $jumlahPersonil,
        int $jumlahPerahu,
        ?string $catatan,
        User $admin
    ): Penugasan {
        return DB::transaction(function () use (
            $penugasan,
            $jumlahPersonil,
            $jumlahPerahu,
            $catatan,
            $admin
        ): Penugasan {
            $laporan = LaporanBanjir::query()
                ->lockForUpdate()
                ->findOrFail($penugasan->laporan_banjir_id);
            $terkunci = Penugasan::query()
                ->where('laporan_banjir_id', $laporan->id)
                ->lockForUpdate()
                ->findOrFail($penugasan->id);

            if ($terkunci->stage !== 'menunggu') {
                throw ValidationException::withMessages([
                    'penugasan' => 'Hanya penugasan yang belum diterima yang dapat diedit.',
                ]);
            }

            $komunitas = KomunitasRelawan::query()
                ->lockForUpdate()
                ->findOrFail($terkunci->komunitas_relawan_id);
            $tugasLain = Penugasan::query()
                ->where('komunitas_relawan_id', $komunitas->id)
                ->whereIn('stage', Penugasan::STAGE_AKTIF)
                ->where('id', '!=', $terkunci->id)
                ->lockForUpdate()
                ->get();
            $maksPersonil = max(0, $komunitas->kapasitas_max - (int) $tugasLain->sum('jumlah_personil_tugas'));
            $maksPerahu = max(0, $komunitas->jumlah_perahu - (int) $tugasLain->sum('armada_perahu_tugas'));

            if ($jumlahPersonil < 1 || $jumlahPersonil > $maksPersonil) {
                throw ValidationException::withMessages([
                    'jumlah_personil_tugas' => 'Jumlah personel melebihi kapasitas komunitas yang tersedia.',
                ]);
            }

            if ($jumlahPerahu < 0 || $jumlahPerahu > $maksPerahu) {
                throw ValidationException::withMessages([
                    'armada_perahu_tugas' => 'Jumlah perahu melebihi armada komunitas yang tersedia.',
                ]);
            }

            $sebelumnya = "{$terkunci->jumlah_personil_tugas} personel, {$terkunci->armada_perahu_tugas} perahu";
            $terkunci->update([
                'jumlah_personil_tugas' => $jumlahPersonil,
                'armada_perahu_tugas' => $jumlahPerahu,
                'catatan' => $catatan,
            ]);
            AuditLog::catat(
                'penugasan.perbarui',
                $terkunci,
                "Memperbarui penugasan {$terkunci->id} untuk laporan {$laporan->kode_pelacakan}: {$sebelumnya} menjadi {$jumlahPersonil} personel, {$jumlahPerahu} perahu",
                $admin
            );

            return $terkunci->fresh(['laporanBanjir', 'komunitasRelawan']);
        });
    }

    public function alihkan(
        Penugasan $penugasan,
        int $komunitasId,
        int $jumlahPersonil,
        int $jumlahPerahu,
        ?string $catatan,
        string $alasan,
        User $admin
    ): Penugasan {
        return DB::transaction(function () use (
            $penugasan,
            $komunitasId,
            $jumlahPersonil,
            $jumlahPerahu,
            $catatan,
            $alasan,
            $admin
        ): Penugasan {
            $laporan = LaporanBanjir::query()
                ->lockForUpdate()
                ->findOrFail($penugasan->laporan_banjir_id);
            $lama = Penugasan::query()
                ->where('laporan_banjir_id', $laporan->id)
                ->lockForUpdate()
                ->findOrFail($penugasan->id);

            if ($lama->stage !== 'menunggu') {
                throw ValidationException::withMessages([
                    'penugasan' => 'Hanya penugasan yang belum diterima yang dapat dialihkan.',
                ]);
            }

            if ((int) $lama->komunitas_relawan_id === $komunitasId) {
                throw ValidationException::withMessages([
                    'komunitas_relawan_id' => 'Pilih komunitas yang berbeda untuk pengalihan.',
                ]);
            }

            $lama->perbaruiStage('dibatalkan', $alasan);
            AuditLog::catat(
                'penugasan.alihkan_batalkan',
                $lama,
                "Membatalkan penugasan {$lama->id} untuk dialihkan: {$alasan}",
                $admin
            );

            $baru = $this->buatDalamTransaksi(
                $laporan,
                $komunitasId,
                $jumlahPersonil,
                $jumlahPerahu,
                $catatan,
                $admin
            );
            AuditLog::catat(
                'penugasan.alihkan_buat',
                $baru,
                "Mengalihkan laporan {$laporan->kode_pelacakan} dari penugasan {$lama->id} ke {$baru->komunitasRelawan->nama}: {$alasan}",
                $admin
            );

            return $baru->fresh(['laporanBanjir', 'komunitasRelawan']);
        });
    }

    public function responKomunitas(Penugasan $penugasan, KomunitasRelawan $komunitas, string $stage, ?string $alasan): Penugasan
    {
        return DB::transaction(function () use ($penugasan, $komunitas, $stage, $alasan): Penugasan {
            $laporan = LaporanBanjir::query()
                ->lockForUpdate()
                ->findOrFail($penugasan->laporan_banjir_id);
            $terkunci = Penugasan::query()
                ->where('laporan_banjir_id', $laporan->id)
                ->lockForUpdate()
                ->findOrFail($penugasan->id);

            if ((int) $terkunci->komunitas_relawan_id !== (int) $komunitas->id) {
                abort(404);
            }

            if ($terkunci->stage !== 'menunggu' || ! in_array($stage, ['diterima', 'ditolak'], true)) {
                throw ValidationException::withMessages([
                    'penugasan' => 'Penugasan ini tidak dapat direspons.',
                ]);
            }

            if ($stage === 'ditolak' && blank($alasan)) {
                throw ValidationException::withMessages([
                    'alasan_tolak' => 'Alasan penolakan wajib diisi.',
                ]);
            }

            $terkunci->perbaruiStage($stage, $alasan);
            AuditLog::catat(
                'penugasan.'.$stage,
                $terkunci,
                $stage === 'ditolak'
                    ? "Komunitas {$komunitas->nama} menolak tugas laporan {$laporan->kode_pelacakan}: {$alasan}"
                    : "Komunitas {$komunitas->nama} menerima tugas laporan {$laporan->kode_pelacakan}",
                $komunitas->user
            );

            return $terkunci->fresh(['laporanBanjir', 'komunitasRelawan']);
        });
    }

    private function buatDalamTransaksi(
        LaporanBanjir $laporan,
        int $komunitasId,
        int $jumlahPersonil,
        int $jumlahPerahu,
        ?string $catatan,
        User $admin
    ): Penugasan {
        if (! in_array($laporan->status, ['terverifikasi', 'ditugaskan'], true)) {
            throw ValidationException::withMessages([
                'laporan' => 'Laporan belum terverifikasi atau sudah tidak dapat ditugaskan.',
            ]);
        }

        $komunitas = KomunitasRelawan::query()
            ->lockForUpdate()
            ->findOrFail($komunitasId);
        $kandidat = collect($this->rekomendasi->untukLaporan($laporan)['kelompok'])
            ->flatMap(fn (array $group) => $group['kandidat'])
            ->first(fn (array $item) => (int) $item['komunitas']->id === $komunitasId);

        if ($komunitas->status_pendaftaran !== 'disetujui' || ! $komunitas->user?->is_active) {
            throw ValidationException::withMessages([
                'komunitas_relawan_id' => 'Komunitas belum terverifikasi.',
            ]);
        }

        if (! $kandidat || ! $kandidat['bisa_ditugaskan']) {
            throw ValidationException::withMessages([
                'komunitas_relawan_id' => $kandidat['alasan_tidak_bisa'] ?? 'Komunitas tidak memenuhi kriteria penugasan.',
            ]);
        }

        if ($jumlahPersonil < 1 || $jumlahPersonil > $komunitas->sisaKapasitas()) {
            throw ValidationException::withMessages([
                'jumlah_personil_tugas' => 'Jumlah personel melebihi kapasitas komunitas yang tersedia.',
            ]);
        }

        if ($jumlahPerahu < 0 || $jumlahPerahu > $kandidat['sisa_perahu']) {
            throw ValidationException::withMessages([
                'armada_perahu_tugas' => 'Jumlah perahu melebihi armada komunitas yang tersedia.',
            ]);
        }

        $penugasan = Penugasan::create([
            'laporan_banjir_id' => $laporan->id,
            'komunitas_relawan_id' => $komunitas->id,
            'ditugaskan_oleh' => $admin->id,
            'jumlah_personil_tugas' => $jumlahPersonil,
            'armada_perahu_tugas' => $jumlahPerahu,
            'jarak_km' => $kandidat['jarak_km'],
            'skor_rekomendasi' => $kandidat['skor_rekomendasi'],
            'stage' => 'menunggu',
            'catatan' => $catatan,
        ]);
        AuditLog::catat(
            'penugasan.buat',
            $penugasan,
            "Menugaskan {$komunitas->nama} untuk laporan {$laporan->kode_pelacakan}: {$jumlahPersonil} personel, {$jumlahPerahu} perahu",
            $admin
        );

        return $penugasan->load(['laporanBanjir', 'komunitasRelawan.user']);
    }
}
