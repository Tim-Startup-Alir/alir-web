<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->restrictOnDelete();
            $table->string('nama_barang');
            // Mengikuti class diagram (FR-10 perlu direvisi: makanan -> pangan, obat -> kesehatan, + papan)
            $table->enum('kategori', ['pangan', 'sandang', 'papan', 'kesehatan', 'peralatan']);
            $table->string('satuan', 30);
            $table->unsignedInteger('stok_minimum')->default(0);
            $table->timestamps();
            // Stok tidak disimpan; dihitung dari mutasi_stok (masuk - keluar).

            $table->unique(['komunitas_relawan_id', 'nama_barang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris');
    }
};
