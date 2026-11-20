<?php

namespace App\Console\Commands;

use App\Models\LaporanBanjir;
use App\Services\KepercayaanLaporanService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('alir:hitung-kepercayaan {--semua : Hitung ulang semua laporan, termasuk yang sudah memiliki nilai}')]
#[Description('Menghitung tingkat kepercayaan laporan yang belum dinilai atau seluruh laporan.')]
class HitungKepercayaanLaporan extends Command
{
    public function handle(KepercayaanLaporanService $service): int
    {
        $query = LaporanBanjir::query()->when(! $this->option('semua'), fn ($builder) => $builder->whereNull('tingkat_kepercayaan'));
        $total = $query->count();
        $berubah = 0;
        $diproses = 0;

        $this->output->progressStart($total);

        $query->chunkById(100, function ($laporanChunk) use ($service, &$berubah, &$diproses) {
            foreach ($laporanChunk as $laporan) {
                $berubah += (int) $service->hitungUlang($laporan);
                $diproses++;
                $this->output->progressAdvance();
            }
        });

        $this->output->progressFinish();
        $this->info("Selesai: {$diproses} laporan diproses, {$berubah} nilai diperbarui.");

        return self::SUCCESS;
    }
}
