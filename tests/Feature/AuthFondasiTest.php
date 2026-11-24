<?php

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\User;
use App\Models\Wilayah;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
});

test('tamu diarahkan ke halaman login saat mengakses panel admin atau komunitas', function () {
    $this->get('/admin')->assertRedirect(route('login'));
    $this->get('/komunitas')->assertRedirect(route('login'));
});

test('admin dapat login, last_login_at terbarui, tercatat audit log, dan diarahkan ke dashboard admin', function () {
    $response = $this->post('/login', [
        'email' => 'admin@alir.test',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticated();

    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    expect($admin->last_login_at)->not->toBeNull();
    expect(AuditLog::where('aksi', 'auth.login')->where('user_id', $admin->id)->exists())->toBeTrue();

    $this->get('/admin')->assertOk()->assertSee('Ringkasan');
});

test('komunitas dengan wajib_ganti_sandi diarahkan ke halaman ganti sandi sebelum masuk portal', function () {
    $response = $this->post('/login', [
        'email' => 'komunitas.tirto@alir.test',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('password.force.edit'));
    $this->get('/komunitas')->assertRedirect(route('password.force.edit'));
    $this->get(route('password.force.edit'))
        ->assertOk()
        ->assertSee('data-toggle-password="password_confirmation"', false);

    $this->put('/ganti-sandi', [
        'password' => 'sandibaru123',
        'password_confirmation' => 'sandibaru123',
    ])->assertRedirect(route('komunitas.dashboard'));

    $user = User::where('email', 'komunitas.tirto@alir.test')->firstOrFail();
    expect($user->wajib_ganti_sandi)->toBeFalse();

    $this->get('/komunitas')->assertOk()->assertSee('Komunitas Relawan Tirto');
});

test('komunitas wajib ganti sandi ditolak jika memakai kata sandi yang sama dengan sandi awal', function () {
    $this->post('/login', [
        'email' => 'komunitas.tirto@alir.test',
        'password' => 'password',
    ])->assertRedirect(route('password.force.edit'));

    $this->from('/ganti-sandi')
        ->put('/ganti-sandi', [
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect('/ganti-sandi')
        ->assertSessionHasErrors('password');

    expect(session('errors')->first('password'))->toContain('tidak boleh sama');

    $user = User::where('email', 'komunitas.tirto@alir.test')->firstOrFail();
    expect($user->wajib_ganti_sandi)->toBeTrue();
    expect(AuditLog::where('aksi', 'auth.ganti_sandi')->where('user_id', $user->id)->exists())->toBeFalse();

    $this->get('/komunitas')->assertRedirect(route('password.force.edit'));
});

test('akun nonaktif ditolak saat login dan bila dinonaktifkan saat sesi berjalan', function () {
    $user = User::factory()->nonaktif()->create([
        'email' => 'nonaktif@alir.test',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'nonaktif@alir.test',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();

    // Simulasi akun dinonaktifkan setelah login
    $aktif = User::factory()->admin()->create(['is_active' => true]);
    $this->actingAs($aktif);
    $aktif->update(['is_active' => false]);

    $this->get('/admin')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('role yang salah mendapat respon 403 dari middleware role', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $this->actingAs($admin)->get('/komunitas')->assertForbidden();

    $komunitas = User::where('email', 'komunitas.tirto@alir.test')->firstOrFail();
    $komunitas->update(['wajib_ganti_sandi' => false]);
    $this->actingAs($komunitas)->get('/admin')->assertForbidden();
});

test('komunitas yang belum verifikasi email diarahkan ke halaman verifikasi email', function () {
    $user = User::factory()->unverified()->create(['wajib_ganti_sandi' => false]);
    $wilayah = Wilayah::firstOrFail();
    KomunitasRelawan::create([
        'user_id' => $user->id,
        'wilayah_id' => $wilayah->id,
        'nama' => 'Komunitas Uji',
        'nama_pic' => 'PIC Uji',
        'nomor_whatsapp_pic' => '6281234567890',
        'status_pendaftaran' => 'disetujui',
    ]);

    $this->actingAs($user)->get('/komunitas')->assertRedirect(route('verification.notice'));
});

test('login gagal berulang dibatasi oleh rate limiter', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'admin@alir.test',
            'password' => 'salah-sandi',
        ])->assertSessionHasErrors('email');
    }

    $response = $this->post('/login', [
        'email' => 'admin@alir.test',
        'password' => 'salah-sandi',
    ]);

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toContain('Terlalu banyak percobaan');
});

test('lupa dan reset kata sandi berfungsi dan menghapus wajib_ganti_sandi', function () {
    $user = User::where('email', 'komunitas.tirto@alir.test')->firstOrFail();
    $token = Password::createToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Atur ulang kata sandi')
        ->assertSee('data-toggle-password="password_confirmation"', false);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'sandireset123',
        'password_confirmation' => 'sandireset123',
    ])->assertRedirect(route('login'));

    $user->refresh();
    expect($user->wajib_ganti_sandi)->toBeFalse();
    expect(AuditLog::where('aksi', 'auth.reset_sandi')->where('user_id', $user->id)->exists())->toBeTrue();
});

test('pengguna dapat logout dengan bersih', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    $this->actingAs($admin)
        ->post('/logout')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
