<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisasi_pembelian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_donasi_id')->constrained('wishlist_donasi')->restrictOnDelete();
            $table->string('nama_barang');
            $table->unsignedInteger('jumlah');
            $table->unsignedBigInteger('harga_satuan'); // rupiah
            $table->string('vendor')->nullable();
            $table->string('nomor_invoice')->nullable();
            $table->date('tanggal');
            $table->string('foto_nota_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_pembelian');
    }
};
