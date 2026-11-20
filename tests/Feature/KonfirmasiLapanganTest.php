<?php

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\KonfirmasiLapangan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\KepercayaanLaporanService;
use App\Services\KonfirmasiLapanganService;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($komunitas) => $komunitas->user?->update(['wajib_ganti_sandi' => false]));
});

function buatLaporanKonfirmasi(array $attributes = []): LaporanBanjir
{
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    return LaporanBanjirFactory::new()->koordinat((float) $wilayah->latitude, (float) $wilayah->longitude)
        ->create(['wilayah_id' => $wilayah->id] + $attributes);
}

function fotoKonfirmasiPng(): UploadedFile
{
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ic0AAAAASUVORK5CYII=');

    return UploadedFile::fake()->createWithContent('bukti.png', $png);
}

function komunitasKonfirmasi(string $kelurahan = 'Tirto'): KomunitasRelawan
{
    return KomunitasRelawan::where('nama', "Komunitas Relawan {$kelurahan}")->firstOrFail();
}

test('community access is limited by role, active state, assignment and nearby post', function () {
    $laporan = buatLaporanKonfirmasi();
    $tirto = komunitasKonfirmasi();
    $jauh = komunitasKonfirmasi('Degayu');
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    $this->get(route('komunitas.konfirmasi.index'))->assertRedirect(route('login'));
    $this->actingAs($admin)->get(route('komunitas.konfirmasi.show', $laporan))->assertForbidden();
    $this->actingAs($tirto->user)->get(route('komunitas.konfirmasi.show', $laporan))->assertOk();
    $this->actingAs($jauh->user)->get(route('komunitas.konfirmasi.show', $laporan))->assertForbidden();

    expect(app(KonfirmasiLapanganService::class)->bolehMengonfirmasi($tirto, $laporan))->toBeTrue()
        ->and(app(KonfirmasiLapanganService::class)->bolehMengonfirmasi($jauh, $laporan))->toBeFalse();

    $jauh->posko()->firstOrFail()->update([
        'latitude' => $laporan->latitude,
        'longitude' => $laporan->longitude,
    ]);
    expect(app(KonfirmasiLapanganService::class)->bolehMengonfirmasi($jauh->fresh(), $laporan))->toBeTrue();

    $jauh->user->update(['is_active' => false]);
    expect(app(KonfirmasiLapanganService::class)->bolehMengonfirmasi($jauh->fresh(), $laporan))->toBeFalse();
});

test('a live assignment or a pending request grants access and finished statuses do not', function () {
    $laporan = buatLaporanKonfirmasi();
    $jauh = komunitasKonfirmasi('Degayu');
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $jauh->id,
        'ditugaskan_oleh' => $admin->id,
        'stage' => 'menunggu',
    ]);
    $service = app(KonfirmasiLapanganService::class);

    expect($service->bolehMengonfirmasi($jauh, $laporan->fresh()))->toBeTrue();

    $laporan->update(['status' => 'ditolak']);
    expect($service->bolehMengonfirmasi($jauh, $laporan->fresh()))->toBeFalse();
});

test('successful confirmation stores private proof, audit and updated confidence for each result', function (string $hasil, string $level) {
    Storage::fake('local');
    $laporan = buatLaporanKonfirmasi();
    $tirto = komunitasKonfirmasi();

    $this->actingAs($tirto->user)->post(route('komunitas.konfirmasi.store', $laporan), [
        'hasil' => $hasil,
        'foto' => fotoKonfirmasiPng(),
        'catatan' => 'Pemeriksaan lapangan.',
    ])->assertRedirect(route('komunitas.konfirmasi.index'))
        ->assertSessionHas('success')
        ->assertSessionHas('kepercayaan_terbaru.level', $level);

    $konfirmasi = KonfirmasiLapangan::where('laporan_banjir_id', $laporan->id)->firstOrFail();
    expect($konfirmasi->status)->toBe('dijawab')
        ->and($konfirmasi->hasil)->toBe($hasil)
        ->and(Storage::disk('local')->exists($konfirmasi->foto_path))->toBeTrue()
        ->and($laporan->fresh()->tingkat_kepercayaan)->toBe($level)
        ->and(AuditLog::where('aksi', 'konfirmasi.jawab')->where('subjek_id', $konfirmasi->id)->exists())->toBeTrue()
        ->and($konfirmasi->foto_path)->not->toContain('..');
})->with([
    ['sesuai', 'tinggi'],
    ['tidak_sesuai', 'rendah'],
    ['tidak_ditemukan', 'rendah'],
]);

test('pending request is answered in place and duplicate submissions are rejected', function () {
    Storage::fake('local');
    $laporan = buatLaporanKonfirmasi();
    $tirto = komunitasKonfirmasi();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $pending = app(KonfirmasiLapanganService::class)->mintaKonfirmasi($laporan, $tirto, $admin);

    $this->actingAs($tirto->user)->post(route('komunitas.konfirmasi.store', $laporan), [
        'hasil' => 'sesuai', 'foto' => fotoKonfirmasiPng(), 'catatan' => 'Sudah diperiksa.',
    ])->assertRedirect(route('komunitas.konfirmasi.index'));

    expect(KonfirmasiLapangan::where('laporan_banjir_id', $laporan->id)->count())->toBe(1)
        ->and($pending->fresh()->status)->toBe('dijawab');

    $this->post(route('komunitas.konfirmasi.store', $laporan), [
        'hasil' => 'sesuai', 'foto' => fotoKonfirmasiPng(),
    ])->assertForbidden();
    expect(KonfirmasiLapangan::where('laporan_banjir_id', $laporan->id)->count())->toBe(1);
});

