<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_donasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->restrictOnDelete();
            $table->string('nama_barang');
            $table->string('gambar_path')->nullable();
            $table->enum('urgensi', ['kritis', 'mendesak', 'normal'])->default('normal');
            $table->unsignedBigInteger('harga_satuan'); // rupiah
            $table->unsignedInteger('target_jumlah');
            $table->string('satuan', 30);
            $table->enum('kategori', ['pangan', 'sandang', 'papan']);
            $table->enum('status', ['aktif', 'terpenuhi', 'ditutup'])->default('aktif');
            $table->date('tenggat')->nullable();
            $table->timestamps();
            // Target dana = harga_satuan x target_jumlah;
            // dana terkumpul = SUM(transaksi_donasi.nominal) yang status_pembayaran = berhasil.

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_donasi');
    }
};
