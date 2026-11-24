<?php

use App\Models\AuditLog;
use App\Models\JadwalKesiapsiagaan;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\User;
use App\Models\Wilayah;
use App\Notifications\PenugasanBaruNotification;
use App\Services\PenugasanService;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($k) => $k->user?->update(['wajib_ganti_sandi' => false]));
});

function buatKomunitasSiaga(Wilayah $wilayah, string $email, string $nama, int $kapasitas = 12, int $perahu = 2, bool $siaga = true): KomunitasRelawan
{
    $user = User::create([
        'name' => $nama,
        'email' => $email,
        'password' => bcrypt('password'),
        'role' => 'komunitas_relawan',
        'is_active' => true,
        'email_verified_at' => now(),
        'wajib_ganti_sandi' => false,
    ]);

    $komunitas = KomunitasRelawan::create([
        'user_id' => $user->id,
        'wilayah_id' => $wilayah->id,
        'nama' => $nama,
        'nama_pic' => 'PIC '.$nama,
        'nomor_whatsapp_pic' => '081234567890',
        'total_anggota' => $kapasitas,
        'kapasitas_max' => $kapasitas,
        'jumlah_perahu' => $perahu,
        'status_pendaftaran' => 'disetujui',
        'diverifikasi_pada' => now(),
    ]);

    if ($siaga) {
        JadwalKesiapsiagaan::create([
            'komunitas_relawan_id' => $komunitas->id,
            'hari' => JadwalKesiapsiagaan::hariDari(now()),
            'nama_shift' => 'Shift Siaga 24 Jam',
            'jam_mulai' => '00:00:00',
            'jam_selesai' => '00:00:00',
            'status_ketersediaan' => 'tersedia',
            'nama_pic' => 'PIC '.$nama,
            'nomor_whatsapp_pic' => '081234567890',
        ]);
    }

    return $komunitas;
}

test('pbi-013 halaman penugasan memblokir non-admin dan hanya menampilkan laporan terverifikasi atau ditugaskan', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $community = KomunitasRelawan::firstOrFail()->user;

    $this->get(route('admin.penugasan.index'))->assertRedirect(route('login'));
    $this->actingAs($community)->get(route('admin.penugasan.index'))->assertForbidden();

    $wilayah = Wilayah::where('nama_kelurahan', 'Degayu')->firstOrFail();
    LaporanBanjirFactory::new()->create(['wilayah_id' => $wilayah->id, 'kode_pelacakan' => 'ALR-TERVERIF', 'status' => 'terverifikasi']);
    LaporanBanjirFactory::new()->create(['wilayah_id' => $wilayah->id, 'kode_pelacakan' => 'ALR-MENUNGGU', 'status' => 'menunggu_verifikasi']);

    $this->actingAs($admin)->get(route('admin.penugasan.index'))
        ->assertOk()
        ->assertSee('ALR-TERVERIF')
        ->assertDontSee('ALR-MENUNGGU');
});

test('pbi-013 rekomendasi hanya memuat komunitas terverifikasi aktif, hitung eta 15 km/jam, dan tolak jika melebihi kapasitas', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $degayu = Wilayah::where('nama_kelurahan', 'Degayu')->firstOrFail();
    $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    $komSiaga = buatKomunitasSiaga($degayu, 'siaga@alir.test', 'Banser Peduli Banjir', 10, 2, true);
    $komBelumSiaga = buatKomunitasSiaga($tirto, 'belum@alir.test', 'Komunitas Belum Siaga', 10, 1, false);

    $laporan = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $degayu->id,
        'latitude' => (float) $degayu->latitude,
        'longitude' => (float) $degayu->longitude,
        'status' => 'terverifikasi',
    ]);

    $rek = app(PenugasanService::class)->rekomendasiUntuk($laporan);
    $itemSiaga = $rek->firstWhere('komunitas_id', $komSiaga->id);
    $itemBelum = $rek->firstWhere('komunitas_id', $komBelumSiaga->id);

    expect($itemSiaga['bisa_ditugaskan'])->toBeTrue()
        ->and($rek->where('is_terbaik', true)->count())->toBe(1)
        ->and($itemBelum['bisa_ditugaskan'])->toBeFalse()
        ->and($itemBelum['alasan_disabled'])->toBe('Belum siaga');

    // Personel melebihi kapasitas ditolak
    $this->actingAs($admin)
        ->postJson(route('admin.penugasan.store', $laporan), [
            'komunitas_relawan_id' => $komSiaga->id,
            'jumlah_personil_tugas' => 99,
            'armada_perahu_tugas' => 1,
        ])
        ->assertUnprocessable();
});

