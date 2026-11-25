<?php

use App\Http\Controllers\LaporanBanjirController;
use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Models\Wilayah;
use App\Notifications\LaporanBaruAdminNotification;
use App\Services\LaporanBanjirService;
use App\Services\TurnstileService;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// JPEG 1x1 piksel yang valid (tanpa butuh ekstensi GD)
const JPEG_KECIL = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAABP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL8A/9k=';

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($k) => $k->user?->update(['wajib_ganti_sandi' => false]));
    User::where('role', User::ROLE_ADMIN)->update(['wajib_ganti_sandi' => false]);

    config(['services.turnstile.secret_key' => 'rahasia-uji']);
    // hasil verifikasi Turnstile dapat diubah per test lewat $this->turnstileSukses
    $this->turnstileSukses = true;
    Http::fake([TurnstileService::URL_VERIFIKASI => fn () => Http::response($this->turnstileSukses
        ? ['success' => true]
        : ['success' => false, 'error-codes' => ['invalid-input-response']])]);
    Storage::fake('local');
    RateLimiter::clear('lapor-menit|127.0.0.1');
    RateLimiter::clear('lapor-jam|127.0.0.1');
});

function fotoJpeg(string $nama = 'banjir.jpg', int $tambahanByte = 0): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nama, base64_decode(JPEG_KECIL).str_repeat("\0", $tambahanByte));
}

function dataLaporan(array $ubah = []): array
{
    $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    return array_merge([
        'latitude' => (float) $tirto->latitude,
        'longitude' => (float) $tirto->longitude,
        'sumber_lokasi' => 'pin',
        'alamat_singkat' => 'Gang Melati RT 03, dekat mushola',
        'severity' => 'sedang',
        'jumlah_warga' => 12,
        'foto' => fotoJpeg(),
        'nomor_wa' => '0812 3456 7890',
        'nama_pelapor' => 'Budi',
        'catatan' => 'Ada lansia',
        'setuju_dihubungi' => '1',
        'cf-turnstile-response' => 'token-uji',
    ], $ubah);
}

test('pbi-004/005/006 laporan publik tersimpan dengan kode unik, foto privat, dan notifikasi admin', function () {
    Notification::fake();

    $res = $this->postJson(route('lapor.store'), dataLaporan(['sumber_lokasi' => 'gps']));

    $res->assertCreated()->assertJsonPath('redirect', route('lapor.diterima'));
    $kode = $res->json('kode');
    expect($kode)->toMatch(LaporanBanjirService::POLA_KODE);

    $laporan = LaporanBanjir::where('kode_pelacakan', $kode)->with(['foto', 'wilayah'])->firstOrFail();
    expect($laporan->status)->toBe('menunggu_verifikasi')
        ->and($laporan->wilayah->nama_kelurahan)->toBe('Tirto')
        ->and($laporan->sumber_lokasi)->toBe('gps')
        ->and($laporan->severity)->toBe('sedang')
        ->and($laporan->jumlah_warga)->toBe(12)
        ->and($laporan->nomor_wa_pelapor)->toBe('081234567890')
        ->and($laporan->setuju_dihubungi)->toBeTrue()
        ->and((float) $laporan->latitude)->toBe(-6.8928121)
        ->and($laporan->foto)->toHaveCount(1);

    // NFR-03: foto di disk privat, bukan public
    $path = $laporan->foto->first()->foto_path;
    expect($path)->toStartWith('foto-laporan/');
    Storage::disk('local')->assertExists($path);

    $res->assertSessionHas(LaporanBanjirController::SESSION_TERKIRIM, $laporan->id);
    expect(AuditLog::where('aksi', 'laporan.buat')->where('subjek_id', $laporan->id)->exists())->toBeTrue();

    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    Notification::assertSentTo($admin, LaporanBaruAdminNotification::class, fn ($n) => $n->laporan->is($laporan)
        && $n->toArray($admin)['url'] === route('admin.laporan.cari', ['kode' => $kode]));
    Notification::assertNotSentTo(KomunitasRelawan::firstOrFail()->user, LaporanBaruAdminNotification::class);
});

