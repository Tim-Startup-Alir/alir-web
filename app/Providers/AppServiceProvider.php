<?php

namespace App\Providers;

use App\Models\KonfirmasiLapangan;
use App\Models\LaporanBanjir;
use App\Observers\KonfirmasiLapanganObserver;
use App\Observers\LaporanBanjirObserver;
use App\Services\KepercayaanLaporanService;
use App\Services\LaporanSekitarService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Windows: `php artisan serve` hanya meneruskan env tertentu ke server PHP. Tanpa TEMP/TMP,
        // PHP gagal membuat file sementara sehingga semua upload (foto laporan, dokumen, konfirmasi) ditolak.
        if ($this->app->runningInConsole()) {
            ServeCommand::$passthroughVariables = array_values(array_unique(
                [...ServeCommand::$passthroughVariables, 'TEMP', 'TMP'],
            ));
        }

        $this->app->scoped(LaporanSekitarService::class);
        $this->app->scoped(KepercayaanLaporanService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // F-03 Pemetaan Titik Banjir
        LaporanBanjir::observe(LaporanBanjirObserver::class);
        KonfirmasiLapangan::observe(KonfirmasiLapanganObserver::class);

        // F-02 Pelaporan Banjir (NFR-02): batasi pengiriman laporan publik per IP
        RateLimiter::for('lapor-banjir', function (Request $request) {
            $tolak = fn (Request $request, array $headers) => $request->expectsJson()
                ? response()->json(['message' => 'Terlalu banyak laporan dari perangkat ini. Coba lagi beberapa menit lagi.'], 429, $headers)
                : back()->withInput()->with('error', 'Terlalu banyak laporan dari perangkat ini. Coba lagi beberapa menit lagi.');

            return [
                Limit::perMinute((int) config('alir.laporan.batas_per_menit', 5))->by('lapor-menit|'.$request->ip())->response($tolak),
                Limit::perHour((int) config('alir.laporan.batas_per_jam', 20))->by('lapor-jam|'.$request->ip())->response($tolak),
            ];
        });
        RateLimiter::for('cek-lokasi', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}
