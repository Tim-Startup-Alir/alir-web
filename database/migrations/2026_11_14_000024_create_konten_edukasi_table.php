<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konten_edukasi', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->enum('kategori', [
                'pencegahan', 'kesiapsiagaan', 'saat_banjir', 'setelah_banjir', 'fun_fact', 'mitos_fakta',
            ]);
            $table->enum('tipe', ['artikel', 'infografis', 'video_sematan']);
            $table->longText('isi')->nullable();
            $table->string('media_url')->nullable();
            $table->enum('status', ['draft', 'terbit'])->default('draft');
            $table->timestamp('diterbitkan_pada')->nullable();
            $table->timestamps();

            $table->index(['status', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konten_edukasi');
    }
};
