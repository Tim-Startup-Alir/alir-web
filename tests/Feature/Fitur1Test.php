<?php

use App\Models\AuditLog;
use App\Models\Inventaris;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Posko;
use App\Models\User;
use App\Models\Wilayah;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
});

test('tabel komunitas_keterampilan telah dihapus dan seeder wilayah menghasilkan 3 kelurahan', function () {
    expect(Schema::hasTable('komunitas_keterampilan'))->toBeFalse();
    expect(Wilayah::count())->toBe(3);
    expect(Wilayah::pluck('nama_kelurahan')->all())->toEqualCanonicalizing(['Tirto', 'Pasirkratonkramat', 'Degayu']);
});

test('pbi-1.1 admin dapat crud wilayah, nonaktifkan, dan ditolak hapus bila dipakai laporan atau fk komunitas', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    // Tambah wilayah baru
    $this->actingAs($admin)->post(route('admin.wilayah.store'), [
        'kode_adm4' => '33.75.02.1001',
        'nama_kelurahan' => 'Krapyak',
        'kecamatan' => 'Pekalongan Utara',
        'latitude' => -6.8680,
        'longitude' => 109.6800,
        'radius_cakupan_m' => 1200,
    ])->assertRedirect(route('admin.wilayah.index'));

    $krapyak = Wilayah::where('kode_adm4', '33.75.02.1001')->firstOrFail();
    expect(AuditLog::where('aksi', 'wilayah.buat')->where('subjek_id', $krapyak->id)->exists())->toBeTrue();

    // Ubah wilayah
    $this->actingAs($admin)->put(route('admin.wilayah.update', $krapyak), [
        'kode_adm4' => '33.75.02.1001',
        'nama_kelurahan' => 'Krapyak Baru',
        'kecamatan' => 'Pekalongan Utara',
        'latitude' => -6.8680,
        'longitude' => 109.6800,
        'radius_cakupan_m' => 1400,
    ])->assertRedirect(route('admin.wilayah.index'));
    expect($krapyak->fresh()->nama_kelurahan)->toBe('Krapyak Baru');

    // Hapus wilayah yang belum dipakai berhasil
    $this->actingAs($admin)->delete(route('admin.wilayah.destroy', $krapyak))
        ->assertRedirect(route('admin.wilayah.index'))
        ->assertSessionHas('success');
    expect(Wilayah::find($krapyak->id))->toBeNull();

    // Wilayah yang dipakai komunitas (FK restrictOnDelete) gagal dihapus dengan pesan ramah (bukan 500)
    $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
    $this->actingAs($admin)->delete(route('admin.wilayah.destroy', $tirto))
        ->assertRedirect(route('admin.wilayah.index'))
        ->assertSessionHas('error');
    expect(Wilayah::find($tirto->id))->not->toBeNull();

    // Wilayah yang dipakai laporan banjir -> isInUse() true dan ditolak hapus
    LaporanBanjir::create([
        'wilayah_id' => $tirto->id,
        'kode_pelacakan' => 'ALR-TEST01',
        'alamat_singkat' => 'Jl. Tirto',
        'latitude' => $tirto->latitude,
        'longitude' => $tirto->longitude,
        'severity' => 'sedang',
        'nomor_wa_pelapor' => '081234567890',
    ]);
    expect($tirto->isInUse())->toBeTrue();

    // Nonaktifkan wilayah berfungsi
    $this->actingAs($admin)->patch(route('admin.wilayah.toggle-status', $tirto))
        ->assertRedirect(route('admin.wilayah.index'));
    expect($tirto->fresh()->is_aktif)->toBeFalse();
});

