<?php

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\KonfirmasiLapangan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\KepercayaanLaporanService;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($komunitas) => $komunitas->user?->update(['wajib_ganti_sandi' => false]));
});

function laporanKepercayaan(string $wa = '081234567890', float $latitude = -6.8928121, float $longitude = 109.6515333, array $attributes = []): LaporanBanjir
{
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    return LaporanBanjirFactory::new()->koordinat($latitude, $longitude)->nomorWa($wa)->create(['wilayah_id' => $wilayah->id] + $attributes);
}

function konfirmasiKepercayaan(LaporanBanjir $laporan, string $hasil, Carbon $waktu): KonfirmasiLapangan
{
    $komunitas = KomunitasRelawan::firstOrFail();

    return KonfirmasiLapangan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'status' => 'dijawab',
        'hasil' => $hasil,
        'dijawab_pada' => $waktu,
    ]);
}

test('one two and three independent reporters map to low medium and high', function () {
    $low = laporanKepercayaan('081111111111');
    $medium = laporanKepercayaan('082222222222', -6.9028121, 109.6515333);
    LaporanBanjirFactory::new()->dekatDari($medium, 50)->nomorWa('083333333333')->create();
    $high = laporanKepercayaan('084444444444', -6.9128121, 109.6515333);
    LaporanBanjirFactory::new()->dekatDari($high, 50)->nomorWa('085555555555')->create();
    LaporanBanjirFactory::new()->dekatDari($high, 100)->nomorWa('086666666666')->create();
    $service = app(KepercayaanLaporanService::class);

    expect($service->hitung($low))->toBe('rendah')
        ->and($service->analisis($medium)['level'])->toBe('sedang')
        ->and($service->analisis($high)['level'])->toBe('tinggi');
});

test('same whatsapp in local and international formats counts once', function () {
    $laporan = laporanKepercayaan('081234567890');
    LaporanBanjirFactory::new()->dekatDari($laporan, 100)->nomorWa('+62 812-3456-7890')->create();
    LaporanBanjirFactory::new()->dekatDari($laporan, 120)->nomorWa('62 812 3456 7890')->create();

    expect($laporan->fresh()->jumlahPelaporIndependen())->toBe(1)
        ->and($laporan->fresh()->hitungKepercayaan())->toBe('rendah');
});

test('far old, rejected and merged reports are excluded from independent count', function () {
    $laporan = laporanKepercayaan('081111111111');
    LaporanBanjirFactory::new()->dekatDari($laporan, 501)->nomorWa('082222222222')->create();
    LaporanBanjirFactory::new()->dekatDari($laporan, 100)->nomorWa('083333333333')->create(['created_at' => now()->subHours(25)]);
    LaporanBanjirFactory::new()->dekatDari($laporan, 100)->nomorWa('084444444444')->statusLaporan('ditolak')->create();
    $duplikat = laporanKepercayaan('085555555555', -6.91, 109.70, [
        'laporan_banjir_induk_id' => $laporan->id,
        'status' => 'digabung',
        'created_at' => now()->subDays(10),
    ]);
    $service = app(KepercayaanLaporanService::class);

    expect($duplikat->fresh()->hitungKepercayaan())->toBe('rendah')
        ->and($service->jumlahPelaporIndependen($laporan->fresh()))->toBe(1)
        ->and($laporan->fresh()->hitungKepercayaan())->toBe('rendah');
});

test('positive confirmation raises confidence and negative confirmation is informational', function () {
    $laporan = laporanKepercayaan('081111111111');
    LaporanBanjirFactory::new()->dekatDari($laporan, 20)->nomorWa('082222222222')->create();
    LaporanBanjirFactory::new()->dekatDari($laporan, 40)->nomorWa('083333333333')->create();
    $waktu = now()->startOfSecond();
    konfirmasiKepercayaan($laporan, 'terkonfirmasi', $waktu->copy()->subHour());
    konfirmasiKepercayaan($laporan, 'tidak_terkonfirmasi', $waktu);
    KonfirmasiLapangan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => KomunitasRelawan::firstOrFail()->id,
        'status' => 'diminta',
        'diminta_pada' => $waktu->copy()->addMinute(),
    ]);

    expect($laporan->fresh()->hitungKepercayaan())->toBe('tinggi')
        ->and(app(KepercayaanLaporanService::class)->analisis($laporan->fresh())['konfirmasi']['hasil'])->toBe('tidak_terkonfirmasi');
});

test('negative confirmation keeps confidence based on independent report count', function () {
    $laporan = laporanKepercayaan('081111111111');
    LaporanBanjirFactory::new()->dekatDari($laporan, 20)->nomorWa('082222222222')->create();
    LaporanBanjirFactory::new()->dekatDari($laporan, 40)->nomorWa('083333333333')->create();
    konfirmasiKepercayaan($laporan, 'tidak_terkonfirmasi', now());

    expect($laporan->fresh()->hitungKepercayaan())->toBe('tinggi')
        ->and(app(KepercayaanLaporanService::class)->analisis($laporan->fresh())['alasan'])->toContain('Dibantah lapangan; tingkat tetap berdasarkan laporan independen.');
});

test('one positive confirmation raises a single independent report to high', function () {
    $laporan = laporanKepercayaan('081111111111');
    konfirmasiKepercayaan($laporan, 'terkonfirmasi', now());

    expect($laporan->fresh()->hitungKepercayaan())->toBe('tinggi');
});

