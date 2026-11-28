<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Halaman publik Donasi (wishlist posko + donatur terbaru). Saat ini FE saja: data contoh.
 * TODO (PBI donasi): ganti $wishlist dengan WishlistDonasi, $donatur dengan TransaksiDonasi,
 * dan sambungkan tombol "Lanjut ke Pembayaran" (modal di resources/js/publik/donasi.js) ke payment gateway.
 */
class DonasiController extends Controller
{
    public function index(): View
    {
        $ago = fn (int $menit) => now()->subMinutes($menit)->toIso8601String();

        return view('donasi.index', [
            'statistik' => ['dana_masuk_jt' => 482.5, 'dialokasikan_jt' => 371.2, 'persen' => 77, 'donatur' => 1284, 'relawan' => 38, 'kebutuhan' => 6],
            'wishlist' => [
                ['id' => 'W-01', 'nama' => 'Beras 5 kg', 'foto' => 'sembako-bnpb.jpg', 'urgensi' => 'Kritis', 'harga' => 68000, 'target' => 400, 'terkumpul' => 312, 'satuan' => 'paket', 'oleh' => 'Komunitas Sigap Pantura', 'kategori' => 'Pangan'],
                ['id' => 'W-02', 'nama' => 'Selimut & Matras', 'foto' => 'relawan-padang-2.jpg', 'urgensi' => 'Mendesak', 'harga' => 85000, 'target' => 250, 'terkumpul' => 143, 'satuan' => 'set', 'oleh' => 'PMI Relawan Kota', 'kategori' => 'Sandang'],
                ['id' => 'W-03', 'nama' => 'Popok Bayi (isi 30)', 'foto' => 'evakuasi-pekalongan.jpg', 'urgensi' => 'Mendesak', 'harga' => 62000, 'target' => 180, 'terkumpul' => 96, 'satuan' => 'pak', 'oleh' => 'Karang Taruna Bandengan', 'kategori' => 'Sandang'],
                ['id' => 'W-04', 'nama' => 'Air Mineral 1 Dus', 'foto' => 'logistik-pesawat.jpg', 'urgensi' => 'Normal', 'harga' => 48000, 'target' => 500, 'terkumpul' => 455, 'satuan' => 'dus', 'oleh' => 'Relawan Pekalongan Tanggap', 'kategori' => 'Pangan'],
                ['id' => 'W-05', 'nama' => 'Kotak P3K Lengkap', 'foto' => 'heli-bantuan.jpg', 'urgensi' => 'Kritis', 'harga' => 145000, 'target' => 60, 'terkumpul' => 21, 'satuan' => 'kotak', 'oleh' => 'PMI Relawan Kota', 'kategori' => 'Papan'],
                ['id' => 'W-06', 'nama' => 'Pompa Air Diesel', 'foto' => 'banjir-bintara.jpg', 'urgensi' => 'Mendesak', 'harga' => 2850000, 'target' => 8, 'terkumpul' => 3, 'satuan' => 'unit', 'oleh' => 'Banser Peduli Banjir', 'kategori' => 'Papan'],
            ],
            'donatur' => [
                ['nama' => 'Hamba Allah', 'anonim' => true, 'barang' => 'Beras 5 kg', 'jumlah' => 10, 'nominal' => 680000, 'waktu' => $ago(2)],
                ['nama' => 'Rina Kusuma', 'anonim' => false, 'barang' => 'Selimut & Matras', 'jumlah' => 2, 'nominal' => 170000, 'waktu' => $ago(11)],
                ['nama' => 'PT Batik Nusantara', 'anonim' => false, 'barang' => 'Pompa Air Diesel', 'jumlah' => 1, 'nominal' => 2850000, 'waktu' => $ago(27)],
                ['nama' => 'Hamba Allah', 'anonim' => true, 'barang' => 'Air Mineral 1 Dus', 'jumlah' => 5, 'nominal' => 240000, 'waktu' => $ago(41)],
                ['nama' => 'Dimas Prasetyo', 'anonim' => false, 'barang' => 'Popok Bayi', 'jumlah' => 3, 'nominal' => 186000, 'waktu' => $ago(60)],
                ['nama' => 'Komunitas Alumni SMAN 1', 'anonim' => false, 'barang' => 'Kotak P3K', 'jumlah' => 4, 'nominal' => 580000, 'waktu' => $ago(120)],
                ['nama' => 'Siti Aminah', 'anonim' => false, 'barang' => 'Beras 5 kg', 'jumlah' => 2, 'nominal' => 136000, 'waktu' => $ago(180)],
            ],
        ]);
    }
}
