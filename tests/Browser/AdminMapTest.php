<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\User;
use App\Models\KomunitasRelawan;
use App\Models\Wilayah;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;

class AdminMapTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
        KomunitasRelawan::query()->with('user')->get()->each(fn ($komunitas) => $komunitas->user?->update(['wajib_ganti_sandi' => false]));
    }

    /** @test */
    public function admin_can_view_map_and_load_report_details()
    {
        $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
        $laporan = LaporanBanjirFactory::new()->create([
            'wilayah_id' => $wilayah->id,
            'latitude' => $wilayah->latitude,
            'longitude' => $wilayah->longitude,
        ]);
        $admin = User::where('email', 'admin@alir.test')->firstOrFail();

        $this->browse(function (Browser $browser) use ($admin, $laporan) {
            $browser->loginAs($admin)
                    ->visit('/admin/peta-laporan?laporan='.$laporan->id)
                    ->waitFor('.leaflet-container', 15)
                    ->assertSee('Peta Laporan')
                    ->waitForText('1 laporan independen', 20)
                    ->assertPresent('#detail-confidence .tag')
                    ->assertVisible('#detail-content')
                    ->assertPresent('#detail-photo');
        });
    }
}