test('validation rejects missing, wrong type, oversized proof and invalid result while retaining text input', function () {
    Storage::fake('local');
    config(['alir.konfirmasi.foto_maks_kb' => 16]);
    $laporan = buatLaporanKonfirmasi();
    $tirto = komunitasKonfirmasi();

    $this->actingAs($tirto->user)->from(route('komunitas.konfirmasi.show', $laporan))
        ->post(route('komunitas.konfirmasi.store', $laporan), ['hasil' => 'sesuai', 'catatan' => 'Isian tetap ada.'])
        ->assertSessionHasErrors('foto')->assertSessionHasInput('catatan', 'Isian tetap ada.');

    $this->post(route('komunitas.konfirmasi.store', $laporan), [
        'hasil' => 'banjir', 'foto' => UploadedFile::fake()->create('bukti.gif', 1, 'image/gif'),
    ])->assertSessionHasErrors(['hasil', 'foto']);

    $this->post(route('komunitas.konfirmasi.store', $laporan), [
        'hasil' => 'sesuai', 'foto' => UploadedFile::fake()->create('besar.jpg', 20, 'image/jpeg'),
    ])->assertSessionHasErrors('foto');

    expect(KonfirmasiLapangan::count())->toBe(0);
});

test('failed confidence update rolls back confirmation and audit and deletes uploaded proof', function () {
    Storage::fake('local');
    $laporan = buatLaporanKonfirmasi();
    $tirto = komunitasKonfirmasi();
    $foto = fotoKonfirmasiPng();
    $this->mock(KepercayaanLaporanService::class, function ($mock) {
        $mock->shouldReceive('hitungUlang')->andThrow(new RuntimeException('confidence failure'));
    });

    expect(fn () => app(KonfirmasiLapanganService::class)->kirim($tirto, $laporan, 'sesuai', $foto, null, $tirto->user))
        ->toThrow(RuntimeException::class);

    expect(KonfirmasiLapangan::count())->toBe(0)
        ->and(AuditLog::where('aksi', 'konfirmasi.jawab')->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles('konfirmasi-lapangan'))->toBeEmpty();
});

test('community detail and admin payload omit reporter personal data and expose safe confirmation fields', function () {
    $laporan = buatLaporanKonfirmasi(['nama_pelapor' => 'Nama Rahasia', 'nomor_wa_pelapor' => '081299991111', 'setuju_dihubungi' => true]);
    $tirto = komunitasKonfirmasi();
    $konfirmasi = KonfirmasiLapangan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $tirto->id,
        'status' => 'dijawab',
        'hasil' => 'sesuai',
        'foto_path' => 'konfirmasi-lapangan/bukti.png',
        'catatan' => '<script>alert(1)</script>',
        'dijawab_pada' => now(),
    ]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $this->actingAs($tirto->user)->get(route('komunitas.konfirmasi.index'))->assertOk()
        ->assertDontSee('Nama Rahasia')->assertDontSee('081299991111')->assertDontSee('setuju_dihubungi');
    $this->actingAs($admin)->getJson(route('admin.peta-laporan.sekitar', $laporan))->assertOk()
        ->assertJsonPath('laporan.konfirmasi.0.hasil', 'sesuai')
        ->assertJsonPath('laporan.konfirmasi.0.komunitas', $tirto->nama)
        ->assertJsonMissingPath('laporan.konfirmasi.0.nomor_wa_pelapor')
        ->assertDontSee('Nama Rahasia')->assertDontSee('081299991111')->assertDontSee('setuju_dihubungi');

    expect($konfirmasi->exists)->toBeTrue();
});

test('proof route enforces parent scope, role, and private storage', function () {
    Storage::fake('local');
    Storage::disk('local')->put('konfirmasi-lapangan/proof.png', 'private proof');
    $laporan = buatLaporanKonfirmasi();
    $laporanLain = buatLaporanKonfirmasi();
    $tirto = komunitasKonfirmasi();
    $konfirmasi = KonfirmasiLapangan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $tirto->id,
        'status' => 'dijawab', 'hasil' => 'sesuai',
        'foto_path' => 'konfirmasi-lapangan/proof.png', 'dijawab_pada' => now(),
    ]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    $this->actingAs($admin)->get(route('admin.konfirmasi.foto', [$laporan, $konfirmasi]))->assertOk();
    $this->get(route('admin.konfirmasi.foto', [$laporanLain, $konfirmasi]))->assertNotFound();
    $this->actingAs($tirto->user)->get(route('admin.konfirmasi.foto', [$laporan, $konfirmasi]))->assertForbidden();
});

test('laporan list contains only eligible pending, assigned, and nearby rows', function () {
    $laporan = buatLaporanKonfirmasi();
    $jauh = komunitasKonfirmasi('Degayu');
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $jauh->id,
        'ditugaskan_oleh' => $admin->id,
        'stage' => 'menunggu',
    ]);
    $jauh->posko()->firstOrFail()->update([
        'latitude' => -6.99,
        'longitude' => 109.75,
    ]);

    $rows = app(KonfirmasiLapanganService::class)->laporanUntuk($jauh->fresh());

    expect($rows->pluck('id')->all())->toContain($laporan->id);
});
