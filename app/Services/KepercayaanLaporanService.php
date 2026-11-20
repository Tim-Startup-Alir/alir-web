<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KonfirmasiLapangan;
use App\Models\LaporanBanjir;
use Illuminate\Support\Facades\DB;

class KepercayaanLaporanService
{
    public function __construct(private readonly LaporanSekitarService $laporanSekitar) {}

    /** @return array{level: string, alasan: array<int, string>, jumlah_pelapor_independen: int, konfirmasi: ?array{hasil: string, komunitas: ?string, waktu: ?string}, ambang: array<string, int|bool>} */
    public function analisis(LaporanBanjir $laporan): array
    {
        $ambang = [
            'laporan_tinggi' => (int) config('alir.kepercayaan.laporan_tinggi'),
            'laporan_sedang' => (int) config('alir.kepercayaan.laporan_sedang'),
            'radius_m' => (int) config('alir.kepercayaan.radius_m'),
            'rentang_jam' => (int) config('alir.kepercayaan.rentang_jam'),
            'foto_menaikkan_ke_sedang' => (bool) config('alir.kepercayaan.foto_menaikkan_ke_sedang', false),
        ];
        $jumlah = $this->jumlahPelaporIndependen($laporan);
        $konfirmasi = $this->konfirmasiTerakhir($laporan);

        if ($konfirmasi !== null) {
            $level = $konfirmasi->hasil === 'sesuai' ? 'tinggi' : 'rendah';
            $hasil = str_replace('_', ' ', $konfirmasi->hasil ?? 'belum ada hasil');
            $alasan = ["Konfirmasi lapangan terakhir menyatakan {$hasil}."];
            $ringkasanKonfirmasi = [
                'hasil' => $konfirmasi->hasil,
                'komunitas' => $konfirmasi->komunitasRelawan?->nama,
                'waktu' => $konfirmasi->dijawab_pada?->toIso8601String(),
            ];
        } else {
            $ringkasanKonfirmasi = null;
            $alasan = ["{$jumlah} pelapor independen dalam {$ambang['radius_m']} m dan ±{$ambang['rentang_jam']} jam."];
            $level = match (true) {
                $jumlah >= $ambang['laporan_tinggi'] => 'tinggi',
                $jumlah >= $ambang['laporan_sedang'] => 'sedang',
                default => 'rendah',
            };

            $punyaFoto = $laporan->relationLoaded('foto') ? $laporan->foto->isNotEmpty() : $laporan->foto()->exists();

            if ($level === 'rendah' && $ambang['foto_menaikkan_ke_sedang'] && $punyaFoto) {
                $level = 'sedang';
                $alasan[] = 'Foto laporan menaikkan tingkat kepercayaan ke sedang.';
            } elseif ($level === 'rendah') {
                $alasan[] = "Belum mencapai ambang {$ambang['laporan_sedang']} pelapor independen untuk tingkat sedang.";
            }
        }

        return [
            'level' => $level,
            'alasan' => $alasan,
            'jumlah_pelapor_independen' => $jumlah,
            'konfirmasi' => $ringkasanKonfirmasi,
            'ambang' => $ambang,
        ];
    }

    public function hitung(LaporanBanjir $laporan): string
    {
        return $this->analisis($laporan)['level'];
    }

    public function jumlahPelaporIndependen(LaporanBanjir $laporan): int
    {
        $nomor = $this->laporanSekitar->cariSekitar($laporan)
            ->filter(fn (LaporanBanjir $item) => ! in_array($item->status, ['ditolak', 'digabung'], true))
            ->pluck('nomor_wa_pelapor')
            ->all();

        if (! in_array($laporan->status, ['ditolak', 'digabung'], true)) {
            $nomor[] = $laporan->nomor_wa_pelapor;
        }

        $duplikat = $laporan->relationLoaded('duplikat')
            ? $laporan->duplikat->where('status', '!=', 'ditolak')->pluck('nomor_wa_pelapor')->all()
            : $laporan->duplikat()->where('status', '!=', 'ditolak')->pluck('nomor_wa_pelapor')->all();
        $nomor = array_merge($nomor, $duplikat);

        return collect($nomor)
            ->map(fn ($value) => $this->normalisasiNomorWa($value))
            ->filter()
            ->unique()
            ->count();
    }

    public function hitungUlang(LaporanBanjir $laporan): bool
    {
        [$berubah, $baru] = DB::transaction(function () use ($laporan): array {
            $terkunci = LaporanBanjir::query()->lockForUpdate()->findOrFail($laporan->getKey());
            $lama = $terkunci->tingkat_kepercayaan;
            $analisis = $this->analisis($terkunci);
            $baru = $analisis['level'];

            if ($lama === $baru) {
                return [false, $baru];
            }

            $terkunci->updateQuietly(['tingkat_kepercayaan' => $baru]);
            AuditLog::catat(
                'laporan.kepercayaan',
                $terkunci,
                "Tingkat kepercayaan {$lama} menjadi {$baru}: ".implode(' ', $analisis['alasan']),
                sistem: true,
            );

            return [true, $baru];
        });

        if ($berubah) {
            $laporan->setAttribute('tingkat_kepercayaan', $baru);
            $laporan->syncOriginalAttribute('tingkat_kepercayaan');
        }

        return $berubah;
    }

    public function hitungUlangSekitar(LaporanBanjir $laporan): int
    {
        $berubah = 0;

        foreach ($this->laporanSekitar->cariSekitar($laporan) as $tetangga) {
            $berubah += (int) $this->hitungUlang($tetangga);
        }

        return $berubah;
    }

    private function normalisasiNomorWa(?string $nomor): ?string
    {
        $angka = preg_replace('/\D+/', '', (string) $nomor);

        if ($angka === '') {
            return null;
        }

        return str_starts_with($angka, '0') ? '62'.substr($angka, 1) : $angka;
    }

    private function konfirmasiTerakhir(LaporanBanjir $laporan): ?KonfirmasiLapangan
    {
        $laporan->loadMissing('konfirmasiLapangan.komunitasRelawan');

        return $laporan->konfirmasiLapangan
            ->where('status', 'dijawab')
            ->sort(function (KonfirmasiLapangan $a, KonfirmasiLapangan $b) {
                $waktuA = $a->dijawab_pada?->getTimestamp() ?? 0;
                $waktuB = $b->dijawab_pada?->getTimestamp() ?? 0;

                return ($waktuB <=> $waktuA) ?: ($b->id <=> $a->id);
            })
            ->first();
    }
}
