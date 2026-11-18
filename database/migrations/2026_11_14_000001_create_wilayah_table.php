<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('kode_adm4', 20)->unique(); // kode kelurahan BMKG
            $table->string('nama_kelurahan');
            $table->string('kecamatan');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->json('batas_wilayah')->nullable(); // GeoJSON Polygon/MultiPolygon (utama untuk FR-24)
            $table->unsignedInteger('radius_cakupan_m')->nullable(); // cadangan FR-24 bila batas_wilayah belum ada
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
