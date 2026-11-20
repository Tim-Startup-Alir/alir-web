<?php

use App\Models\FotoLaporan;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Services\LaporanSekitarService;
use App\Support\Geo;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($komunitas) => $komunitas->user?->update(['wajib_ganti_sandi' => false]));
});

function petaLaporan(float $lat, float $lng, array $attributes = []): LaporanBanjir
{
    return LaporanBanjirFactory::new()->koordinat($lat, $lng)->create($attributes);
}

test('admin pages and json routes enforce role and validate input', function () {
    $laporan = petaLaporan(-6.8928121, 109.6515333);
    $foto = FotoLaporan::create(['laporan_banjir_id' => $laporan->id, 'foto_path' => 'peta/test.jpg', 'urutan' => 1]);
    $community = KomunitasRelawan::firstOrFail()->user;
    $this->get(route('admin.peta-laporan.index'))->assertRedirect(route('login'));
    $this->get(route('admin.peta-laporan.index', ['laporan' => $laporan->id]))->assertRedirect(route('login'));
    $this->actingAs($community)->get(route('admin.peta-laporan.index'))->assertForbidden();
    $this->actingAs($community)->get(route('admin.peta-laporan.data', ['south' => -6.9, 'west' => 109.64, 'north' => -6.88, 'east' => 109.67]))->assertForbidden();
    $this->actingAs($community)->get(route('admin.peta-laporan.sekitar', $laporan))->assertForbidden();
    $this->actingAs($community)->get(route('admin.laporan.foto', [$laporan, $foto]))->assertForbidden();

    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $this->actingAs($admin)->get(route('admin.peta-laporan.index', ['laporan' => $laporan->id]))->assertOk();
    $this->getJson(route('admin.peta-laporan.data', ['south' => -6.89, 'west' => 109.64, 'north' => -6.88, 'east' => 109.67, 'status' => ['tidak_valid']]))->assertUnprocessable();
    $this->getJson(route('admin.peta-laporan.data', ['south' => -6.8, 'west' => 109.6, 'north' => -6.9, 'east' => 109.7]))->assertUnprocessable();
});

test('marker query filters bbox and reports truncation', function () {
    $inside = petaLaporan(-6.892, 109.652);
    petaLaporan(-6.7, 109.8);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    config(['alir.peta.batas_marker' => 1]);

    $this->actingAs($admin)->getJson(route('admin.peta-laporan.data', [
        'south' => -6.9, 'west' => 109.64, 'north' => -6.88, 'east' => 109.67,
    ]))->assertOk()->assertJsonPath('data.0.id', $inside->id)->assertJsonPath('terpotong', false);

    petaLaporan(-6.891, 109.653);
    $this->getJson(route('admin.peta-laporan.data', [
        'south' => -6.9, 'west' => 109.64, 'north' => -6.88, 'east' => 109.67,
    ]))->assertOk()->assertJsonPath('terpotong', true);
});

test('nearby reports use 500m and symmetric 24 hour windows and preserve status', function () {
    $lat = -6.8928121;
    $lng = 109.6515333;
    $now = now()->startOfSecond();
    $target = petaLaporan($lat, $lng, ['created_at' => $now, 'updated_at' => $now]);
    $near = LaporanBanjirFactory::new()->dekatDari($target, 499)->statusLaporan('ditolak')->create(['created_at' => $now->copy()->subHours(23)->subMinutes(59)]);
    $edge = LaporanBanjirFactory::new()->dekatDari($target, 500)->create(['created_at' => $now]);
    $joined = LaporanBanjirFactory::new()->dekatDari($target, 100)->statusLaporan('digabung')->create(['created_at' => $now->copy()->addHours(24)]);
    $far = LaporanBanjirFactory::new()->dekatDari($target, 501)->create(['created_at' => $now]);
    $old = LaporanBanjirFactory::new()->dekatDari($target, 50)->create(['created_at' => $now->copy()->subHours(24)->subMinute()]);
    $self = $target;
    FotoLaporan::create(['laporan_banjir_id' => $near->id, 'foto_path' => 'peta/near.jpg', 'urutan' => 1]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    expect(Geo::jarakMeter($lat, $lng, (float) $near->fresh()->latitude, (float) $near->fresh()->longitude))->toBeLessThanOrEqual(500);
    expect(Geo::jarakMeter($lat, $lng, (float) $far->latitude, (float) $far->longitude))->toBeGreaterThan(500);
    $response = $this->actingAs($admin)->getJson(route('admin.peta-laporan.sekitar', $target))->assertOk();
    $ids = collect($response->json('sekitar'))->pluck('id')->all();
    expect($ids)->toContain($near->id, $edge->id, $joined->id)->not->toContain($far->id, $old->id, $self->id);
    expect($response->json('sekitar.0.jarak_m'))->toBeLessThanOrEqual($response->json('sekitar.1.jarak_m'));
    expect(collect($response->json('sekitar'))->firstWhere('id', $near->id)['status'])->toBe('ditolak');
    expect($response->json())->not->toHaveKey('nomor_wa_pelapor')->not->toHaveKey('nama_pelapor')->not->toHaveKey('setuju_dihubungi');
});

test('nearby service eager loads photos and wilayah in bounded queries', function () {
    $target = petaLaporan(-6.892, 109.652);
    $near = LaporanBanjirFactory::new()->dekatDari($target, 20)->create();
    foreach ([$target, $near] as $report) {
        FotoLaporan::create(['laporan_banjir_id' => $report->id, 'foto_path' => 'peta/query.jpg', 'urutan' => 1]);
    }
    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $results = app(LaporanSekitarService::class)->cariSekitar($target);

    expect($results)->toHaveCount(1)->and($results->first()->relationLoaded('foto'))->toBeTrue()->and($results->first()->relationLoaded('wilayah'))->toBeTrue();
    expect($queries)->toBe(3);
});

test('private photos are scoped to their report and missing files return 404', function () {
    Storage::fake('local');
    Storage::disk('local')->put('peta/a.jpg', 'image');
    $a = petaLaporan(-6.892, 109.652);
    $b = petaLaporan(-6.891, 109.653);
    $photo = FotoLaporan::create(['laporan_banjir_id' => $a->id, 'foto_path' => 'peta/a.jpg', 'urutan' => 1]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $this->actingAs($admin)->get(route('admin.laporan.foto', [$a, $photo]))->assertOk();
    $this->get(route('admin.laporan.foto', [$b, $photo]))->assertNotFound();
    $photo->update(['foto_path' => 'peta/missing.jpg']);
    $this->get(route('admin.laporan.foto', [$a, $photo]))->assertNotFound();
});

test('report text remains escaped on blade and private values never enter resources', function () {
    $laporan = petaLaporan(-6.892, 109.652, ['alamat_singkat' => '<script>alert(1)</script>', 'nama_pelapor' => 'Rahasia', 'nomor_wa_pelapor' => '081234567890', 'setuju_dihubungi' => true]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $this->actingAs($admin)->get(route('admin.peta-laporan.index'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    $json = $this->getJson(route('admin.peta-laporan.sekitar', $laporan))->assertOk();
    expect(json_encode($json->json()))->not->toContain('Rahasia')->not->toContain('081234567890')->not->toContain('setuju_dihubungi');
});