test('runtime thresholds control the level', function () {
    $laporan = laporanKepercayaan('081111111111');
    config(['alir.kepercayaan.laporan_tinggi' => 1, 'alir.kepercayaan.laporan_sedang' => 1]);
    expect($laporan->fresh()->hitungKepercayaan())->toBe('tinggi');

    config(['alir.kepercayaan.laporan_tinggi' => 3, 'alir.kepercayaan.laporan_sedang' => 2]);
    expect($laporan->fresh()->hitungKepercayaan())->toBe('rendah');

    LaporanBanjirFactory::new()->dekatDari($laporan, 50)->nomorWa('082222222222')->create();
    config(['alir.kepercayaan.laporan_sedang' => 1]);
    expect($laporan->fresh()->hitungKepercayaan())->toBe('sedang');
});

test('new nearby report updates confidence and audit is written only when level changes', function () {
    $laporan = laporanKepercayaan('081111111111');
    expect($laporan->fresh()->tingkat_kepercayaan)->toBe('rendah');
    $countBefore = AuditLog::where('aksi', 'laporan.kepercayaan')->where('subjek_id', $laporan->id)->count();

    LaporanBanjirFactory::new()->dekatDari($laporan, 30)->nomorWa('082222222222')->create();
    $log = AuditLog::where('aksi', 'laporan.kepercayaan')->where('subjek_id', $laporan->id)->latest('id')->firstOrFail();
    $countAfterCreate = AuditLog::where('aksi', 'laporan.kepercayaan')->where('subjek_id', $laporan->id)->count();
    app(KepercayaanLaporanService::class)->hitungUlang($laporan->fresh());

    expect($laporan->fresh()->tingkat_kepercayaan)->toBe('sedang')
        ->and($countAfterCreate)->toBe($countBefore + 1)
        ->and($log->user_id)->toBeNull()
        ->and(AuditLog::where('aksi', 'laporan.kepercayaan')->where('subjek_id', $laporan->id)->count())->toBe($countAfterCreate);
});

test('moving a report recalculates neighbors at both old and new coordinates', function () {
    $laporan = laporanKepercayaan('081111111111');
    $tetangga = LaporanBanjirFactory::new()->dekatDari($laporan, 40)->nomorWa('082222222222')->create();
    expect($laporan->fresh()->tingkat_kepercayaan)->toBe('sedang');

    $tetangga->update(['latitude' => -6.88, 'longitude' => 109.68]);

    expect($laporan->fresh()->tingkat_kepercayaan)->toBe('rendah')
        ->and($tetangga->fresh()->tingkat_kepercayaan)->toBe('rendah');
});

test('unrelated report edits do not trigger confidence recalculation', function () {
    $laporan = laporanKepercayaan('081111111111');
    $before = AuditLog::where('aksi', 'laporan.kepercayaan')->where('subjek_id', $laporan->id)->count();

    $laporan->update(['alamat_singkat' => 'Alamat diperbarui']);

    expect(AuditLog::where('aksi', 'laporan.kepercayaan')->where('subjek_id', $laporan->id)->count())->toBe($before);
});

test('observer catches service errors and does not fail public report creation', function () {
    $this->mock(KepercayaanLaporanService::class, function ($mock) {
        $mock->shouldReceive('hitungUlang')->andThrow(new RuntimeException('service gagal'));
    });

    $laporan = laporanKepercayaan('081111111111');

    expect($laporan->exists)->toBeTrue();
});

test('around payload includes analysis and excludes reporter private fields', function () {
    $laporan = laporanKepercayaan('081234567890', attributes: [
        'nama_pelapor' => 'Nama Privat', 'setuju_dihubungi' => true,
    ]);
    $laporan->updateQuietly(['nomor_wa_pelapor' => '081234567890']);
    LaporanBanjirFactory::new()->dekatDari($laporan, 40)->nomorWa('081111111111')->create();
    LaporanBanjirFactory::new()->dekatDari($laporan, 80)->nomorWa('082222222222')->create();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $response = $this->actingAs($admin)->getJson(route('admin.peta-laporan.sekitar', $laporan))->assertOk();

    expect($response->json('laporan.kepercayaan.level'))->toBe('tinggi')
        ->and($response->json('laporan.kepercayaan.jumlah_pelapor_independen'))->toBe(3)
        ->and($response->json('laporan.kepercayaan.ambang.radius_m'))->toBe(500)
        ->and($response->json('laporan.kepercayaan.alasan'))->not->toBeEmpty()
        ->and(json_encode($response->json()))->not->toContain('Nama Privat', '081234567890', 'setuju_dihubungi');
    expect($queries)->toBeLessThanOrEqual(20);
});

test('confidence filter limits map markers and backfill command fills null levels', function () {
    $tinggi = laporanKepercayaan('081111111111');
    $rendah = laporanKepercayaan('082222222222', -6.9028121, 109.6515333);
    $tinggi->updateQuietly(['tingkat_kepercayaan' => 'tinggi']);
    $rendah->updateQuietly(['tingkat_kepercayaan' => null]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $this->actingAs($admin)->getJson(route('admin.peta-laporan.data', [
        'south' => -6.9, 'west' => 109.64, 'north' => -6.88, 'east' => 109.67, 'kepercayaan' => ['tinggi'],
    ]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $tinggi->id);

    $this->artisan('alir:hitung-kepercayaan')->assertSuccessful();
    expect($rendah->fresh()->tingkat_kepercayaan)->toBe('rendah');
    $tinggi->updateQuietly(['tingkat_kepercayaan' => 'rendah']);
    $this->artisan('alir:hitung-kepercayaan --semua')->assertSuccessful();
    expect($tinggi->fresh()->tingkat_kepercayaan)->toBe('rendah');
});
