<?php

use Laravel\Dusk\Browser;

test('basic example', function () {
    $this->browse(function (Browser $browser) {
        // halaman welcome bawaan Laravel sudah diganti beranda publik ALIR
        $browser->visit('/')
            ->waitForText('Bantuan bergerak.');
    });
});
