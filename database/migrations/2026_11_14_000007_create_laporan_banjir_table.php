<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_banjir', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_banjir_induk_id')->nullable()->constrained('laporan_banjir')->nullOnDelete(); // duplikat dari
            $table->foreignId('wilayah_id')->constrained('wilayah')->restrictOnDelete();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kode_pelacakan', 20)->unique(); // ALR-XXXXXX
            $table->string('nama_pelapor')->nullable(); // null = anonim
            $table->string('alamat_singkat');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->enum('sumber_lokasi', ['gps', 'pin'])->nullable(); // FR-24: GPS perangkat atau pin di peta
            $table->enum('severity', ['ringan', 'sedang', 'berat']);
            $table->unsignedInteger('jumlah_warga')->default(0);
            $table->enum('status', [
                'menunggu_verifikasi', 'terverifikasi', 'ditugaskan', 'ditangani', 'selesai', 'ditolak', 'digabung',
            ])->default('menunggu_verifikasi');
            $table->enum('tingkat_kepercayaan', ['tinggi', 'sedang', 'rendah'])->nullable();
            $table->text('catatan')->nullable();
            $table->string('nomor_wa_pelapor', 20);
            $table->boolean('setuju_dihubungi')->default(false);
            $table->boolean('sudah_dikabari')->default(false); // direset setiap status berubah
            $table->text('alasan_tolak')->nullable();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['latitude', 'longitude', 'created_at']); // cek kluster radius 500 m / 24 jam
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_banjir');
    }
};
