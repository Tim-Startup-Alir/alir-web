<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('komunitas_keterampilan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->cascadeOnDelete();
            $table->string('nama_keterampilan');
            $table->unsignedInteger('jumlah_anggota_terampil')->default(0);
            $table->timestamps();

            $table->unique(['komunitas_relawan_id', 'nama_keterampilan'], 'komunitas_keterampilan_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komunitas_keterampilan');
    }
};
