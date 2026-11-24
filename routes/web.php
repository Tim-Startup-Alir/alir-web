<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JadwalKesiapsiagaanController as AdminJadwalController;
use App\Http\Controllers\Admin\KomunitasController as AdminKomunitasController;
use App\Http\Controllers\Admin\KonfirmasiLapanganController as AdminKonfirmasiLapanganController;
use App\Http\Controllers\Admin\PenugasanController;
use App\Http\Controllers\Admin\PetaLaporanController;
use App\Http\Controllers\Admin\VerifikasiLaporanController;
use App\Http\Controllers\Admin\WilayahController as AdminWilayahController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForcePasswordChangeController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredKomunitasController;
use App\Http\Controllers\Komunitas\DashboardController as KomunitasDashboardController;
use App\Http\Controllers\Komunitas\JadwalKesiapsiagaanController as KomunitasJadwalController;
use App\Http\Controllers\Komunitas\KonfirmasiLapanganController as KomunitasKonfirmasiLapanganController;
use App\Http\Controllers\ProfilKomunitasController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/daftar', [RegisteredKomunitasController::class, 'create'])->name('register');
    Route::post('/daftar', [RegisteredKomunitasController::class, 'store'])->name('register.store');
    Route::get('/daftar/terkirim', [RegisteredKomunitasController::class, 'sent'])->name('register.sent');

    Route::get('/lupa-sandi', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-sandi/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-sandi', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

Route::get('/daftar/verifikasi-email/{id}/{hash}', [RegisteredKomunitasController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('register.verify-email');

Route::get('/daftar/perbaikan/{komunitas}', [RegisteredKomunitasController::class, 'editPerbaikan'])
    ->middleware('signed')
    ->name('register.perbaikan.edit');

Route::put('/daftar/perbaikan/{komunitas}', [RegisteredKomunitasController::class, 'updatePerbaikan'])
    ->middleware('signed')
    ->name('register.perbaikan.update');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/ganti-sandi', [ForcePasswordChangeController::class, 'edit'])->name('password.force.edit');
    Route::put('/ganti-sandi', [ForcePasswordChangeController::class, 'update'])->name('password.force.update');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'password.changed', 'role:admin'])
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // F-03 Pemetaan Titik Banjir
        Route::get('peta-laporan', [PetaLaporanController::class, 'index'])->name('peta-laporan.index');
        Route::get('peta-laporan/data', [PetaLaporanController::class, 'data'])->middleware('throttle:60,1')->name('peta-laporan.data');
        Route::get('peta-laporan/{laporan}/sekitar', [PetaLaporanController::class, 'sekitar'])->middleware('throttle:60,1')->name('peta-laporan.sekitar');
        Route::get('laporan/{laporan}/foto/{foto}', [PetaLaporanController::class, 'foto'])->scopeBindings()->name('laporan.foto');
        Route::get('peta-laporan/{laporan}/konfirmasi/{konfirmasi}', [AdminKonfirmasiLapanganController::class, 'foto'])
            ->scopeBindings()->middleware('throttle:60,1')->name('konfirmasi.foto');

        Route::resource('wilayah', AdminWilayahController::class)->except(['show']);
        Route::patch('wilayah/{wilayah}/status', [AdminWilayahController::class, 'toggleStatus'])->name('wilayah.toggle-status');

        Route::resource('komunitas', AdminKomunitasController::class)
            ->parameters(['komunitas' => 'komunitas'])
            ->except(['show']);
        Route::patch('komunitas/{komunitas}/status', [AdminKomunitasController::class, 'toggleStatus'])->name('komunitas.toggle-status');
        Route::get('komunitas/{komunitas}/dokumen', [AdminKomunitasController::class, 'dokumen'])->name('komunitas.dokumen');
        Route::post('komunitas/{komunitas}/setujui', [AdminKomunitasController::class, 'setujui'])->name('komunitas.setujui');
        Route::post('komunitas/{komunitas}/tolak', [AdminKomunitasController::class, 'tolak'])->name('komunitas.tolak');

        Route::get('komunitas/{komunitas}/profil', [ProfilKomunitasController::class, 'editAdmin'])->name('profil.edit');
        Route::put('komunitas/{komunitas}/profil', [ProfilKomunitasController::class, 'updateAdmin'])->name('profil.update');

        Route::resource('jadwal-kesiapsiagaan', AdminJadwalController::class)
            ->names('jadwal')
            ->parameters(['jadwal-kesiapsiagaan' => 'jadwal'])
            ->except(['show']);
        // F-04 Verifikasi Laporan (PBI-010, PBI-011, PBI-012)
        Route::get('laporan/verifikasi', [VerifikasiLaporanController::class, 'index'])->name('laporan.verifikasi.index');
        Route::get('laporan/verifikasi/{laporan}', [VerifikasiLaporanController::class, 'show'])->name('laporan.verifikasi.show');
        Route::post('laporan/{laporan}/valid', [VerifikasiLaporanController::class, 'valid'])->middleware('throttle:30,1')->name('laporan.valid');
        Route::post('laporan/{laporan}/tolak', [VerifikasiLaporanController::class, 'tidakValid'])->middleware('throttle:30,1')->name('laporan.tolak');
        Route::post('laporan/{laporan}/duplikat', [VerifikasiLaporanController::class, 'duplikat'])->middleware('throttle:30,1')->name('laporan.duplikat');

        // F-05 Penugasan Relawan (PBI-013)
        Route::get('laporan/penugasan', [PenugasanController::class, 'index'])->name('penugasan.index');
        Route::post('laporan/{laporan}/penugasan', [PenugasanController::class, 'store'])->middleware('throttle:30,1')->name('penugasan.store');
        Route::patch('penugasan/{penugasan}/ganti', [PenugasanController::class, 'ganti'])->middleware('throttle:30,1')->name('penugasan.ganti');
        Route::delete('penugasan/{penugasan}', [PenugasanController::class, 'destroy'])->middleware('throttle:30,1')->name('penugasan.destroy');
    });

Route::prefix('komunitas')
    ->name('komunitas.')
    ->middleware(['auth', 'verified', 'password.changed', 'role:komunitas_relawan'])
    ->group(function () {
        Route::get('/', [KomunitasDashboardController::class, 'index'])->name('dashboard');

        // F-03 Pemetaan Titik Banjir
        Route::get('konfirmasi-lapangan', [KomunitasKonfirmasiLapanganController::class, 'index'])->name('konfirmasi.index');
        Route::get('konfirmasi-lapangan/{laporan}', [KomunitasKonfirmasiLapanganController::class, 'show'])->name('konfirmasi.show');
        Route::post('konfirmasi-lapangan/{laporan}', [KomunitasKonfirmasiLapanganController::class, 'store'])
            ->middleware('throttle:10,1')->name('konfirmasi.store');
        Route::get('konfirmasi-lapangan/{laporan}/foto/{foto}', [KomunitasKonfirmasiLapanganController::class, 'fotoLaporan'])
            ->scopeBindings()->name('konfirmasi.foto-laporan');

        Route::get('profil/{komunitas?}', [ProfilKomunitasController::class, 'editKomunitas'])->name('profil.edit');
        Route::put('profil/{komunitas?}', [ProfilKomunitasController::class, 'updateKomunitas'])->name('profil.update');

        Route::get('jadwal-kesiapsiagaan', [KomunitasJadwalController::class, 'index'])->name('jadwal.index');
    });
