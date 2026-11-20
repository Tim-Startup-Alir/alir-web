<?php

namespace App\Providers;

use App\Models\KonfirmasiLapangan;
use App\Models\LaporanBanjir;
use App\Observers\KonfirmasiLapanganObserver;
use App\Observers\LaporanBanjirObserver;
use App\Services\KepercayaanLaporanService;
use App\Services\LaporanSekitarService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
    }
}
