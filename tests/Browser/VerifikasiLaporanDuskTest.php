<?php

use App\Models\FotoLaporan;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Models\Wilayah;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($k) => $k->user?->update(['wajib_ganti_sandi' => false]));
});

test('pbi-010-011-012 dusk sidebar, verifikasi valid, tolak ber-alasan, dan duplikat', function () {
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
    Storage::disk('local')->put('dusk/verif.jpg', 'img');

    $induk = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $wilayah->id,
        'kode_pelacakan' => 'ALR-INDUK1',
        'jumlah_warga' => 20,
        'status' => 'menunggu_verifikasi',
    ]);
    FotoLaporan::create(['laporan_banjir_id' => $induk->id, 'foto_path' => 'dusk/verif.jpg', 'urutan' => 1]);

    $lapTolak = LaporanBanjirFactory::new()->dekatDari($induk, 80)->create([
        'wilayah_id' => $wilayah->id,
        'kode_pelacakan' => 'ALR-TOLAK1',
        'status' => 'menunggu_verifikasi',
    ]);
    FotoLaporan::create(['laporan_banjir_id' => $lapTolak->id, 'foto_path' => 'dusk/verif.jpg', 'urutan' => 1]);

    $lapDup = LaporanBanjirFactory::new()->dekatDari($induk, 90)->create([
        'wilayah_id' => $wilayah->id,
        'kode_pelacakan' => 'ALR-DUP001',
        'jumlah_warga' => 10,
        'status' => 'menunggu_verifikasi',
    ]);
    FotoLaporan::create(['laporan_banjir_id' => $lapDup->id, 'foto_path' => 'dusk/verif.jpg', 'urutan' => 1]);

    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    /** @var DuskTestCase $dusk */
    $dusk = $this;
    $dusk->browse(function (Browser $browser) use ($admin, $induk, $lapTolak, $lapDup) {
        $browser->loginAs($admin)
            ->visit('/admin/laporan/verifikasi')
            ->waitFor('@verif-card-list', 10)
            ->assertSee('Verifikasi Laporan')
            ->assertVisible('@nav-verifikasi')
            ->assertVisible('@nav-penugasan');

        // Sidebar toggle menutup Verifikasi & Penugasan sekaligus
        $browser->click('@nav-laporan-toggle')
            ->pause(350)
            ->assertAttribute('@nav-laporan-toggle', 'aria-expanded', 'false')
            ->click('@nav-laporan-toggle')
            ->pause(350)
            ->assertAttribute('@nav-laporan-toggle', 'aria-expanded', 'true');

        // Validasi laporan induk
        $browser->click("@verif-card-{$induk->id}")
            ->waitForText('ALR-INDUK1', 10)
            ->click('@btn-valid')
            ->waitFor('@modal-valid', 5)
            ->click('@btn-konfirmasi-valid')
            ->waitUntilMissing("@verif-card-{$induk->id}", 10);

        // Tolak laporan (harus isi alasan dulu)
        $browser->click("@verif-card-{$lapTolak->id}")
            ->waitForText('ALR-TOLAK1', 10)
            ->click('@btn-tidak-valid')
            ->waitFor('@modal-tidak-valid', 5)
            ->assertDisabled('@btn-konfirmasi-tolak')
            ->type('@input-alasan-tolak', 'Lokasi tidak sesuai titik banjir')
            ->assertEnabled('@btn-konfirmasi-tolak')
            ->click('@btn-konfirmasi-tolak')
            ->waitUntilMissing("@verif-card-{$lapTolak->id}", 10);

        // Tandai duplikat
        $browser->click("@verif-card-{$lapDup->id}")
            ->waitForText('ALR-DUP001', 10)
            ->click('@btn-duplikat')
            ->waitFor('@modal-duplikat', 5)
            ->select('@select-laporan-induk', (string) $induk->id)
            ->click('@btn-konfirmasi-duplikat')
            ->waitUntilMissing("@verif-card-{$lapDup->id}", 10);
    });
});
