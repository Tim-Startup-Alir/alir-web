<?php

use App\Enums\StatusLaporan;
use App\Http\Requests\Publik\StoreLaporanBanjirRequest;
use App\Models\LaporanBanjir;
use App\Services\LaporanBanjirService;

test('enum status laporan sinkron dengan kolom laporan_banjir.status', function () {
    expect(StatusLaporan::values())->toBe(LaporanBanjir::STATUS)
        ->and(StatusLaporan::MenungguVerifikasi->value)->toBe('menunggu_verifikasi')
        ->and(StatusLaporan::MenungguVerifikasi->label())->toBe('Menunggu Verifikasi')
        ->and(StatusLaporan::labelDari('ditolak'))->toBe('Ditolak');
});

test('normalisasi kode pencarian menerima variasi penulisan', function (string $input, string $hasil) {
    expect(LaporanBanjirService::normalisasiKode($input))->toBe($hasil);
})->with([
    ['ALR-7K2M9P', 'ALR-7K2M9P'],
    ['alr-7k2m9p', 'ALR-7K2M9P'],
    ['7k2m9p', 'ALR-7K2M9P'],
    [' ALR 7K2M9P ', 'ALR-7K2M9P'],
    ['', ''],
]);

test('karakter kode laporan tidak memuat huruf atau angka yang mirip', function () {
    expect(LaporanBanjirService::KARAKTER_KODE)->not->toContain('0')
        ->not->toContain('O')->not->toContain('1')->not->toContain('I');
});

test('normalisasi nomor whatsapp sesuai pola server', function (string $input, string $hasil, bool $valid) {
    $nomor = StoreLaporanBanjirRequest::normalisasiWa($input);

    expect($nomor)->toBe($hasil)
        ->and((bool) preg_match(StoreLaporanBanjirRequest::POLA_WA, $nomor))->toBe($valid);
})->with([
    ['0812 3456 7890', '081234567890', true],
    ['+62 812-3456-7890', '081234567890', true],
    ['6281234567890', '081234567890', true],
    ['(0812) 3456.789', '08123456789', true],
    ['0211234567', '0211234567', false],
    ['08123', '08123', false],
]);
