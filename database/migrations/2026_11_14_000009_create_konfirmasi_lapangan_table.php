<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfirmasi_lapangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_banjir_id')->constrained('laporan_banjir')->cascadeOnDelete();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->restrictOnDelete();
            // FR-27 / UC-23 A3: admin dapat meminta konfirmasi lebih dulu (status diminta), lalu komunitas menjawab.
            // Konfirmasi atas inisiatif komunitas sendiri langsung berstatus dijawab dan diminta_oleh = null.
            $table->enum('status', ['diminta', 'dijawab'])->default('diminta');
            $table->foreignId('diminta_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diminta_pada')->nullable();
            $table->enum('hasil', ['sesuai', 'tidak_sesuai', 'tidak_ditemukan'])->nullable(); // diisi saat dijawab
            $table->string('foto_path')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('dijawab_pada')->nullable();
            $table->timestamps();

            $table->index(['komunitas_relawan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfirmasi_lapangan');
    }
};