test('pbi-1.1 admin dapat membuat komunitas + posko utama, menonaktifkan, dan aturan hapus komunitas', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    // Posko di luar cakupan tanpa centang pernyataan harus ditolak validasi
    $this->actingAs($admin)->post(route('admin.komunitas.store'), [
        'nama' => 'Komunitas Luar Cakupan',
        'wilayah_id' => $tirto->id,
        'nama_pic' => 'Budi',
        'nomor_whatsapp_pic' => '081234567890',
        'total_anggota' => 12,
        'kapasitas_max' => 10,
        'jumlah_perahu' => 1,
        'email' => 'luar@alir.test',
        'password' => 'password123',
        'posko_latitude' => -7.0000000,
        'posko_longitude' => 109.9000000,
        'posko_radius_m' => 1000,
    ])->assertSessionHasErrors('pernyataan_luar_cakupan');

    // Tambah komunitas valid (di dalam cakupan Tirto)
    $this->actingAs($admin)->post(route('admin.komunitas.store'), [
        'nama' => 'Komunitas Sigap Tirto',
        'wilayah_id' => $tirto->id,
        'nama_pic' => 'Budi Santoso',
        'nomor_whatsapp_pic' => '081234567890',
        'total_anggota' => 12,
        'kapasitas_max' => 10,
        'jumlah_perahu' => 1,
        'email' => 'sigap.tirto@alir.test',
        'password' => 'password123',
        'posko_nama' => 'Posko Sigap Tirto',
        'posko_alamat' => 'Jl. Raya Tirto',
        'posko_latitude' => $tirto->latitude,
        'posko_longitude' => $tirto->longitude,
        'posko_radius_m' => 1200,
    ])->assertRedirect(route('admin.komunitas.index'));

    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Sigap Tirto')->firstOrFail();
    expect($komunitas->status_pendaftaran)->toBe('disetujui');
    expect($komunitas->user->wajib_ganti_sandi)->toBeTrue();
    expect($komunitas->user->email_verified_at)->not->toBeNull();
    expect($komunitas->poskoUtama())->not->toBeNull();

    // Toggle nonaktif komunitas (users.is_active = false)
    $this->actingAs($admin)->patch(route('admin.komunitas.toggle-status', $komunitas))
        ->assertRedirect(route('admin.komunitas.index'));
    expect($komunitas->user->fresh()->is_active)->toBeFalse();

    // Hapus komunitas yang belum dipakai -> menghapus komunitas dan akun user-nya
    $userId = $komunitas->user_id;
    $this->actingAs($admin)->delete(route('admin.komunitas.destroy', $komunitas))
        ->assertRedirect(route('admin.komunitas.index'))
        ->assertSessionHas('success');
    expect(KomunitasRelawan::find($komunitas->id))->toBeNull();
    expect(User::find($userId))->toBeNull();

    // Komunitas yang sudah punya inventaris -> ditolak untuk dihapus
    $komTirto = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    Inventaris::create([
        'komunitas_relawan_id' => $komTirto->id,
        'nama_barang' => 'Air Mineral',
        'kategori' => 'pangan',
        'satuan' => 'dus',
        'stok_minimum' => 5,
    ]);
    $this->actingAs($admin)->delete(route('admin.komunitas.destroy', $komTirto))
        ->assertRedirect(route('admin.komunitas.index'))
        ->assertSessionHas('error');
    expect(KomunitasRelawan::find($komTirto->id))->not->toBeNull();
});