test('pbi-004 data wajib divalidasi dan tidak ada laporan tersimpan', function () {
    $this->postJson(route('lapor.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'latitude', 'longitude', 'sumber_lokasi', 'alamat_singkat', 'severity', 'jumlah_warga',
            'foto', 'nomor_wa', 'setuju_dihubungi', 'cf-turnstile-response',
        ]);

    expect(LaporanBanjir::count())->toBe(0);
});

test('pbi-004 format whatsapp, keparahan, dan jumlah warga divalidasi', function (array $ubah, string $field, string $pesan) {
    $this->postJson(route('lapor.store'), dataLaporan($ubah))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $pesan]);

    expect(LaporanBanjir::count())->toBe(0);
})->with([
    'wa huruf' => [['nomor_wa' => '08abc'], 'nomor_wa', 'Format nomor WhatsApp tidak valid'],
    'wa terlalu pendek' => [['nomor_wa' => '08123'], 'nomor_wa', 'Format nomor WhatsApp tidak valid'],
    'wa bukan seluler' => [['nomor_wa' => '0211234567'], 'nomor_wa', 'Format nomor WhatsApp tidak valid'],
    'keparahan asing' => [['severity' => 'parah'], 'severity', 'ringan, sedang, atau berat'],
    'warga nol' => [['jumlah_warga' => 0], 'jumlah_warga', 'minimal 1'],
    'tanpa persetujuan' => [['setuju_dihubungi' => '0'], 'setuju_dihubungi', 'Centang persetujuan'],
]);

test('pbi-004 nomor whatsapp +62 dinormalisasi menjadi 08', function () {
    $this->postJson(route('lapor.store'), dataLaporan(['nomor_wa' => '+62 812-3456-7890']))->assertCreated();

    expect(LaporanBanjir::firstOrFail()->nomor_wa_pelapor)->toBe('081234567890');
});

