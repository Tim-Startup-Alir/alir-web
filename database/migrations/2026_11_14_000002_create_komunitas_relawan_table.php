<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komunitas_relawan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('wilayah_id')->constrained('wilayah')->restrictOnDelete(); // kelurahan layanan
            $table->string('nama');
            $table->string('nama_pic');
            $table->string('nomor_whatsapp_pic', 20);
            $table->unsignedInteger('total_anggota')->default(0);
            $table->unsignedInteger('kapasitas_max')->default(0);
            $table->unsignedInteger('jumlah_perahu')->default(0);
            $table->enum('status_pendaftaran', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->string('dokumen_pendukung_path')->nullable();
            $table->text('alasan_tolak_registrasi')->nullable();
            $table->boolean('izin_tampil_publik')->default(false); // NFR-12/FR-29: posko & area layanan di peta publik
            $table->timestamp('menyetujui_syarat_pada')->nullable(); // FR-02: centang pernyataan persetujuan saat registrasi
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();

            $table->index('status_pendaftaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komunitas_relawan');
    }
};
