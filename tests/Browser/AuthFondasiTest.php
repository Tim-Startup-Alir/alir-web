<?php

use App\Models\User;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
});

test('tamu diarahkan ke login saat membuka panel admin dan komunitas', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/admin')
            ->waitForLocation('/login')
            ->assertPathIs('/login')
            ->visit('/komunitas')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});

test('admin dapat login, mengakses panel admin, ditolak 403 di panel komunitas, dan logout', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->type('email', 'admin@alir.test')
            ->type('password', 'password')
            ->click('@login-submit')
            ->waitForLocation('/admin')
            ->assertPathIs('/admin')
            ->assertSee('Ringkasan')
            ->visit('/komunitas')
            ->assertSee('403')
            ->visit('/admin')
            ->click('@logout-button')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});

test('komunitas dengan wajib_ganti_sandi diarahkan ke ganti sandi, lalu masuk portal komunitas dan ditolak 403 di admin', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->type('email', 'komunitas.tirto@alir.test')
            ->type('password', 'password')
            ->click('@login-submit')
            ->waitForLocation('/ganti-sandi')
            ->assertPathIs('/ganti-sandi')
            ->waitForText('Ganti kata sandi awal')
            ->type('password', 'sandibaru123')
            ->type('password_confirmation', 'sandibaru123')
            ->click('@force-password-submit')
            ->waitForLocation('/komunitas')
            ->assertPathIs('/komunitas')
            ->assertSee('Komunitas Relawan Tirto')
            ->visit('/admin')
            ->assertSee('403')
            ->visit('/komunitas')
            ->click('@logout-button')
            ->waitForLocation('/login')
            ->assertPathIs('/login');
    });
});

test('akun nonaktif ditolak saat login', function () {
    User::where('email', 'komunitas.degayu@alir.test')->update(['is_active' => false]);

    $this->browse(function (Browser $browser) {
        $browser->visit('/login')
            ->type('email', 'komunitas.degayu@alir.test')
            ->type('password', 'password')
            ->click('@login-submit')
            ->waitForText('Akun Anda sedang dinonaktifkan')
            ->assertPathIs('/login');
    });
});
