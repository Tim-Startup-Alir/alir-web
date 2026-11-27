<?php

use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\User;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    RateLimiter::clear('lacak-menit|127.0.0.1');
    RateLimiter::clear('lacak-jam|127.0.0.1');
});

function laporanLacak(string $status = 'menunggu_verifikasi', array $attr = []): LaporanBanjir
{
    return LaporanBanjirFactory::new()->statusLaporan($status)->create(['kode_pelacakan' => 'ALR-7K3M9P', 'alamat_singkat' => 'Gang Rahasia RT 09'] + $attr);
}

function penugasanLacak(LaporanBanjir $laporan, string $stage, array $attr = []): Penugasan
{
    return Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => KomunitasRelawan::firstOrFail()->id,
        'ditugaskan_oleh' => User::where('role', 'admin')->firstOrFail()->id,
        'stage' => $stage,
    ] + $attr);
}

function stateLinimasa(array $lacak): array
{
    return array_column($lacak['linimasa'], 'state');
}

it('menemukan laporan dengan kode huruf kecil atau tanpa awalan', function (string $input) {
    laporanLacak();

    $this->postJson(route('lacak.cari'), ['kode' => $input])
        ->assertOk()
        ->assertJson(['redirect' => route('lacak.show', 'ALR-7K3M9P')]);
})->with(['ALR-7K3M9P', 'alr-7k3m9p', '7k3m9p', ' alr 7k3m9p ']);

it('menampilkan halaman progres tanpa data pribadi', function () {
    laporanLacak('ditangani', ['nama_pelapor' => 'Budi Rahasia', 'nomor_wa_pelapor' => '081299998888']);

    $this->get(route('lacak.show', 'alr-7k3m9p'))
        ->assertOk()
        ->assertSee('ALR-7K3M9P')
        ->assertSee('Ditangani')
        ->assertDontSee('Budi Rahasia')
        ->assertDontSee('081299998888')
        ->assertDontSee('Gang Rahasia')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('memberi pesan generik untuk kode yang tidak ada', function () {
    $this->postJson(route('lacak.cari'), ['kode' => 'ALR-ZZZZZZ'])
        ->assertNotFound()
        ->assertJson(['message' => 'Kode laporan tidak ditemukan. Periksa kembali kode Anda, contoh: ALR-7K3M9P.']);

    $this->get(route('lacak.show', 'ALR-ZZZZZZ'))->assertNotFound()->assertSee('tidak ditemukan');
    $this->post(route('lacak.cari'), ['kode' => ''])->assertSessionHasErrors('kode');
});

it('membangun linimasa sesuai status', function () {
    $svc = app(\App\Services\LacakLaporanService::class);

    $menunggu = laporanLacak();
    expect(stateLinimasa($svc->ringkas($menunggu->fresh())))->toBe(['selesai', 'aktif', 'belum', 'belum', 'belum', 'belum']);

    $menunggu->update(['status' => 'terverifikasi', 'diverifikasi_pada' => now()]);
    expect(stateLinimasa($svc->ringkas($menunggu->fresh())))->toBe(['selesai', 'selesai', 'aktif', 'belum', 'belum', 'belum']);

    penugasanLacak($menunggu->fresh(), 'diterima', ['waktu_diterima' => now()]);
    expect(stateLinimasa($svc->ringkas($menunggu->fresh())))->toBe(['selesai', 'selesai', 'selesai', 'aktif', 'belum', 'belum']);
});

it('menandai selesai dan kasus ditolak serta digabung', function () {
    $svc = app(\App\Services\LacakLaporanService::class);

    $selesai = laporanLacak('selesai', ['diverifikasi_pada' => now()]);
    penugasanLacak($selesai, 'selesai', ['waktu_diterima' => now(), 'waktu_menuju_lokasi' => now(), 'waktu_ditangani' => now(), 'waktu_selesai' => now()]);
    expect(stateLinimasa($svc->ringkas($selesai->fresh())))->toBe(array_fill(0, 6, 'selesai'));

    $selesai->update(['status' => 'ditolak', 'alasan_tolak' => 'internal: foto palsu']);
    $ringkas = $svc->ringkas($selesai->fresh());
    expect(stateLinimasa($ringkas))->toBe(['selesai', 'gagal']);
    $this->get(route('lacak.show', 'ALR-7K3M9P'))->assertOk()->assertDontSee('foto palsu');

    $selesai->update(['status' => 'digabung']);
    expect($svc->ringkas($selesai->fresh())['label'])->toBe('Digabung');
});

it('membatasi pencarian per IP', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson(route('lacak.cari'), ['kode' => 'ALR-ZZZZZZ'])->assertNotFound();
    }

    $this->postJson(route('lacak.cari'), ['kode' => 'ALR-ZZZZZZ'])->assertStatus(429);
});
