<?php

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\User;
use App\Models\Wilayah;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
});

test('pbi-1.1 dusk: crud wilayah, aturan hapus/nonaktif, tambah komunitas, dan proteksi role', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komTirto = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $komTirto->user->update(['wajib_ganti_sandi' => false]);

    $this->browse(function (Browser $browser) use ($admin, $komTirto) {
        // Komunitas ditolak saat mencoba membuka halaman master
        $browser->loginAs($komTirto->user)
            ->visit('/admin/wilayah')
            ->assertSee('403')
            ->visit('/admin/komunitas')
            ->assertSee('403');

        // Admin masuk dan verifikasi 3 kelurahan seeder tampil
        $browser->loginAs($admin)
            ->visit('/admin/wilayah')
            ->assertSee('Tirto')
            ->assertSee('Pasirkratonkramat')
            ->assertSee('Degayu');

        // Submit form dengan field kosong sementara nama_kelurahan sudah diisi -> error tampil tanpa menghapus isian
        $browser->click('@add-wilayah-btn')
            ->waitForLocation('/admin/wilayah/create')
            ->type('nama_kelurahan', 'Krapyak Test')
            ->click('@submit-wilayah')
            ->waitForText('Kode wilayah (ADM4) wajib diisi.')
            ->assertInputValue('nama_kelurahan', 'Krapyak Test');

        // Tambah wilayah valid
        $browser->type('kode_adm4', '33.75.02.1005')
            ->type('nama_kelurahan', 'Krapyak Kidul')
            ->type('kecamatan', 'Pekalongan Utara')
            ->type('latitude', '-6.8700000')
            ->type('longitude', '109.6800000')
            ->click('@submit-wilayah')
            ->waitForLocation('/admin/wilayah')
            ->waitForText('Wilayah Krapyak Kidul berhasil ditambahkan.');

        $wilayahBaru = Wilayah::where('kode_adm4', '33.75.02.1005')->firstOrFail();

        // Ubah wilayah
        $browser->click("@edit-wilayah-{$wilayahBaru->id}")
            ->waitForLocation("/admin/wilayah/{$wilayahBaru->id}/edit")
            ->clear('nama_kelurahan')
            ->type('nama_kelurahan', 'Krapyak Lor')
            ->click('@submit-wilayah')
            ->waitForLocation('/admin/wilayah')
            ->waitForText('Wilayah Krapyak Lor berhasil diperbarui.');

        // Hapus wilayah yang belum dipakai berhasil
        $browser->click("@delete-wilayah-{$wilayahBaru->id}")
            ->acceptDialog()
            ->waitForText('Wilayah Krapyak Lor berhasil dihapus.');

        // Wilayah yang dipakai komunitas (Tirto) ditolak hapus & nonaktifkan berfungsi
        $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
        $browser->click("@delete-wilayah-{$tirto->id}")
            ->acceptDialog()
            ->waitForText('tidak dapat dihapus')
            ->click("@toggle-wilayah-{$tirto->id}")
            ->waitForText('berhasil dinonaktifkan')
            ->click("@toggle-wilayah-{$tirto->id}")
            ->waitForText('berhasil diaktifkan');

        // Tambah komunitas baru beserta akun & posko utama (uji cek pernyataan luar cakupan)
        $browser->visit('/admin/komunitas/create')
            ->type('nama', 'Komunitas Dusk Baru')
            ->select('wilayah_id', (string) $tirto->id)
            ->type('email', 'dusk.baru@alir.test')
            ->type('password', 'password123')
            ->type('nama_pic', 'PIC Dusk')
            ->type('nomor_whatsapp_pic', '081234567899')
            ->type('posko_latitude', '-7.1000000')
            ->type('posko_longitude', '109.9000000')
            ->click('@submit-komunitas')
            ->waitForText('Koordinat posko berada di luar 3 kelurahan pilot')
            ->assertInputValue('nama', 'Komunitas Dusk Baru')
            ->check('pernyataan_luar_cakupan')
            ->type('password', 'password123')
            ->click('@submit-komunitas')
            ->waitForLocation('/admin/komunitas')
            ->assertSee('Komunitas Dusk Baru');
    });
});

