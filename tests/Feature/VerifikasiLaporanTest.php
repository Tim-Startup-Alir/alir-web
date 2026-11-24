<?php

use App\Models\AuditLog;
use App\Models\FotoLaporan;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\VerifikasiLaporanService;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($k) => $k->user?->update(['wajib_ganti_sandi' => false]));
});

function buatLaporan(?Wilayah $wilayah = null, array $attr = []): LaporanBanjir
{
    $wilayah ??= Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    return LaporanBanjirFactory::new()->create(array_merge([
        'wilayah_id' => $wilayah->id,
        'latitude' => (float) $wilayah->latitude,
        'longitude' => (float) $wilayah->longitude,
        'status' => 'menunggu_verifikasi',
    ], $attr));
}

test('pbi-010 antrean hanya memuat menunggu verifikasi, pakai pagination, dan blokir non-admin', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $community = KomunitasRelawan::firstOrFail()->user;

    $this->get(route('admin.laporan.verifikasi.index'))->assertRedirect(route('login'));
    $this->actingAs($community)->get(route('admin.laporan.verifikasi.index'))->assertForbidden();

    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
    LaporanBanjirFactory::new()->count(14)->create([
        'wilayah_id' => $wilayah->id,
        'status' => 'menunggu_verifikasi',
    ]);
    $sudahValid = buatLaporan($wilayah, ['status' => 'terverifikasi', 'kode_pelacakan' => 'ALR-VALID1']);

    $res = $this->actingAs($admin)->get(route('admin.laporan.verifikasi.index'));
    $res->assertOk()
        ->assertViewHas('laporans', fn ($p) => $p->perPage() === 12
            && $p->total() === 14
            && ! $p->getCollection()->contains('kode_pelacakan', 'ALR-VALID1'));

    // Halaman 2 pagination memuat sisa 2 laporan
    $this->actingAs($admin)->get(route('admin.laporan.verifikasi.index', ['page' => 2]))
        ->assertOk()
        ->assertViewHas('laporans', fn ($p) => $p->count() === 2);
});

test('pbi-010 detail json mengembalikan kepercayaan pbi-008, laporan sekitar, dan wa termasking', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    $utama = buatLaporan($wilayah, ['nomor_wa_pelapor' => '085712344432']);
    $sekitar = LaporanBanjirFactory::new()->dekatDari($utama, 120)->create([
        'wilayah_id' => $wilayah->id,
        'nomor_wa_pelapor' => '085799998888',
    ]);
    LaporanBanjirFactory::new()->dekatDari($utama, 700)->create(['wilayah_id' => $wilayah->id]);

    $this->actingAs($admin)
        ->getJson(route('admin.laporan.verifikasi.show', $utama))
        ->assertOk()
        ->assertJsonPath('id', $utama->id)
        ->assertJsonPath('nomor_wa_masked', '0857-****-4432')
        ->assertJsonPath('kepercayaan.level', 'sedang')
        ->assertJsonCount(1, 'sekitar')
        ->assertJsonPath('sekitar.0.id', $sekitar->id);
});

test('pbi-011 tandai valid mengubah status, mencatat verifikator, waktu, dan audit log', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $laporan = buatLaporan();

    $this->actingAs($admin)
        ->postJson(route('admin.laporan.valid', $laporan))
        ->assertOk()
        ->assertJsonPath('status', 'terverifikasi');

    $laporan->refresh();
    expect($laporan->status)->toBe('terverifikasi')
        ->and($laporan->diverifikasi_oleh)->toBe($admin->id)
        ->and($laporan->diverifikasi_pada)->not->toBeNull();

    $this->assertDatabaseHas('audit_log', [
        'user_id' => $admin->id,
        'aksi' => 'laporan.verifikasi_valid',
        'subjek_id' => $laporan->id,
    ]);
});

