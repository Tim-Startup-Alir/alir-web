<?php

use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\LaporanBanjirService;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/*
 * PBI-004, PBI-005, PBI-006: alur lengkap modal Lapor Banjir tanpa login.
 * Butuh internet (tile OpenStreetMap + Cloudflare Turnstile) dan test key Turnstile di .env.dusk.local:
 *   TURNSTILE_SITE_KEY=1x00000000000000000000AA
 *   TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA
 */
uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($k) => $k->user?->update(['wajib_ganti_sandi' => false]));
    User::where('role', User::ROLE_ADMIN)->update(['wajib_ganti_sandi' => false]);
});

test('pbi-004/005/006 dusk warga melapor banjir lalu admin menemukan laporan dari kodenya', function () {
    $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $foto = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alir-lapor-'.bin2hex(random_bytes(6)).'.png';
    file_put_contents($foto, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ic0AAAAASUVORK5CYII='));
    $kode = null;

    try {
        /** @var DuskTestCase $dusk */
        $dusk = $this;
        $dusk->browse(function (Browser $browser) use ($tirto, $admin, $foto, &$kode) {
            $pin = fn (float $lat, float $lng) => $browser->script("document.querySelector('#pmap').alirSetPin({$lat}, {$lng});");

            $browser->visit('/')
                ->waitFor('@hero-lapor', 15)
                ->click('@hero-lapor')
                ->waitFor('.ld-modal.lapor', 10)
                ->waitFor('#pmap.leaflet-container', 15)
                ->assertSee('Tandai lokasi banjir');

            // PBI-005: titik di luar cakupan ditolak dan diminta koreksi
            $pin(-6.95, 109.55);
            $browser->waitFor('[data-pin-info][data-state="out"]', 10)
                ->assertSeeIn('[data-pin-info]', 'Geser pin')
                ->type('#lp-alamat', 'Gang Melati RT 03, dekat mushola')
                ->click('@lapor-next')
                ->waitFor('@lapor-error', 5)
                ->assertSeeIn('@lapor-error', 'luar wilayah cakupan');

            // koreksi pin ke dalam Kelurahan Tirto; alamat yang sudah diketik tetap ada
            $pin((float) $tirto->latitude + 0.001, (float) $tirto->longitude);
            $browser->waitFor('[data-pin-info][data-state="ok"]', 10)
                ->assertSeeIn('[data-pin-info]', 'Kel. Tirto')
                ->assertInputValue('#lp-alamat', 'Gang Melati RT 03, dekat mushola')
                ->click('@lapor-next')
                ->waitFor('@sev-sedang', 5);

            // PBI-004 langkah 2
            $browser->click('@sev-sedang')
                ->waitFor('.sev-o.on[data-sev="sedang"]', 5)
                ->script("const w = document.querySelector('#lp-warga'); w.value = '';");
            $browser->type('#lp-warga', '15')
                ->attach('@lapor-foto', $foto)
                ->waitForText('ketuk untuk mengganti', 5)
                ->click('@lapor-next')
                ->waitFor('#lp-wa', 5);

            // PBI-004 langkah 3: WA salah ditolak tanpa menghapus isian lain
            $browser->type('#lp-wa', '08abc')
                ->type('#lp-nama', 'Budi Dusk')
                ->waitForText('Nomor tidak valid', 5)
                ->click('@lapor-next')
                ->waitFor('@lapor-error', 5)
                ->assertSeeIn('@lapor-error', 'Format nomor WhatsApp tidak valid')
                ->assertInputValue('#lp-wa', '08abc')
                ->assertInputValue('#lp-nama', 'Budi Dusk');

            // menutup modal tidak menghilangkan isian
            $browser->click('[data-x]')
                ->waitUntilMissing('.ld-modal.lapor', 5)
                ->click('@nav-lapor')
                ->waitFor('#lp-wa', 10)
                ->assertInputValue('#lp-nama', 'Budi Dusk');

            $browser->clear('#lp-wa')->type('#lp-wa', '0812 3456 7890')
                ->waitForText('Nomor valid.', 5)
                ->check('setuju_dihubungi')
                ->waitFor('[data-turnstile][data-ok]', 30)
                ->click('@lapor-next')
                ->waitForLocation('/lapor/diterima', 20)
                ->waitForText('Laporan diterima.', 10)
                ->assertSeeIn('@status-laporan', 'Menunggu Verifikasi');

            // teks dibaca lewat DOM: elemen masih beranimasi (opacity 0) sehingga WebDriver menganggapnya belum terlihat
            $kode = trim((string) ($browser->script("return document.querySelector('[dusk=\"kode-laporan\"]').textContent;")[0] ?? ''));
            expect($kode)->toMatch(LaporanBanjirService::POLA_KODE);

            // PBI-006: Tim Startup ALIR mencari laporan berdasarkan kode
            $browser->loginAs($admin)
                ->visit('/admin/laporan/cari')
                ->type('@input-kode', strtolower($kode))
                ->click('@btn-cari-kode')
                ->waitFor('@hasil-laporan', 10)
                ->assertSeeIn('@hasil-laporan', $kode)
                ->assertSeeIn('@hasil-laporan', 'Gang Melati RT 03')
                ->assertSeeIn('@hasil-laporan', '081234567890')
                ->assertSeeIn('@hasil-laporan', 'Menunggu Verifikasi');
        });
    } finally {
        @unlink($foto);
    }

    $laporan = LaporanBanjir::where('kode_pelacakan', $kode)->with('foto')->firstOrFail();
    expect($laporan->wilayah_id)->toBe($tirto->id)
        ->and($laporan->sumber_lokasi)->toBe('pin')
        ->and($laporan->severity)->toBe('sedang')
        ->and($laporan->jumlah_warga)->toBe(15)
        ->and($laporan->nama_pelapor)->toBe('Budi Dusk')
        ->and($laporan->foto)->toHaveCount(1);
});

test('dusk halaman cuaca, donasi, dan edukasi tampil (FE saja)', function () {
    /** @var DuskTestCase $dusk */
    $dusk = $this;
    $dusk->browse(function (Browser $browser) {
        foreach (['/cuaca' => '.pg-cuaca', '/donasi' => '.pg-donasi', '/edukasi' => '.pg-edukasi'] as $url => $selector) {
            $browser->visit($url)->assertPresent($selector);
        }
    });
});
