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
    KomunitasRelawan::query()->with('user')->get()->each(fn ($komunitas) => $komunitas->user?->update(['wajib_ganti_sandi' => false]));
});

test('pbi-007 dusk map shows report detail, nearby list, filters and escaped address', function () {
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
    $base = LaporanBanjirFactory::new()->create(['wilayah_id' => $wilayah->id, 'latitude' => $wilayah->latitude, 'longitude' => $wilayah->longitude, 'alamat_singkat' => '<script>alert(1)</script>']);
    $valid = LaporanBanjirFactory::new()->dekatDari($base, 100)->create(['wilayah_id' => $wilayah->id]);
    LaporanBanjirFactory::new()->dekatDari($base, 700)->create(['wilayah_id' => $wilayah->id]);
    LaporanBanjirFactory::new()->dekatDari($base, 80)->create(['wilayah_id' => $wilayah->id, 'created_at' => now()->subHours(30)]);
    Storage::disk('local')->put('dusk/report.jpg', 'image');
    FotoLaporan::create(['laporan_banjir_id' => $base->id, 'foto_path' => 'dusk/report.jpg', 'urutan' => 1]);
    FotoLaporan::create(['laporan_banjir_id' => $valid->id, 'foto_path' => 'dusk/report.jpg', 'urutan' => 1]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $community = KomunitasRelawan::firstOrFail()->user;

    /** @var DuskTestCase $dusk */
    $dusk = $this;
    $dusk->browse(function (Browser $browser) use ($admin, $community, $base, $valid) {
        $browser->loginAs($community)->visit('/admin/peta-laporan')->assertSee('403');
        $browser->loginAs($admin)->visit("/admin/peta-laporan?laporan={$base->id}")
            ->waitFor('.leaflet-container', 15)->waitFor('.leaflet-marker-icon', 20)
            ->waitForText('<script>alert(1)</script>', 15)
            ->assertSee('Laporan sekitar')->assertSee('100 m')->waitFor('.peta-item')
            ->assertDontSee('700 m')->assertDontSee('1,800 menit')
            ->assertPresent('img.peta-photo');
        expect((int) ($browser->script('return document.querySelectorAll(".peta-item").length;')[0] ?? -1))->toBe(1);
        expect((string) ($browser->script('return document.querySelector(".peta-item")?.dataset.laporanId;')[0] ?? ''))->toBe((string) $valid->id);
        $browser->script('const input = document.querySelector("#filter-status input[value=ditolak]"); input.checked = true; input.dispatchEvent(new Event("change", {bubbles:true}));');
        $browser->waitForText('Tidak ada laporan pada area peta ini.', 15)->assertPresent('img.peta-photo');
        expect((int) ($browser->script('return document.querySelectorAll(".peta-item").length;')[0] ?? -1))->toBe(0);
    });

    expect(LaporanBanjir::find($valid->id))->not->toBeNull();
});
