<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi aturan bisnis ALIR
|--------------------------------------------------------------------------
| Nilai-nilai ambang yang menurut proposal "dapat disesuaikan setelah uji coba".
| Semua dapat diubah lewat .env tanpa mengubah kode.
*/

return [

    // F-03 Pemetaan Titik Banjir
    'peta' => [
        'batas_marker' => (int) env('ALIR_PETA_BATAS_MARKER', 500),
        'pusat_default' => [(float) env('ALIR_PETA_PUSAT_LAT', -6.8845858), (float) env('ALIR_PETA_PUSAT_LNG', 109.6704618)],
        'zoom_default' => (int) env('ALIR_PETA_ZOOM_DEFAULT', 13),
    ],

    // FR-25 / FR-26: laporan sekitar dan tingkat kepercayaan
    'kepercayaan' => [
        'radius_m' => (int) env('ALIR_KLUSTER_RADIUS_M', 500),
        'rentang_jam' => (int) env('ALIR_KLUSTER_RENTANG_JAM', 24),
        // independen = nomor WA pelapor berbeda; laporan yang digabung tetap dihitung untuk induknya
        // tinggi: konfirmasi lapangan "sesuai" ATAU >= laporan_tinggi pelapor independen
        'laporan_tinggi' => (int) env('ALIR_KEPERCAYAAN_LAPORAN_TINGGI', 3),
        // sedang: >= laporan_sedang pelapor independen; opsi foto dikendalikan flag di bawah.
        'laporan_sedang' => (int) env('ALIR_KEPERCAYAAN_LAPORAN_SEDANG', 2),
        // F-03 Pemetaan Titik Banjir: foto hanya menaikkan level bila diaktifkan setelah uji coba.
        'foto_menaikkan_ke_sedang' => (bool) env('ALIR_KEPERCAYAAN_FOTO_SEDANG', false),
    ],

    // F-03 Pemetaan Titik Banjir
    'konfirmasi' => [
        'radius_terdekat_m' => (int) env('ALIR_KONFIRMASI_RADIUS_M', 3000),
        'foto_maks_kb' => (int) env('ALIR_KONFIRMASI_FOTO_MAKS_KB', 5120),
    ],

    // F-02 Pelaporan Banjir (PBI-004)
    'laporan' => [
        'foto_maks_kb' => (int) env('ALIR_LAPORAN_FOTO_MAKS_KB', 5120),
        // NFR-02: batas kirim laporan per IP
        'batas_per_menit' => (int) env('ALIR_LAPORAN_BATAS_PER_MENIT', 5),
        'batas_per_jam' => (int) env('ALIR_LAPORAN_BATAS_PER_JAM', 20),
        // pelacakan laporan lewat kode: batas pencarian per IP
        'lacak_per_menit' => (int) env('ALIR_LACAK_PER_MENIT', 10),
        'lacak_per_jam' => (int) env('ALIR_LACAK_PER_JAM', 60),
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