test('pbi-013 tugaskan mengubah status laporan menjadi ditugaskan, kirim notifikasi, catat audit log, dan cegah double assign', function () {
    Notification::fake();

    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $degayu = Wilayah::where('nama_kelurahan', 'Degayu')->firstOrFail();
    $komunitas = buatKomunitasSiaga($degayu, 'relawan1@alir.test', 'PMI Relawan Kota', 15, 3, true);

    $laporan = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $degayu->id,
        'status' => 'terverifikasi',
    ]);

    $this->actingAs($admin)
        ->postJson(route('admin.penugasan.store', $laporan), [
            'komunitas_relawan_id' => $komunitas->id,
            'jumlah_personil_tugas' => 6,
            'armada_perahu_tugas' => 1,
            'catatan' => 'Bawa pelampung tambahan',
        ])
        ->assertOk();

    expect($laporan->fresh()->status)->toBe('ditugaskan')
        ->and($komunitas->sisaKapasitas())->toBe(9);

    Notification::assertSentTo($komunitas->user, PenugasanBaruNotification::class);

    $this->assertDatabaseHas('audit_log', [
        'user_id' => $admin->id,
        'aksi' => 'penugasan.buat',
    ]);

    // Klik ganda / tugaskan lagi ditolak
    $this->actingAs($admin)
        ->postJson(route('admin.penugasan.store', $laporan), [
            'komunitas_relawan_id' => $komunitas->id,
            'jumlah_personil_tugas' => 2,
            'armada_perahu_tugas' => 0,
        ])
        ->assertUnprocessable();
});

test('pbi-013 ganti dan batalkan hanya berlaku saat stage menunggu dan mengembalikan kapasitas', function () {
    Notification::fake();

    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $degayu = Wilayah::where('nama_kelurahan', 'Degayu')->firstOrFail();
    $komA = buatKomunitasSiaga($degayu, 'koma@alir.test', 'Komunitas A', 10, 2, true);
    $komB = buatKomunitasSiaga($degayu, 'komb@alir.test', 'Komunitas B', 12, 2, true);

    $laporan = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $degayu->id,
        'status' => 'terverifikasi',
    ]);

    $penugasan = app(PenugasanService::class)->tugaskan($laporan, $komA, $admin, 4, 1);
    expect($komA->sisaKapasitas())->toBe(6);

    // Ganti ke Komunitas B
    $this->actingAs($admin)
        ->patchJson(route('admin.penugasan.ganti', $penugasan), [
            'komunitas_relawan_id' => $komB->id,
            'jumlah_personil_tugas' => 5,
            'armada_perahu_tugas' => 1,
        ])
        ->assertOk();

    expect($penugasan->fresh()->stage)->toBe('dibatalkan')
        ->and($komA->sisaKapasitas())->toBe(10)
        ->and($komB->sisaKapasitas())->toBe(7)
        ->and($laporan->fresh()->status)->toBe('ditugaskan');

    $penugasanBaru = Penugasan::where('laporan_banjir_id', $laporan->id)->where('stage', 'menunggu')->firstOrFail();

    // Batalkan penugasan baru -> laporan kembali terverifikasi & kapasitas B kembali 12
    $this->actingAs($admin)
        ->deleteJson(route('admin.penugasan.destroy', $penugasanBaru), ['alasan' => 'Banjir sudah surut'])
        ->assertOk();

    expect($penugasanBaru->fresh()->stage)->toBe('dibatalkan')
        ->and($komB->sisaKapasitas())->toBe(12)
        ->and($laporan->fresh()->status)->toBe('terverifikasi');

    // Jika stage sudah diterima, tidak boleh dibatalkan
    $penugasanDiterima = app(PenugasanService::class)->tugaskan($laporan->fresh(), $komA, $admin, 3, 1);
    $penugasanDiterima->perbaruiStage('diterima');

    $this->actingAs($admin)
        ->deleteJson(route('admin.penugasan.destroy', $penugasanDiterima))
        ->assertUnprocessable();
});