test('pbi-1.2 policy hak akses profil, larangan field terlarang, dan audit log', function () {
    $komA = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $komB = KomunitasRelawan::where('nama', 'Komunitas Relawan Degayu')->firstOrFail();
    $komA->user->update(['wajib_ganti_sandi' => false]);
    $wilayahDegayu = Wilayah::where('nama_kelurahan', 'Degayu')->firstOrFail();

    // Komunitas A tidak boleh mengakses profil komunitas B via URL
    $this->actingAs($komA->user)->get(route('komunitas.profil.edit', $komB))->assertForbidden();
    $this->actingAs($komA->user)->put(route('komunitas.profil.update', $komB), [
        'nama_pic' => 'Hack',
    ])->assertForbidden();

    // Komunitas A mengirim request dengan field terlarang (nama, wilayah_id, status_pendaftaran, izin_tampil_publik)
    $this->actingAs($komA->user)->put(route('komunitas.profil.update'), [
        'nama' => 'Nama Diubah Paksa',
        'wilayah_id' => $wilayahDegayu->id,
        'status_pendaftaran' => 'ditolak',
        'izin_tampil_publik' => false,
        'nama_pic' => 'PIC Baru Tirto',
        'nomor_whatsapp_pic' => '081299998888',
        'total_anggota' => 25,
        'kapasitas_max' => 18,
        'jumlah_perahu' => 3,
        'posko_nama' => 'Posko Tirto Terkini',
        'posko_alamat' => 'Jl. Tirto Baru',
        'posko_latitude' => $komA->wilayah->latitude,
        'posko_longitude' => $komA->wilayah->longitude,
        'posko_radius_m' => 1400,
    ])->assertRedirect(route('komunitas.profil.edit'));

    $komA->refresh();
    expect($komA->nama)->toBe('Komunitas Relawan Tirto');
    expect($komA->wilayah_id)->not->toBe($wilayahDegayu->id);
    expect($komA->status_pendaftaran)->toBe('disetujui');
    expect($komA->izin_tampil_publik)->toBeTrue();
    expect($komA->nama_pic)->toBe('PIC Baru Tirto');
    expect($komA->poskoUtama()->nama)->toBe('Posko Tirto Terkini');
    expect(AuditLog::where('aksi', 'profil.ubah')->where('subjek_id', $komA->id)->exists())->toBeTrue();
});

test('pbi-1.3 admin crud jadwal dengan validasi bentrok dan komunitas hanya baca', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komTirto = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $komTirto->user->update(['wajib_ganti_sandi' => false]);

    // Komunitas hanya bisa baca jadwalnya sendiri, dan ditolak 403 di route admin jadwal
    $this->actingAs($komTirto->user)->get(route('komunitas.jadwal.index'))
        ->assertOk()
        ->assertSee('Jadwal Kesiapsiagaan');
    $this->actingAs($komTirto->user)->get(route('admin.jadwal.index'))->assertForbidden();
    $this->actingAs($komTirto->user)->post(route('admin.jadwal.store'), [])->assertForbidden();

    // Hapus jadwal default Tirto untuk menguji tambah jadwal baru + bentrok
    $komTirto->jadwalKesiapsiagaan()->delete();

    // Admin tambah shift lintas tengah malam Minggu 22:00-06:00
    $this->actingAs($admin)->post(route('admin.jadwal.store'), [
        'komunitas_relawan_id' => $komTirto->id,
        'hari' => 'minggu',
        'nama_shift' => 'Malam Minggu',
        'jam_mulai' => '22:00',
        'jam_selesai' => '06:00',
        'status_ketersediaan' => 'tersedia',
        'nama_pic' => 'PIC Malam',
        'nomor_whatsapp_pic' => '081234567890',
    ])->assertRedirect(route('admin.jadwal.index'));

    // Bentrok dengan Senin 04:00-08:00 harus ditolak
    $this->actingAs($admin)->post(route('admin.jadwal.store'), [
        'komunitas_relawan_id' => $komTirto->id,
        'hari' => 'senin',
        'nama_shift' => 'Pagi Bentrok',
        'jam_mulai' => '04:00',
        'jam_selesai' => '08:00',
        'status_ketersediaan' => 'tersedia',
        'nama_pic' => 'PIC Pagi',
        'nomor_whatsapp_pic' => '081234567890',
    ])->assertSessionHasErrors('jam_mulai');

    // Bersentuhan batas Senin 06:00-14:00 harus diterima
    $this->actingAs($admin)->post(route('admin.jadwal.store'), [
        'komunitas_relawan_id' => $komTirto->id,
        'hari' => 'senin',
        'nama_shift' => 'Pagi Aman',
        'jam_mulai' => '06:00',
        'jam_selesai' => '14:00',
        'status_ketersediaan' => 'siaga_terbatas',
        'nama_pic' => 'PIC Pagi',
        'nomor_whatsapp_pic' => '081234567890',
    ])->assertRedirect(route('admin.jadwal.index'));

    expect($komTirto->jadwalKesiapsiagaan()->count())->toBe(2);
    expect(AuditLog::where('aksi', 'jadwal.buat')->count())->toBe(2);
});
