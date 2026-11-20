<?php

use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Models\Wilayah;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($komunitas) => $komunitas->user?->update(['wajib_ganti_sandi' => false]));
});

test('community can submit private field proof and admin sees refreshed confidence and confirmation', function () {
    $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
    $dega = Wilayah::where('nama_kelurahan', 'Degayu')->firstOrFail();
    $laporan = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $tirto->id,
        'latitude' => $tirto->latitude,
        'longitude' => $tirto->longitude,
        'nama_pelapor' => 'Data Rahasia Pelapor',
        'nomor_wa_pelapor' => '081299991111',
    ]);
    $laporanJauh = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $dega->id,
        'latitude' => -6.82,
        'longitude' => 109.80,
    ]);
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $temporaryPng = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alir-konfirmasi-'.bin2hex(random_bytes(6)).'.png';
    file_put_contents($temporaryPng, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ic0AAAAASUVORK5CYII='));

    try {
        /** @var DuskTestCase $dusk */
        $dusk = $this;
        $dusk->browse(function (Browser $browser) use ($komunitas, $admin, $laporan, $laporanJauh, $temporaryPng) {
            $browser->loginAs($komunitas->user)
                ->visit(route('komunitas.konfirmasi.show', $laporanJauh))
                ->assertSee('403')
                ->visit(route('komunitas.konfirmasi.index'))
                ->waitForText('Diminta Tim Startup ALIR')
                ->assertSee($laporan->alamat_singkat)
                ->click("@konfirmasi-buka-{$laporan->id}")
                ->waitFor('@form-konfirmasi')
                ->assertSee('Foto bukti');

            $browser->radio('hasil', 'sesuai')->type('catatan', 'Kondisi terpantau aman untuk dicatat.')
                ->script("document.querySelector('#foto-bukti').removeAttribute('required');");
            $browser->click('@submit-konfirmasi')
                ->acceptDialog()
                ->waitForText('Foto bukti wajib diunggah.')
                ->assertInputValue('catatan', 'Kondisi terpantau aman untuk dicatat.')
                ->attach('foto', $temporaryPng)
                ->click('@submit-konfirmasi')
                ->acceptDialog()
                ->waitForText('Konfirmasi lapangan berhasil dikirim.')
                ->assertSee('Tinggi')
                ->assertDontSee('Data Rahasia Pelapor')
                ->assertDontSee('081299991111');

            $browser->loginAs($admin)
                ->visit(route('admin.peta-laporan.index', ['laporan' => $laporan->id]))
                ->waitForText('Konfirmasi lapangan', 20)
                ->assertSee('sesuai')
                ->assertSee($komunitas->nama)
                ->assertSee('Tinggi')
                ->assertDontSee('Data Rahasia Pelapor')
                ->assertDontSee('081299991111');
        });
    } finally {
        if (is_file($temporaryPng)) {
            unlink($temporaryPng);
        }
    }

    expect(LaporanBanjir::findOrFail($laporan->id)->tingkat_kepercayaan)->toBe('tinggi');
});
