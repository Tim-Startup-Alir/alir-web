<?php

namespace App\Observers;

use App\Models\KonfirmasiLapangan;
use App\Models\LaporanBanjir;
use App\Services\KepercayaanLaporanService;
use Throwable;

class KonfirmasiLapanganObserver
{
    public function __construct(private readonly KepercayaanLaporanService $service) {}

    public function created(KonfirmasiLapangan $konfirmasi): void
    {
        $this->hitungUlang($konfirmasi->laporan_banjir_id);
    }

    public function updated(KonfirmasiLapangan $konfirmasi): void
    {
        if (! $konfirmasi->wasChanged(['status', 'hasil', 'dijawab_pada', 'laporan_banjir_id'])) {
            return;
        }

        if ($konfirmasi->wasChanged('laporan_banjir_id')) {
            $this->hitungUlang($konfirmasi->getOriginal('laporan_banjir_id'));
        }

        $this->hitungUlang($konfirmasi->laporan_banjir_id);
    }

    public function deleted(KonfirmasiLapangan $konfirmasi): void
    {
        $this->hitungUlang($konfirmasi->laporan_banjir_id);
    }

    private function hitungUlang(mixed $id): void
    {
        try {
            if ($id && ($laporan = LaporanBanjir::find($id))) {
                $this->service->hitungUlang($laporan);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
