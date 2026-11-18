<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_kesiapsiagaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->cascadeOnDelete();
            $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu']); // hari shift dimulai
            $table->string('nama_shift', 50)->nullable(); // bebas, mis. "Pagi"; null = label otomatis dari jam_mulai
            $table->time('jam_mulai');
            $table->time('jam_selesai'); // jika <= jam_mulai, shift berlanjut melewati tengah malam ke hari berikutnya
            $table->enum('status_ketersediaan', ['tersedia', 'siaga_terbatas', 'tidak_tersedia'])->default('tersedia');
            $table->string('nama_pic'); // penanggung jawab shift, dihubungi saat gawat
            $table->string('nomor_whatsapp_pic', 20);
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->index(['komunitas_relawan_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_kesiapsiagaan');
    }
};
