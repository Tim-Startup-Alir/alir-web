<?php

use App\Models\KomunitasRelawan;
use App\Models\User;
use App\Models\Wilayah;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class]);
});

test('dusk alur registrasi komunitas, verifikasi email, tolak & ajukan ulang, setujui admin, hingga login', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $tirto = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    $tempFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'alir_dusk_doc.pdf';
    file_put_contents($tempFile, "%PDF-1.4\n%Test\n");

    try {
        $this->browse(function (Browser $browser) use ($admin, $tirto, $tempFile) {
            // 1. Dari halaman login klik Daftar di sini -> isi form registrasi
            $browser->visit('/login')
                ->clickLink('Daftar di sini')
                ->waitForLocation('/daftar')
                ->waitForText('Daftar relawan')
                ->type('nama', 'Komunitas Pesisir Dusk')
                ->select('wilayah_id', (string) $tirto->id)
                ->type('posko_latitude', (string) $tirto->latitude)
                ->type('posko_longitude', (string) $tirto->longitude)
                ->type('nama_pic', 'Sari Pesisir')
                ->type('nomor_whatsapp_pic', '081234567800')
                ->type('email', 'pesisir.dusk@alir.test')
                ->type('total_anggota', '14')
                ->type('jumlah_perahu', '2')
                ->attach('dokumen_pendukung', $tempFile)
                ->type('password', 'sandiku123')
                ->type('password_confirmation', 'sandiku123')
                ->check('setuju_syarat')
                ->click('@register-submit')
                ->waitForLocation('/daftar/terkirim')
                ->waitForText('Pendaftaran terkirim');

            $user = User::where('email', 'pesisir.dusk@alir.test')->firstOrFail();
            $komunitas = $user->komunitasRelawan;

            // 2. Coba login sebelum verifikasi & persetujuan -> ditolak
            $browser->visit('/login')
                ->type('email', 'pesisir.dusk@alir.test')
                ->type('password', 'sandiku123')
                ->click('@login-submit')
                ->waitForText('Email pendaftaran Anda belum diverifikasi');

            // 3. Klik link verifikasi email
            $verifyUrl = URL::temporarySignedRoute('register.verify-email', now()->addHour(), [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]);
            $browser->visit($verifyUrl)
                ->waitForText('Email berhasil diverifikasi');

            // 4. Admin melihat antrean dan menolak dengan alasan
            $browser->loginAs($admin)
                ->visit('/admin/komunitas')
                ->waitForText('Antrean Pendaftaran Baru')
                ->assertSee('Komunitas Pesisir Dusk')
                ->within("@pendaftaran-card-{$komunitas->id}", function (Browser $card) use ($komunitas) {
                    $card->type('alasan_tolak_registrasi', 'Nama posko mohon dilengkapi')
                        ->click("@tolak-komunitas-{$komunitas->id}");
                })
                ->waitForText('tautan perbaikan telah dikirim');

            // 5. Buka signed URL perbaikan dan ajukan ulang
            $perbaikanUrl = URL::temporarySignedRoute('register.perbaikan.edit', now()->addDays(7), [
                'komunitas' => $komunitas->id,
            ]);
            $browser->visit($perbaikanUrl)
                ->waitForText('Nama posko mohon dilengkapi')
                ->clear('posko_nama')
                ->type('posko_nama', 'Posko Pesisir Utama')
                ->click('@perbaikan-submit')
                ->waitForText('Pengajuan ulang terkirim');

            // 6. Admin menyetujui pendaftaran
            $browser->loginAs($admin)
                ->visit('/admin/komunitas')
                ->waitForText('Antrean Pendaftaran Baru')
                ->click("@setujui-komunitas-{$komunitas->id}")
                ->waitForText('disetujui dan notifikasi email telah dikirim');

            // 7. Logout admin lalu login sebagai komunitas baru
            $browser->click('@logout-button')
                ->waitForLocation('/login')
                ->type('email', 'pesisir.dusk@alir.test')
                ->type('password', 'sandiku123')
                ->click('@login-submit')
                ->waitForLocation('/komunitas')
                ->waitForText('Komunitas Pesisir Dusk');
        });
    } finally {
        @unlink($tempFile);
        foreach (KomunitasRelawan::where('nama', 'Komunitas Pesisir Dusk')->get() as $k) {
            if ($k->dokumen_pendukung_path) {
                Storage::disk('local')->delete($k->dokumen_pendukung_path);
            }
        }
    }
});
