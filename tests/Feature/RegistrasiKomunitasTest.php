<?php

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\User;
use App\Models\Wilayah;
use App\Notifications\PendaftaranBaruAdminNotification;
use App\Notifications\PendaftaranDisetujuiNotification;
use App\Notifications\PendaftaranDitolakNotification;
use App\Notifications\PendaftaranVerifikasiEmailNotification;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class]);
});

test('pengunjung dapat melihat halaman pendaftaran komunitas tanpa login', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Daftar relawan')
        ->assertSee('Tirto');
});

test('validasi pendaftaran menolak berkas tidak valid, luar cakupan tanpa pernyataan, dan tanpa persetujuan syarat', function () {
    Storage::fake('local');
    $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    $this->post(route('register.store'), [
        'nama' => 'Komunitas Uji Validasi',
        'wilayah_id' => $tirto->id,
        'posko_latitude' => -7.2000000,
        'posko_longitude' => 109.9500000,
        'nama_pic' => 'Andi',
        'nomor_whatsapp_pic' => '081234567890',
        'email' => 'andi@komunitas.test',
        'total_anggota' => 10,
        'jumlah_perahu' => 1,
        'dokumen_pendukung' => UploadedFile::fake()->create('dokumen.txt', 50, 'text/plain'),
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors(['pernyataan_luar_cakupan', 'dokumen_pendukung', 'setuju_syarat']);

    expect(User::where('email', 'andi@komunitas.test')->exists())->toBeFalse();
});

test('alur lengkap registrasi, verifikasi email, penolakan admin, perbaikan signed url, persetujuan admin, hingga login', function () {
    Storage::fake('local');
    Notification::fake();

    $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    // 1. Calon komunitas mendaftar
    $this->post(route('register.store'), [
        'nama' => 'Komunitas Relawan Pantai',
        'wilayah_id' => $tirto->id,
        'posko_nama' => 'Posko Pantai',
        'posko_alamat' => 'Jl. Pantai Tirto',
        'posko_latitude' => $tirto->latitude,
        'posko_longitude' => $tirto->longitude,
        'posko_radius_m' => 1200,
        'nama_pic' => 'Rina Kartika',
        'nomor_whatsapp_pic' => '081234567890',
        'email' => 'pantai@alir.test',
        'total_anggota' => 15,
        'kapasitas_max' => 12,
        'jumlah_perahu' => 2,
        'dokumen_pendukung' => UploadedFile::fake()->create('surat-izin.pdf', 120, 'application/pdf'),
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'setuju_syarat' => '1',
    ])->assertRedirect(route('register.sent'));

    $user = User::where('email', 'pantai@alir.test')->firstOrFail();
    $komunitas = $user->komunitasRelawan;

    expect($user->is_active)->toBeFalse();
    expect($user->email_verified_at)->toBeNull();
    expect($komunitas->status_pendaftaran)->toBe('menunggu');
    expect($komunitas->menyetujui_syarat_pada)->not->toBeNull();
    expect(Storage::disk('local')->exists($komunitas->dokumen_pendukung_path))->toBeTrue();
    expect(KomunitasRelawan::siapDitinjau()->whereKey($komunitas->id)->exists())->toBeFalse();

    Notification::assertSentTo($user, PendaftaranVerifikasiEmailNotification::class);

    // 2. Belum bisa login sebelum verifikasi email & persetujuan admin
    $this->post('/login', [
        'email' => 'pantai@alir.test',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors('email');
    $this->assertGuest();

    // 3. Verifikasi email lewat signed URL
    $verifyUrl = URL::temporarySignedRoute('register.verify-email', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($user->getEmailForVerification()),
    ]);
    $this->get($verifyUrl)->assertOk()->assertSee('Email berhasil diverifikasi');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect(KomunitasRelawan::siapDitinjau()->whereKey($komunitas->id)->exists())->toBeTrue();
    Notification::assertSentTo($admin, PendaftaranBaruAdminNotification::class);

    // Tetap belum bisa login karena masih menunggu persetujuan admin
    $this->post('/login', [
        'email' => 'pantai@alir.test',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors('email');

    // 4. Admin melihat antrean & dokumen pendukung; tamu/komunitas lain tidak bisa akses dokumen
    $this->get(route('admin.komunitas.dokumen', $komunitas))->assertRedirect(route('login'));
    $this->actingAs($admin)->get(route('admin.komunitas.index'))
        ->assertOk()
        ->assertSee('Antrean Pendaftaran Baru')
        ->assertSee('Komunitas Relawan Pantai');
    $this->actingAs($admin)->get(route('admin.komunitas.dokumen', $komunitas))->assertOk();

    // 5. Admin menolak pendaftaran dengan alasan wajib
    $this->actingAs($admin)->post(route('admin.komunitas.tolak', $komunitas), [
        'alasan_tolak_registrasi' => '',
    ])->assertSessionHasErrors('alasan_tolak_registrasi');

    $this->actingAs($admin)->post(route('admin.komunitas.tolak', $komunitas), [
        'alasan_tolak_registrasi' => 'Foto surat izin belum terbaca, mohon unggah ulang.',
    ])->assertRedirect(route('admin.komunitas.index'));

    $komunitas->refresh();
    expect($komunitas->status_pendaftaran)->toBe('ditolak');
    expect($komunitas->alasan_tolak_registrasi)->toContain('Foto surat izin');
    expect($user->fresh()->is_active)->toBeFalse();
    Notification::assertSentTo($user, PendaftaranDitolakNotification::class);

    // 6. Tautan perbaikan tanpa signature valid ditolak 403
    $this->get("/daftar/perbaikan/{$komunitas->id}")->assertForbidden();

    // Buka signed URL perbaikan dan ajukan ulang
    $perbaikanUrl = URL::temporarySignedRoute('register.perbaikan.edit', now()->addDays(7), [
        'komunitas' => $komunitas->id,
    ]);
    $this->get($perbaikanUrl)
        ->assertOk()
        ->assertSee('Foto surat izin belum terbaca')
        ->assertSee('Komunitas Relawan Pantai');

    $updatePerbaikanUrl = URL::signedRoute('register.perbaikan.update', ['komunitas' => $komunitas->id]);
    $this->put($updatePerbaikanUrl, [
        'nama' => 'Komunitas Relawan Pantai Bersatu',
        'wilayah_id' => $tirto->id,
        'posko_nama' => 'Posko Pantai Baru',
        'posko_alamat' => 'Jl. Pantai Tirto No. 2',
        'posko_latitude' => $tirto->latitude,
        'posko_longitude' => $tirto->longitude,
        'posko_radius_m' => 1300,
        'nama_pic' => 'Rina Kartika',
        'nomor_whatsapp_pic' => '081234567890',
        'total_anggota' => 18,
        'kapasitas_max' => 15,
        'jumlah_perahu' => 3,
        'dokumen_pendukung' => UploadedFile::fake()->image('surat-baru.jpg'),
        'setuju_syarat' => '1',
    ])->assertOk()->assertSee('Pengajuan ulang terkirim');

    $komunitas->refresh();
    expect($komunitas->status_pendaftaran)->toBe('menunggu');
    expect($komunitas->alasan_tolak_registrasi)->toBeNull();
    expect($komunitas->nama)->toBe('Komunitas Relawan Pantai Bersatu');
    expect(AuditLog::where('aksi', 'komunitas.ajukan_ulang')->where('subjek_id', $komunitas->id)->exists())->toBeTrue();

    // 7. Admin menyetujui pendaftaran
    $this->actingAs($admin)->post(route('admin.komunitas.setujui', $komunitas))
        ->assertRedirect(route('admin.komunitas.index'))
        ->assertSessionHas('success');

    $komunitas->refresh();
    $user->refresh();
    expect($komunitas->status_pendaftaran)->toBe('disetujui');
    expect($user->is_active)->toBeTrue();
    Notification::assertSentTo($user, PendaftaranDisetujuiNotification::class);
    expect(AuditLog::where('aksi', 'komunitas.setujui')->where('subjek_id', $komunitas->id)->exists())->toBeTrue();

    // 8. Komunitas kini bisa login dan mengakses portal komunitas
    $this->post('/logout');
    $this->post('/login', [
        'email' => 'pantai@alir.test',
        'password' => 'rahasia123',
    ])->assertRedirect(route('komunitas.dashboard'));

    $this->get('/komunitas')->assertOk()->assertSee('Komunitas Relawan Pantai Bersatu');
});
