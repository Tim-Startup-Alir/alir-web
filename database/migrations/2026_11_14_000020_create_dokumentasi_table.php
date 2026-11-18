<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumentasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->restrictOnDelete();
            $table->foreignId('laporan_banjir_id')->nullable()->constrained('laporan_banjir')->nullOnDelete(); // FR-17/18
            $table->foreignId('penugasan_id')->nullable()->constrained('penugasan')->nullOnDelete();
            $table->foreignId('distribusi_bantuan_id')->nullable()->constrained('distribusi_bantuan')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->enum('jenis_media', ['foto', 'video']);
            $table->string('media_path');
            $table->enum('status_moderasi', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('alasan_tolak')->nullable();
            $table->timestamps();

            $table->index('status_moderasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumentasi');
    }
};