test('pbi-011 tandai tidak valid wajib mengisi alasan dan mencatat audit log', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $laporan = buatLaporan();

    $this->actingAs($admin)
        ->postJson(route('admin.laporan.tolak', $laporan), ['alasan' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('alasan');

    expect($laporan->fresh()->status)->toBe('menunggu_verifikasi');

    $this->actingAs($admin)
        ->postJson(route('admin.laporan.tolak', $laporan), ['alasan' => 'Foto tidak sesuai lokasi banjir'])
        ->assertOk()
        ->assertJsonPath('status', 'ditolak');

    $laporan->refresh();
    expect($laporan->status)->toBe('ditolak')
        ->and($laporan->alasan_tolak)->toBe('Foto tidak sesuai lokasi banjir')
        ->and($laporan->diverifikasi_oleh)->toBe($admin->id)
        ->and($laporan->diverifikasi_pada)->not->toBeNull();

    $this->assertDatabaseHas('audit_log', [
        'user_id' => $admin->id,
        'aksi' => 'laporan.verifikasi_tolak',
        'subjek_id' => $laporan->id,
    ]);
});

test('pbi-012 tandai duplikat wajib pilih induk dalam 500m, gabung foto dan jumlah warga dalam 1 transaksi', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    $induk = buatLaporan($wilayah, ['jumlah_warga' => 25, 'status' => 'terverifikasi']);
    FotoLaporan::create(['laporan_banjir_id' => $induk->id, 'foto_path' => 'foto/induk.jpg', 'urutan' => 1]);

    $duplikat = LaporanBanjirFactory::new()->dekatDari($induk, 80)->create([
        'wilayah_id' => $wilayah->id,
        'jumlah_warga' => 15,
        'status' => 'menunggu_verifikasi',
    ]);
    $fotoDup = FotoLaporan::create(['laporan_banjir_id' => $duplikat->id, 'foto_path' => 'foto/dup.jpg', 'urutan' => 1]);

    $jauh = LaporanBanjirFactory::new()->dekatDari($induk, 700)->create(['wilayah_id' => $wilayah->id]);

    // Tanpa induk ditolak
    $this->actingAs($admin)
        ->postJson(route('admin.laporan.duplikat', $duplikat), [])
        ->assertUnprocessable();

    // Induk > 500m ditolak
    $this->actingAs($admin)
        ->postJson(route('admin.laporan.duplikat', $duplikat), ['laporan_banjir_induk_id' => $jauh->id])
        ->assertUnprocessable();

    // Induk valid <= 500m berhasil
    $this->actingAs($admin)
        ->postJson(route('admin.laporan.duplikat', $duplikat), ['laporan_banjir_induk_id' => $induk->id])
        ->assertOk()
        ->assertJsonPath('status', 'digabung');

    $induk->refresh();
    $duplikat->refresh();
    $fotoDup->refresh();

    expect($duplikat->status)->toBe('digabung')
        ->and($duplikat->laporan_banjir_induk_id)->toBe($induk->id)
        ->and($induk->jumlah_warga)->toBe(40)
        ->and($fotoDup->laporan_banjir_id)->toBe($induk->id)
        ->and($fotoDup->id_laporan_asal)->toBe($duplikat->id);

    $this->assertDatabaseHas('audit_log', [
        'user_id' => $admin->id,
        'aksi' => 'laporan.verifikasi_duplikat',
        'subjek_id' => $duplikat->id,
    ]);
});

test('pbi-012 rollback seluruh perubahan jika terjadi kegagalan di tengah transaksi duplikat', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    $induk = buatLaporan($wilayah, ['jumlah_warga' => 10]);
    $duplikat = LaporanBanjirFactory::new()->dekatDari($induk, 60)->create([
        'wilayah_id' => $wilayah->id,
        'jumlah_warga' => 12,
        'status' => 'menunggu_verifikasi',
    ]);
    $fotoDup = FotoLaporan::create(['laporan_banjir_id' => $duplikat->id, 'foto_path' => 'foto/dup.jpg', 'urutan' => 1]);

    // Paksa exception saat AuditLog::catat dipanggil di akhir transaksi
    AuditLog::creating(function () {
        throw new RuntimeException('Simulasi kegagalan audit log');
    });

    try {
        app(VerifikasiLaporanService::class)->tandaiDuplikat($duplikat, $induk, $admin);
    } catch (RuntimeException) {
        // expected
    }

    expect($induk->fresh()->jumlah_warga)->toBe(10)
        ->and($duplikat->fresh()->status)->toBe('menunggu_verifikasi')
        ->and($duplikat->fresh()->laporan_banjir_induk_id)->toBeNull()
        ->and($fotoDup->fresh()->laporan_banjir_id)->toBe($duplikat->id);
});
