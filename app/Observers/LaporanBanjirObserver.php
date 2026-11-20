<?php

namespace App\Observers;

use App\Models\LaporanBanjir;
use App\Services\KepercayaanLaporanService;
use Throwable;

class LaporanBanjirObserver
{
    public function __construct(private readonly KepercayaanLaporanService $service) {}

    public function created(LaporanBanjir $laporan): void
    {
        $this->jalankanDenganAman(fn () => $this->service->hitungUlangSekitar($laporan));
        $this->jalankanDenganAman(fn () => $this->service->hitungUlang($laporan));
        $this->jalankanDenganAman(fn () => $this->hitungUlangInduk($laporan->laporan_banjir_induk_id));
    }

    public function updated(LaporanBanjir $laporan): void
    {
        $fields = ['status', 'latitude', 'longitude', 'nomor_wa_pelapor', 'laporan_banjir_induk_id'];

        if (! $laporan->wasChanged($fields)) {
            return;
        }

        $indukLama = $laporan->getOriginal('laporan_banjir_induk_id');
        $indukBaru = $laporan->laporan_banjir_induk_id;

        if ($laporan->wasChanged(['latitude', 'longitude'])) {
            $sebelum = clone $laporan;
            $sebelum->setAttribute('latitude', $laporan->getOriginal('latitude'));
            $sebelum->setAttribute('longitude', $laporan->getOriginal('longitude'));
            $this->jalankanDenganAman(fn () => $this->service->hitungUlangSekitar($sebelum));
        }

        $this->jalankanDenganAman(fn () => $this->service->hitungUlang($laporan));
        $this->jalankanDenganAman(fn () => $this->service->hitungUlangSekitar($laporan));
        $this->jalankanDenganAman(fn () => $this->hitungUlangInduk($indukLama));

        if ((string) $indukBaru !== (string) $indukLama) {
            $this->jalankanDenganAman(fn () => $this->hitungUlangInduk($indukBaru));
        }
    }

    public function deleted(LaporanBanjir $laporan): void
    {
        $this->jalankanDenganAman(fn () => $this->service->hitungUlangSekitar($laporan));
        $this->jalankanDenganAman(fn () => $this->hitungUlangInduk($laporan->laporan_banjir_induk_id));
    }

    private function hitungUlangInduk(mixed $id): void
    {
        if ($id && ($induk = LaporanBanjir::find($id))) {
            $this->service->hitungUlang($induk);
        }
    }

    private function jalankanDenganAman(callable $aksi): void
    {
        try {
            $aksi();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
