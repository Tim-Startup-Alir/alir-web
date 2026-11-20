<?php

use App\Models\KomunitasRelawan;
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

test('admin sees confidence levels, rationale, configurable thresholds and confidence filter', function () {
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
    $dusk->browse(function (Browser $browser) use ($admin, $rendah, $sedang, $tinggi) {
        $browser->loginAs($admin)->visit("/admin/peta-laporan?laporan={$rendah->id}")
            ->waitFor('.leaflet-container', 15)->waitForText('Dasar penilaian', 20)
            ->assertSee('Rendah')->assertSee('1 pelapor independen');

        $browser->visit("/admin/peta-laporan?laporan={$sedang->id}")
            ->waitForText('Dasar penilaian', 20)->assertSee('Sedang')->assertSee('2 pelapor independen');

        $browser->visit("/admin/peta-laporan?laporan={$tinggi->id}")
            ->waitForText('Dasar penilaian', 20)->assertSee('Tinggi')->assertSee('3 pelapor independen')
            ->assertSee('Aturan penilaian')->assertSee('minimal 3 pelapor independen')
            ->assertSee('minimal 2 pelapor independen');

        $browser->script("const filter = document.querySelector('#filter-kepercayaan'); [...filter.options].forEach(option => option.selected = option.value === 'rendah'); filter.dispatchEvent(new Event('change')); ");
        $browser->waitForText('1 titik laporan ditampilkan.', 15);
        expect((int) ($browser->script('return document.querySelectorAll(".leaflet-marker-icon").length;')[0] ?? -1))->toBe(1);
    });
});
