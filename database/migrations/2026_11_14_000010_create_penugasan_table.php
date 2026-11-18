<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_banjir_id')->constrained('laporan_banjir')->restrictOnDelete();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->restrictOnDelete();
            $table->foreignId('ditugaskan_oleh')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('jumlah_personil_tugas')->default(0);
            $table->unsignedInteger('armada_perahu_tugas')->default(0);
            $table->decimal('jarak_km', 8, 3)->nullable(); // snapshot jarak Haversine saat ditugaskan
            $table->decimal('skor_rekomendasi', 6, 2)->nullable();
            $table->enum('stage', [
                'menunggu', 'diterima', 'ditolak', 'menuju_lokasi', 'ditangani', 'selesai', 'dibatalkan',
            ])->default('menunggu');
            $table->text('catatan')->nullable(); // catatan umum dari Tim Startup ALIR saat menugaskan
            $table->json('catatan_tahap')->nullable(); // FR-09: catatan opsional per tahap, {"menuju_lokasi": "...", ...}
            $table->text('alasan_tolak')->nullable();
            $table->timestamp('waktu_diterima')->nullable();
            $table->timestamp('waktu_menuju_lokasi')->nullable();
            $table->timestamp('waktu_ditangani')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->timestamps();

            $table->index(['komunitas_relawan_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan');
    }
};
