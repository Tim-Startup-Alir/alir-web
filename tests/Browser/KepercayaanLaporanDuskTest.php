<?php

use App\Models\KomunitasRelawan;
use App\Models\KonfirmasiLapangan;
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

test('same whatsapp does not raise confidence and negative confirmation does not lower it', function () {
    $ambangTinggi = (int) config('alir.kepercayaan.laporan_tinggi');
    $ambangSedang = (int) config('alir.kepercayaan.laporan_sedang');
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
    $same = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $wilayah->id, 'latitude' => -6.8928121, 'longitude' => 109.6515333,
        'nomor_wa_pelapor' => '081234567890',
    ]);
    LaporanBanjirFactory::new()->dekatDari($same, 30)->create(['wilayah_id' => $wilayah->id, 'nomor_wa_pelapor' => '+62 812-3456-7890']);
    LaporanBanjirFactory::new()->dekatDari($same, 60)->create(['wilayah_id' => $wilayah->id, 'nomor_wa_pelapor' => '62 812 3456 7890']);

    $high = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $wilayah->id, 'latitude' => -6.8928121, 'longitude' => 109.6705333,
        'nomor_wa_pelapor' => '081111111111',
    ]);
    LaporanBanjirFactory::new()->dekatDari($high, 30)->create(['wilayah_id' => $wilayah->id, 'nomor_wa_pelapor' => '082222222222']);
    LaporanBanjirFactory::new()->dekatDari($high, 60)->create(['wilayah_id' => $wilayah->id, 'nomor_wa_pelapor' => '083333333333']);
    KonfirmasiLapangan::create([
        'laporan_banjir_id' => $high->id,
        'komunitas_relawan_id' => KomunitasRelawan::firstOrFail()->id,
        'status' => 'dijawab', 'hasil' => 'tidak_terkonfirmasi', 'dijawab_pada' => now(),
    ]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    /** @var DuskTestCase $dusk */
    $dusk = $this;
    $dusk->browse(function (Browser $browser) use ($admin, $same, $high, $ambangTinggi, $ambangSedang) {
        $browser->loginAs($admin)->visit("/admin/peta-laporan?laporan={$same->id}")
            ->waitForText('Tingkat kepercayaan', 20)
            ->assertSee(match (true) { 1 >= $ambangTinggi => 'Tinggi', 1 >= $ambangSedang => 'Sedang', default => 'Rendah' })
            ->assertSee('1 laporan independen');
        $browser->visit("/admin/peta-laporan?laporan={$high->id}")
            ->waitForText('Dibantah lapangan', 20)
            ->assertSee(match (true) { 3 >= $ambangTinggi => 'Tinggi', 3 >= $ambangSedang => 'Sedang', default => 'Rendah' })
            ->assertSee('3 laporan independen');
    });
});

test('admin sees confidence levels and thresholds from config', function () {
    $ambangTinggi = (int) config('alir.kepercayaan.laporan_tinggi');
    $ambangSedang = (int) config('alir.kepercayaan.laporan_sedang');
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
    $rendah = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $wilayah->id, 'latitude' => -6.8928121, 'longitude' => 109.6515333,
    ]);
    $sedang = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $wilayah->id, 'latitude' => -6.8928121, 'longitude' => 109.6605333,
    ]);
    LaporanBanjirFactory::new()->dekatDari($sedang, 60)->create(['wilayah_id' => $wilayah->id]);
    $tinggi = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $wilayah->id, 'latitude' => -6.8928121, 'longitude' => 109.6695333,
    ]);
    LaporanBanjirFactory::new()->dekatDari($tinggi, 60)->create(['wilayah_id' => $wilayah->id]);
    LaporanBanjirFactory::new()->dekatDari($tinggi, 90)->create(['wilayah_id' => $wilayah->id]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    /** @var DuskTestCase $dusk */
    $dusk = $this;
    $dusk->browse(function (Browser $browser) use ($admin, $rendah, $sedang, $tinggi, $ambangTinggi, $ambangSedang) {
        $browser->loginAs($admin)->visit("/admin/peta-laporan?laporan={$rendah->id}")
            ->waitFor('.leaflet-container', 15)->waitForText('Tingkat kepercayaan', 20)
            ->assertSee('Rendah')->assertSee('1 laporan independen');

        $browser->visit("/admin/peta-laporan?laporan={$sedang->id}")
            ->waitForText('Tingkat kepercayaan', 20)
            ->assertSee(match (true) { 2 >= $ambangTinggi => 'Tinggi', 2 >= $ambangSedang => 'Sedang', default => 'Rendah' })
            ->assertSee('2 laporan independen');

        $browser->visit("/admin/peta-laporan?laporan={$tinggi->id}")
            ->waitForText('Tingkat kepercayaan', 20)
            ->assertSee(match (true) { 3 >= $ambangTinggi => 'Tinggi', 3 >= $ambangSedang => 'Sedang', default => 'Rendah' })
            ->assertSee('3 laporan independen')
            ->assertSee('Aturan penilaian')->assertSee("{$ambangTinggi} pelapor berbeda atau lebih")
            ->assertSee("{$ambangSedang} pelapor berbeda")
            ->assertSee('Pelapor berbeda = nomor WhatsApp berbeda')
            ->assertSee('Ada foto atau tidak tidak mengubah tingkat kepercayaan')
            ->assertSee('Dibantah lapangan')
            ->assertDontSee('.env')->assertDontSee('Haversine');

        $browser->script('const input = document.querySelector("#filter-kepercayaan input[value=rendah]"); input.checked = true; input.dispatchEvent(new Event("change", {bubbles:true}));');
        $browser->waitForText('1 titik laporan ditampilkan.', 15);
        expect((int) ($browser->script('return document.querySelectorAll(".leaflet-marker-icon").length;')[0] ?? -1))->toBe(1);
    });
});
