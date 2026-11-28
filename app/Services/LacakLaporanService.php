<?php

namespace App\Services;

use App\Enums\StatusLaporan;
use App\Models\Dokumentasi;
use App\Models\LaporanBanjir;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Pelacakan laporan oleh pelapor (publik, tanpa login).
 * Hanya menghasilkan data yang aman ditampilkan: tidak ada identitas pelapor, alamat, koordinat, foto,
 * maupun nama komunitas/relawan, karena kode laporan pendek dan bisa saja ditebak orang lain.
 */
class LacakLaporanService
{
    public const PESAN_TIDAK_ADA = 'Kode laporan tidak ditemukan. Periksa kembali kode Anda, contoh: ALR-7K3M9P.';

    public function cari(?string $kode): ?LaporanBanjir
    {
        $kode = LaporanBanjirService::normalisasiKode($kode);

        if ($kode === '') {
            return null;
        }

        return LaporanBanjir::with(['wilayah:id,nama_kelurahan', 'penugasan'])
            ->where('kode_pelacakan', $kode)
            ->first();
    }

    /**
     * @return array{
     *     kode: string, status: string, label: string, nada: string, kelurahan: ?string,
     *     dikirim: CarbonInterface|null, pesan: string, linimasa: array<int, array{judul: string, keterangan: string, waktu: ?CarbonInterface, state: string}>
     * }
     */
    public function ringkas(LaporanBanjir $laporan): array
    {
        $status = (string) $laporan->status;

        return [
            'kode' => $laporan->kode_pelacakan,
            'status' => $status,
            'label' => StatusLaporan::labelDari($status),
            'nada' => $this->nada($status),
            'kelurahan' => $laporan->wilayah?->nama_kelurahan,
            'dikirim' => $laporan->created_at,
            'pesan' => $this->pesan($status),
            'linimasa' => $this->linimasa($laporan),
            'dokumentasi' => $this->dokumentasi($laporan),
        ];
    }

    /**
     * Bukti penanganan: hanya dokumentasi yang sudah disetujui Tim ALIR (sudah tampil di galeri publik).
     * Nama komunitas dan catatan kegiatan (teks bebas) tidak ikut ditampilkan, sama seperti bagian lain halaman ini.
     *
     * @return Collection<int, Dokumentasi>
     */
    public function dokumentasi(LaporanBanjir $laporan): Collection
    {
        return Dokumentasi::query()->disetujui()->where('laporan_banjir_id', $laporan->getKey())->latest()->limit(6)->get();
    }

    /** @return array<int, array{judul: string, keterangan: string, waktu: ?CarbonInterface, state: string}> */
    public function linimasa(LaporanBanjir $laporan): array
    {
        $status = (string) $laporan->status;
        $dikirim = $this->langkah('Laporan dikirim', 'Laporan Anda sudah kami terima.', $laporan->created_at, true);

        if ($status === 'ditolak') {
            return [
                $dikirim,
                $this->langkah('Laporan tidak dapat diproses', 'Setelah diperiksa, laporan ini tidak dapat ditindaklanjuti.', $laporan->diverifikasi_pada, true, 'gagal'),
            ];
        }

        if ($status === 'digabung') {
            return [
                $dikirim,
                $this->langkah('Digabung dengan laporan lain', 'Titik yang sama sudah dilaporkan warga lain. Laporan Anda tetap dipantau bersama laporan tersebut.', $laporan->diverifikasi_pada, true),
            ];
        }

        $aktif = $laporan->penugasan->whereNotIn('stage', ['ditolak', 'dibatalkan']);
        $capai = fn (array $stage) => $aktif->contains(fn ($p) => in_array($p->stage, $stage, true));

        $verifikasi = $status !== 'menunggu_verifikasi';
        $ditugaskan = in_array($status, ['ditugaskan', 'ditangani', 'selesai'], true) || $aktif->isNotEmpty();
        $menuju = $capai(['menuju_lokasi', 'ditangani', 'selesai']);
        $ditangani = $capai(['ditangani', 'selesai']);
        $selesai = $status === 'selesai';

        $langkah = [
            $dikirim,
            $this->langkah('Diverifikasi Tim ALIR', 'Tim memeriksa kebenaran dan lokasi laporan.', $laporan->diverifikasi_pada, $verifikasi),
            $this->langkah('Relawan ditugaskan', 'Komunitas relawan terdekat menerima penugasan.', $this->waktuAwal($aktif, 'created_at'), $ditugaskan),
            $this->langkah('Relawan menuju lokasi', 'Tim relawan sedang dalam perjalanan.', $this->waktuAwal($aktif, 'waktu_menuju_lokasi'), $menuju),
            $this->langkah('Sedang ditangani', 'Relawan sudah berada di lokasi dan membantu.', $this->waktuAwal($aktif, 'waktu_ditangani'), $ditangani),
            $this->langkah('Selesai', 'Penanganan di lokasi telah selesai.', $selesai ? $this->waktuAkhir($aktif, 'waktu_selesai') : null, $selesai),
        ];

        // langkah pertama yang belum tercapai = sedang berlangsung
        foreach ($langkah as $i => $l) {
            if ($l['state'] === 'belum') {
                $langkah[$i]['state'] = 'aktif';
                break;
            }
        }

        return $langkah;
    }

    private function langkah(string $judul, string $keterangan, ?CarbonInterface $waktu, bool $tercapai, string $state = 'selesai'): array
    {
        return [
            'judul' => $judul,
            'keterangan' => $keterangan,
            'waktu' => $tercapai ? $waktu : null,
            'state' => $tercapai ? $state : 'belum',
        ];
    }

    private function waktuAwal(Collection $penugasan, string $kolom): ?CarbonInterface
    {
        return $penugasan->pluck($kolom)->filter()->sort()->first();
    }

    private function waktuAkhir(Collection $penugasan, string $kolom): ?CarbonInterface
    {
        return $penugasan->pluck($kolom)->filter()->sort()->last();
    }

    private function nada(string $status): string
    {
        return match ($status) {
            'selesai' => 'teal',
            'ditolak' => 'red',
            'menunggu_verifikasi', 'digabung' => 'amber',
            default => 'navy',
        };
    }

    private function pesan(string $status): string
    {
        return match ($status) {
            'menunggu_verifikasi' => 'Laporan Anda sedang menunggu verifikasi Tim ALIR.',
            'terverifikasi' => 'Laporan sudah diverifikasi dan menunggu relawan yang bisa ditugaskan.',
            'ditugaskan' => 'Relawan sudah ditugaskan dan segera menuju lokasi.',
            'ditangani' => 'Relawan sedang menangani di lokasi.',
            'selesai' => 'Penanganan selesai. Terima kasih sudah melapor.',
            'ditolak' => 'Laporan tidak dapat diproses. Bila kondisi masih berbahaya, kirim laporan baru atau hubungi 112.',
            'digabung' => 'Laporan Anda digabung dengan laporan lain di titik yang sama dan tetap dipantau.',
            default => 'Status laporan diperbarui.',
        };
    }
}
