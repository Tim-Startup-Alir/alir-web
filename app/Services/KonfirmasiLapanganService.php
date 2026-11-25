<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\KonfirmasiLapangan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\User;
use App\Support\Geo;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class KonfirmasiLapanganService
{
    private const STATUS_LAPORAN = ['menunggu_verifikasi', 'terverifikasi', 'ditugaskan', 'ditangani'];

    private const HASIL = ['terkonfirmasi', 'tidak_terkonfirmasi'];

    public function __construct(private readonly KepercayaanLaporanService $kepercayaan) {}

    public function bolehMengonfirmasi(KomunitasRelawan $komunitas, LaporanBanjir $laporan): bool
    {
        if (! $komunitas->exists || ! $laporan->exists || ! $this->komunitasAktif($komunitas->getKey())
            || ! in_array($laporan->status, self::STATUS_LAPORAN, true)) {
            return false;
        }

        $konfirmasi = KonfirmasiLapangan::query()
            ->where('laporan_banjir_id', $laporan->getKey())
            ->where('komunitas_relawan_id', $komunitas->getKey());

        if ((clone $konfirmasi)->where('status', 'dijawab')->exists()) {
            return false;
        }

        if ((clone $konfirmasi)->where('status', 'diminta')->exists()
            || $komunitas->penugasan()->where('laporan_banjir_id', $laporan->getKey())
                ->whereNotIn('stage', ['ditolak', 'dibatalkan'])->exists()) {
            return true;
        }

        return $this->jarakPoskoTerdekat($komunitas, $laporan) <= (int) config('alir.konfirmasi.radius_terdekat_m', 3000);
    }

    public function laporanUntuk(KomunitasRelawan $komunitas): Collection
    {
        if (! $komunitas->exists || ! $this->komunitasAktif($komunitas->getKey())) {
            return collect();
        }

        $idKomunitas = $komunitas->getKey();
        $idsDiminta = KonfirmasiLapangan::query()
            ->where('komunitas_relawan_id', $idKomunitas)->where('status', 'diminta')
            ->pluck('laporan_banjir_id')->map(fn ($id) => (int) $id)->all();
        $idsDitugaskan = Penugasan::query()
            ->where('komunitas_relawan_id', $idKomunitas)->whereNotIn('stage', ['ditolak', 'dibatalkan'])
            ->pluck('laporan_banjir_id')->map(fn ($id) => (int) $id)->all();
        $jarakPosko = $this->jarakLaporanDekatPosko($komunitas);
        $ids = collect($idsDiminta)->merge($idsDitugaskan)->merge(array_keys($jarakPosko))->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        /** @var Collection<int, LaporanBanjir> $laporan */
        $laporan = LaporanBanjir::query()
            ->select(['id', 'kode_pelacakan', 'wilayah_id', 'alamat_singkat', 'latitude', 'longitude', 'severity', 'jumlah_warga', 'status', 'tingkat_kepercayaan', 'created_at'])
            ->whereIn('id', $ids)
            ->whereIn('status', self::STATUS_LAPORAN)
            ->whereDoesntHave('konfirmasiLapangan', fn ($query) => $query
                ->where('komunitas_relawan_id', $idKomunitas)->where('status', 'dijawab'))
            ->with(['wilayah:id,nama_kelurahan', 'foto:id,laporan_banjir_id,foto_path,urutan'])
            ->orderByDesc('created_at')
            ->get();

        foreach ($laporan as $item) {
            $item->setAttribute('konfirmasi_diminta', in_array((int) $item->id, $idsDiminta, true));
            $item->setAttribute('jarak_posko_m', $jarakPosko[$item->id] ?? null);
        }

        return $laporan;
    }

    public function kirim(
        KomunitasRelawan $komunitas,
        LaporanBanjir $laporan,
        string $hasil,
        UploadedFile $foto,
        ?string $catatan,
        User $pelaku,
    ): KonfirmasiLapangan {
        if (! in_array($hasil, self::HASIL, true)) {
            throw new DomainException('Hasil konfirmasi tidak valid.');
        }

        $fotoPath = $foto->store('konfirmasi-lapangan', 'local');
        if (! is_string($fotoPath) || $fotoPath === '') {
            throw new DomainException('Foto bukti gagal disimpan. Silakan coba lagi.');
        }

        try {
            return DB::transaction(function () use ($komunitas, $laporan, $hasil, $fotoPath, $catatan, $pelaku): KonfirmasiLapangan {
                $terkunci = LaporanBanjir::query()->lockForUpdate()->findOrFail($laporan->getKey());
                $komunitasTerkunci = KomunitasRelawan::query()->lockForUpdate()->findOrFail($komunitas->getKey());

                if (! $this->bolehMengonfirmasi($komunitasTerkunci, $terkunci)) {
                    throw new DomainException('Komunitas Anda tidak berhak mengonfirmasi laporan ini atau sudah pernah menjawabnya.');
                }

                $konfirmasi = KonfirmasiLapangan::query()
                    ->where('laporan_banjir_id', $terkunci->getKey())
                    ->where('komunitas_relawan_id', $komunitasTerkunci->getKey())
                    ->where('status', 'diminta')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($konfirmasi) {
                    $konfirmasi->jawab($hasil, $fotoPath, $catatan);
                } else {
                    $konfirmasi = KonfirmasiLapangan::create([
                        'laporan_banjir_id' => $terkunci->getKey(),
                        'komunitas_relawan_id' => $komunitasTerkunci->getKey(),
                        'status' => 'dijawab',
                        'hasil' => $hasil,
                        'foto_path' => $fotoPath,
                        'catatan' => $catatan,
                        'dijawab_pada' => now(),
                    ]);
                }

                AuditLog::catat(
                    'konfirmasi.jawab',
                    $konfirmasi,
                    "Konfirmasi lapangan {$hasil} untuk laporan {$terkunci->kode_pelacakan} oleh {$komunitasTerkunci->nama}",
                    $pelaku,
                );
                $this->kepercayaan->hitungUlang($terkunci);

                return $konfirmasi;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($fotoPath);

            throw $exception;
        }
    }

    public function mintaKonfirmasi(LaporanBanjir $laporan, KomunitasRelawan $komunitas, User $admin): KonfirmasiLapangan
    {
        return DB::transaction(function () use ($laporan, $komunitas, $admin): KonfirmasiLapangan {
            $terkunci = LaporanBanjir::query()->lockForUpdate()->findOrFail($laporan->getKey());
            $komunitasTerkunci = KomunitasRelawan::query()->lockForUpdate()->findOrFail($komunitas->getKey());

            if (! $this->komunitasAktif($komunitasTerkunci->getKey())
                || ! in_array($terkunci->status, self::STATUS_LAPORAN, true)) {
                throw new DomainException('Komunitas atau laporan tidak dapat menerima permintaan konfirmasi.');
            }

            $sudahAda = KonfirmasiLapangan::query()
                ->where('laporan_banjir_id', $terkunci->getKey())
                ->where('komunitas_relawan_id', $komunitasTerkunci->getKey())
                ->lockForUpdate()->exists();

            if ($sudahAda) {
                throw new DomainException('Permintaan atau jawaban konfirmasi untuk laporan ini sudah ada.');
            }

            $konfirmasi = KonfirmasiLapangan::create([
                'laporan_banjir_id' => $terkunci->getKey(),
                'komunitas_relawan_id' => $komunitasTerkunci->getKey(),
                'status' => 'diminta',
                'diminta_oleh' => $admin->getKey(),
                'diminta_pada' => now(),
            ]);

            AuditLog::catat(
                'konfirmasi.minta',
                $konfirmasi,
                "Meminta konfirmasi lapangan untuk laporan {$terkunci->kode_pelacakan} dari {$komunitasTerkunci->nama}",
                $admin,
            );

            return $konfirmasi;
        });
    }

    private function komunitasAktif(int|string $id): bool
    {
        return KomunitasRelawan::aktif()->whereKey($id)->exists();
    }

    private function jarakPoskoTerdekat(KomunitasRelawan $komunitas, LaporanBanjir $laporan): float
    {
        return (float) ($this->jarakLaporanDekatPosko($komunitas, $laporan)[$laporan->getKey()] ?? INF);
    }

    /** @return array<int, float> map id laporan to closest eligible post distance in meters */
    private function jarakLaporanDekatPosko(KomunitasRelawan $komunitas, ?LaporanBanjir $target = null): array
    {
        $radius = (int) config('alir.konfirmasi.radius_terdekat_m', 3000);
        $posko = $komunitas->posko()->get(['latitude', 'longitude']);
        if ($posko->isEmpty()) {
            return [];
        }

        $query = LaporanBanjir::query()
            ->select(['id', 'latitude', 'longitude', 'status'])
            ->whereIn('status', self::STATUS_LAPORAN)
            ->whereDoesntHave('konfirmasiLapangan', fn ($builder) => $builder
                ->where('komunitas_relawan_id', $komunitas->getKey())->where('status', 'dijawab'));

        if ($target) {
            $query->whereKey($target->getKey());
        } else {
            $query->where(function ($area) use ($posko, $radius): void {
                foreach ($posko as $item) {
                    $lat = (float) $item->latitude;
                    $lng = (float) $item->longitude;
                    $dLat = $radius / 110_000;
                    $dLng = $radius / (110_000 * max(cos(deg2rad($lat)), 0.01));
                    $area->orWhere(fn ($bounds) => $bounds
                        ->whereBetween('latitude', [$lat - $dLat, $lat + $dLat])
                        ->whereBetween('longitude', [$lng - $dLng, $lng + $dLng]));
                }
            });
        }

        $hasil = [];
        foreach ($query->get() as $laporan) {
            $jarakTerdekat = INF;
            foreach ($posko as $item) {
                $jarakTerdekat = min($jarakTerdekat, Geo::jarakMeter(
                    (float) $item->latitude,
                    (float) $item->longitude,
                    (float) $laporan->latitude,
                    (float) $laporan->longitude,
                ));
            }

            if ($jarakTerdekat <= $radius) {
                $hasil[$laporan->getKey()] = $jarakTerdekat;
            }
        }

        return $hasil;
    }
}
