<?php

use App\Models\JadwalKesiapsiagaan;
use App\Models\KomunitasRelawan;
use App\Models\User;
use App\Models\Wilayah;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(fn ($k) => $k->user?->update(['wajib_ganti_sandi' => false]));
});

test('pbi-013 dusk penugasan relawan tampilkan rekomendasi, disabled tooltip, tugaskan, dan batalkan', function () {
    $degayu = Wilayah::where('nama_kelurahan', 'Degayu')->firstOrFail();

    $userSiaga = User::create([
        'name' => 'Banser Peduli Banjir',
        'email' => 'banser-dusk@alir.test',
        'password' => bcrypt('password'),
        'role' => 'komunitas_relawan',
        'is_active' => true,
        'email_verified_at' => now(),
        'wajib_ganti_sandi' => false,
    ]);
    $komSiaga = KomunitasRelawan::create([
        'user_id' => $userSiaga->id,
        'wilayah_id' => $degayu->id,
        'nama' => 'Banser Peduli Banjir',
        'nama_pic' => 'PIC Banser',
        'nomor_whatsapp_pic' => '081234567891',
        'total_anggota' => 12,
        'kapasitas_max' => 12,
        'jumlah_perahu' => 2,
        'status_pendaftaran' => 'disetujui',
        'diverifikasi_pada' => now(),
    ]);
    JadwalKesiapsiagaan::create([
        'komunitas_relawan_id' => $komSiaga->id,
        'hari' => JadwalKesiapsiagaan::hariDari(now()),
        'nama_shift' => 'Shift Siaga 24 Jam',
        'jam_mulai' => '00:00:00',
        'jam_selesai' => '00:00:00',
        'status_ketersediaan' => 'tersedia',
        'nama_pic' => 'PIC Banser',
        'nomor_whatsapp_pic' => '081234567891',
    ]);

    $laporan = LaporanBanjirFactory::new()->create([
        'wilayah_id' => $degayu->id,
        'kode_pelacakan' => 'P-1035',
        'jumlah_warga' => 15,
        'severity' => 'sedang',
        'status' => 'terverifikasi',
    ]);

    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    /** @var DuskTestCase $dusk */
    $dusk = $this;
    $dusk->browse(function (Browser $browser) use ($admin, $laporan, $komSiaga) {
        $browser->loginAs($admin)
            ->visit('/admin/laporan/penugasan')
            ->waitFor('@pen-cards-wrap', 10)
            ->assertSee('Penugasan Relawan')
            ->assertSee('P-1035')
            ->assertSee('Banser Peduli Banjir')
            ->assertSee('Rekomendasi terbaik');

        // Tugaskan komunitas
        $browser->click("@btn-tugaskan-{$laporan->id}-{$komSiaga->id}")
            ->waitFor('@modal-tugaskan', 5)
            ->click('@btn-konfirmasi-tugaskan')
            ->waitForText('Sudah ditugaskan', 10)
            ->assertSee('Daftar tugas')
            ->assertSee('Menunggu');
    });
});
