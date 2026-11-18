<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_stok', function (Blueprint $table) {
            $table->id();
            // restrict: item inventaris yang sudah punya riwayat mutasi tidak bisa dihapus (FR-10)
            $table->foreignId('inventaris_id')->constrained('inventaris')->restrictOnDelete();
            $table->foreignId('permintaan_logistik_id')->nullable()->constrained('permintaan_logistik')->nullOnDelete();
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->unsignedInteger('jumlah');
            $table->string('keterangan')->nullable(); // sumber (masuk) atau tujuan (keluar)
            $table->dateTime('tanggal');
            $table->timestamps();

            $table->index(['inventaris_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_stok');
    }
};