test('pbi-004 format dan ukuran foto divalidasi', function () {
    $this->postJson(route('lapor.store'), dataLaporan(['foto' => UploadedFile::fake()->createWithContent('laporan.pdf', '%PDF-1.4 palsu')]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['foto' => 'Format foto harus JPG, PNG, atau WEBP.']);

    $this->postJson(route('lapor.store'), dataLaporan(['foto' => fotoJpeg('besar.jpg', 6 * 1024 * 1024)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['foto' => 'Ukuran foto maksimal 5 MB.']);

    expect(LaporanBanjir::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('nfr-02 captcha yang gagal diverifikasi menolak laporan', function () {
    $this->turnstileSukses = false;

    $this->postJson(route('lapor.store'), dataLaporan())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cf-turnstile-response' => 'Verifikasi CAPTCHA gagal']);

    Http::assertSent(fn ($req) => $req->url() === TurnstileService::URL_VERIFIKASI
        && $req['secret'] === 'rahasia-uji' && $req['response'] === 'token-uji');
    expect(LaporanBanjir::count())->toBe(0);
});

test('nfr-02 captcha ditolak bila secret key belum dikonfigurasi', function () {
    config(['services.turnstile.secret_key' => null]);

    $this->postJson(route('lapor.store'), dataLaporan())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cf-turnstile-response']);
});

test('pbi-005 titik di luar wilayah cakupan ditolak dengan permintaan koreksi', function () {
    $this->postJson(route('lapor.store'), dataLaporan(['latitude' => -6.95, 'longitude' => 109.55]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude' => 'di luar wilayah cakupan ALIR']);

    expect(LaporanBanjir::count())->toBe(0);
});

test('pbi-005 wilayah nonaktif tidak menerima laporan', function () {
    Wilayah::query()->update(['is_aktif' => false]);

    $this->postJson(route('lapor.store'), dataLaporan())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude']);
});

test('pbi-005 cek lokasi mengembalikan kelurahan atau pesan koreksi', function () {
    $degayu = Wilayah::where('nama_kelurahan', 'Degayu')->firstOrFail();

    $this->postJson(route('lapor.cek-lokasi'), ['latitude' => $degayu->latitude, 'longitude' => $degayu->longitude])
        ->assertOk()->assertJson(['dalam_cakupan' => true, 'wilayah' => 'Degayu']);

    $this->postJson(route('lapor.cek-lokasi'), ['latitude' => -6.95, 'longitude' => 109.55])
        ->assertOk()->assertJson(['dalam_cakupan' => false, 'wilayah' => null])
        ->assertJsonPath('pesan', fn ($p) => str_contains($p, 'Geser pin'));

    $this->postJson(route('lapor.cek-lokasi'), ['latitude' => 'abc'])->assertUnprocessable();
});

test('nfr-02 pengiriman laporan dibatasi per ip', function () {
    config(['alir.laporan.batas_per_menit' => 2]);

    $this->postJson(route('lapor.store'), [])->assertUnprocessable();
    $this->postJson(route('lapor.store'), [])->assertUnprocessable();
    $this->postJson(route('lapor.store'), dataLaporan())
        ->assertTooManyRequests()
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'Terlalu banyak laporan'));

    expect(LaporanBanjir::count())->toBe(0);
});

test('pbi-006 halaman laporan diterima menampilkan kode hanya untuk pengirimnya', function () {
    $laporan = LaporanBanjirFactory::new()->create(['kode_pelacakan' => 'ALR-7K2M9P']);

    $this->get(route('lapor.diterima'))->assertRedirect(route('landing'));

    $this->withSession([LaporanBanjirController::SESSION_TERKIRIM => $laporan->id])
        ->get(route('lapor.diterima'))
        ->assertOk()
        ->assertSee('Laporan diterima.')
        ->assertSee('ALR-7K2M9P')
        ->assertSee('Menunggu Verifikasi')
        ->assertDontSee($laporan->nomor_wa_pelapor);
});

test('pbi-006 kode laporan dibuat unik dengan format ALR-XXXXXX', function () {
    $service = app(LaporanBanjirService::class);
    $kode = collect(range(1, 200))->map(fn () => $service->buatKode());

    expect($kode->unique())->toHaveCount(200);
    $kode->each(fn ($k) => expect($k)->toMatch(LaporanBanjirService::POLA_KODE));
});

test('halaman publik beranda, placeholder, dan konfigurasi form tersedia', function () {
    $this->get(route('landing'))->assertOk()
        ->assertSee('Lapor Banjir')
        ->assertSee('id="lapor-config"', false)
        ->assertSee('challenges.cloudflare.com/turnstile', false);

    foreach (['cuaca' => 'Cuaca', 'donasi' => 'Donasi', 'edukasi' => 'Edukasi'] as $route => $judul) {
        $this->get(route($route))->assertOk()->assertSee($judul)->assertSee('Halaman ini belum tersedia');
    }
});

test('pbi-006 admin mencari laporan berdasarkan kode', function () {
    $laporan = LaporanBanjirFactory::new()->create([
        'kode_pelacakan' => 'ALR-7K2M9P', 'alamat_singkat' => '<b>Gang Melati</b>', 'nomor_wa_pelapor' => '081234567890',
    ]);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::firstOrFail()->user;

    $this->get(route('admin.laporan.cari'))->assertRedirect(route('login'));
    $this->actingAs($komunitas)->get(route('admin.laporan.cari', ['kode' => 'ALR-7K2M9P']))->assertForbidden();

    $admin->notify(new LaporanBaruAdminNotification($laporan));
    expect($admin->unreadNotifications()->count())->toBe(1);

    // huruf kecil & tanpa awalan tetap ditemukan
    foreach (['ALR-7K2M9P', 'alr-7k2m9p', '7k2m9p', ' ALR 7K2M9P '] as $input) {
        $this->actingAs($admin)->get(route('admin.laporan.cari', ['kode' => $input]))
            ->assertOk()
            ->assertSee('ALR-7K2M9P')
            ->assertSee('&lt;b&gt;Gang Melati&lt;/b&gt;', false)
            ->assertSee('081234567890')
            ->assertSee(route('admin.peta-laporan.index', ['laporan' => $laporan->id]), false);
    }
    expect($admin->fresh()->unreadNotifications()->count())->toBe(0);

    $this->actingAs($admin)->get(route('admin.laporan.cari', ['kode' => 'ALR-ZZZZZZ']))
        ->assertOk()->assertSee('tidak ditemukan');
});