test('pbi-1.2 dusk: profil komunitas, posko luar cakupan, akses lintas komunitas, dan audit log', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komA = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $komB = KomunitasRelawan::where('nama', 'Komunitas Relawan Degayu')->firstOrFail();
    $komA->user->update(['wajib_ganti_sandi' => false]);

    $this->browse(function (Browser $browser) use ($admin, $komA, $komB) {
        // Admin mengubah profil komunitas B
        $browser->loginAs($admin)
            ->visit("/admin/komunitas/{$komB->id}/profil")
            ->clear('nama_pic')
            ->type('nama_pic', 'PIC Degayu Updated')
            ->click('@submit-profil')
            ->waitForText('berhasil diperbarui');

        // Komunitas A mengubah profilnya sendiri + posko luar cakupan wajib pernyataan
        $browser->loginAs($komA->user)
            ->visit('/komunitas/profil')
            ->assertInputValue('nama_pic', $komA->nama_pic)
            ->clear('nama_pic')
            ->type('nama_pic', 'PIC Tirto Dusk')
            ->clear('posko_latitude')
            ->type('posko_latitude', '-7.1500000')
            ->clear('posko_longitude')
            ->type('posko_longitude', '109.9500000')
            ->click('@submit-profil')
            ->waitForText('Koordinat posko berada di luar 3 kelurahan pilot')
            ->check('pernyataan_luar_cakupan')
            ->click('@submit-profil')
            ->waitForText('Profil komunitas dan posko utama berhasil diperbarui.');

        // Komunitas A mengakses URL komunitas B ditolak 403
        $browser->visit("/komunitas/profil/{$komB->id}")
            ->assertSee('403');
    });

    expect($komA->fresh()->nama_pic)->toBe('PIC Tirto Dusk');
    expect(AuditLog::where('aksi', 'profil.ubah')->where('subjek_id', $komA->id)->exists())->toBeTrue();
});

test('pbi-1.3 dusk: admin kelola jadwal (bentrok & lintas tengah malam) dan komunitas baca saja', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komTirto = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $komTirto->user->update(['wajib_ganti_sandi' => false]);

    $this->browse(function (Browser $browser) use ($admin, $komTirto) {
        // Komunitas melihat jadwalnya sendiri (hanya baca, tidak ada tombol Tambah/Ubah/Hapus)
        $browser->loginAs($komTirto->user)
            ->visit('/komunitas/jadwal-kesiapsiagaan')
            ->waitForText('Jadwal Kesiapsiagaan')
            ->assertSee('Pagi')
            ->assertDontSee('Tambah Jadwal');

        // Kosongkan jadwal Tirto untuk uji skenario tambah, bentrok, lintas tengah malam
        $komTirto->jadwalKesiapsiagaan()->delete();

        $browser->loginAs($admin)
            ->visit('/admin/jadwal-kesiapsiagaan/create')
            ->select('komunitas_relawan_id', (string) $komTirto->id)
            ->select('hari', 'senin')
            ->type('nama_shift', 'Malam Lintas')
            ->type('jam_mulai', '22:00')
            ->type('jam_selesai', '06:00')
            ->select('status_ketersediaan', 'tersedia')
            ->type('nama_pic', 'PIC Malam Dusk')
            ->type('nomor_whatsapp_pic', '081234567890')
            ->click('@submit-jadwal')
            ->waitForLocation('/admin/jadwal-kesiapsiagaan')
            ->waitForText('Jadwal kesiapsiagaan berhasil ditambahkan.')
            ->assertSee('Lintas hari');

        // Bentrok dengan shift lintas tengah malam tersebut pada Selasa 05:00-09:00
        $browser->visit('/admin/jadwal-kesiapsiagaan/create')
            ->select('komunitas_relawan_id', (string) $komTirto->id)
            ->select('hari', 'selasa')
            ->type('nama_shift', 'Pagi Bentrok')
            ->type('jam_mulai', '05:00')
            ->type('jam_selesai', '09:00')
            ->type('nama_pic', 'PIC Bentrok')
            ->type('nomor_whatsapp_pic', '081234567890')
            ->click('@submit-jadwal')
            ->waitForText('Jadwal bentrok dengan shift');

        // Bersentuhan batas tepat di 06:00 pada Selasa -> diterima
        $browser->clear('jam_mulai')
            ->type('jam_mulai', '06:00')
            ->clear('jam_selesai')
            ->type('jam_selesai', '14:00')
            ->click('@submit-jadwal')
            ->waitForLocation('/admin/jadwal-kesiapsiagaan')
            ->waitForText('Jadwal kesiapsiagaan berhasil ditambahkan.');
    });
});
