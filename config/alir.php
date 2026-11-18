<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi aturan bisnis ALIR
|--------------------------------------------------------------------------
| Nilai-nilai ambang yang menurut proposal "dapat disesuaikan setelah uji coba".
| Semua dapat diubah lewat .env tanpa mengubah kode.
*/

return [

    // FR-25 / FR-26: laporan sekitar dan tingkat kepercayaan
    'kepercayaan' => [
        'radius_m' => (int) env('ALIR_KLUSTER_RADIUS_M', 500),
        'rentang_jam' => (int) env('ALIR_KLUSTER_RENTANG_JAM', 24),
        // independen = nomor WA pelapor berbeda; laporan yang digabung tetap dihitung untuk induknya
        // tinggi: konfirmasi lapangan "sesuai" ATAU >= laporan_tinggi pelapor independen
        'laporan_tinggi' => (int) env('ALIR_KEPERCAYAAN_LAPORAN_TINGGI', 3),
        // sedang: >= laporan_sedang pelapor independen (foto wajib, jadi bukan pembeda lagi)
        'laporan_sedang' => (int) env('ALIR_KEPERCAYAAN_LAPORAN_SEDANG', 2),
    ],

    // FR-24: radius cadangan cakupan kelurahan bila batas_wilayah (GeoJSON) belum diisi
    'wilayah' => [
        'radius_cakupan_default_m' => (int) env('ALIR_RADIUS_CAKUPAN_M', 1500),
    ],

    // FR-10 / mockup Inventaris: level stok aman, menipis, kritis
    'stok' => [
        // kritis jika stok <= persen_kritis% dari stok_minimum (atau stok habis)
        'persen_kritis' => (int) env('ALIR_STOK_PERSEN_KRITIS', 50),
    ],

    // FR-14: batas minimum nominal donasi (rupiah); donasi hanya lewat wishlist
    'donasi' => [
        'nominal_minimum' => (int) env('ALIR_DONASI_MINIMUM', 10000),
    ],

];
