<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_donasi', function (Blueprint $table) {
            $table->id();
            // Donasi hanya berdasarkan wishlist (tidak ada donasi umum), jadi wajib diisi.
            // restrict: wishlist yang sudah menerima donasi tidak bisa dihapus, hanya ditutup (FR-13).
            $table->foreignId('wishlist_donasi_id')->constrained('wishlist_donasi')->restrictOnDelete();
            $table->string('order_id', 64)->unique(); // order_id Midtrans
            $table->string('snap_token')->nullable();
            $table->string('nama')->nullable();
            $table->unsignedBigInteger('nominal'); // rupiah
            $table->boolean('anonim')->default(false);
            $table->string('metode_pembayaran', 50)->nullable();
            $table->enum('status_pembayaran', ['pending', 'berhasil', 'gagal', 'kedaluwarsa'])->default('pending');
            $table->timestamp('waktu_bayar')->nullable();
            $table->timestamps();

            $table->index('status_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_donasi');
    }
};
