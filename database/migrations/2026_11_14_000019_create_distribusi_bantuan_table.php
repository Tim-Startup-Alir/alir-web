<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aturan aplikasi: minimal satu sumber (realisasi_pembelian_id / permintaan_logistik_id)
        // dan minimal satu tujuan (laporan_banjir_id / posko_id).
        Schema::create('distribusi_bantuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->restrictOnDelete();
            $table->foreignId('laporan_banjir_id')->nullable()->constrained('laporan_banjir')->restrictOnDelete();
            $table->foreignId('posko_id')->nullable()->constrained('posko')->restrictOnDelete();
            $table->foreignId('realisasi_pembelian_id')->nullable()->constrained('realisasi_pembelian')->restrictOnDelete();
            $table->foreignId('permintaan_logistik_id')->nullable()->constrained('permintaan_logistik')->restrictOnDelete();
            $table->string('item');
            $table->unsignedInteger('jumlah');
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribusi_bantuan');
    }
};
