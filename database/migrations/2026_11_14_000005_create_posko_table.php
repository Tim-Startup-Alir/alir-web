<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->cascadeOnDelete();
            $table->string('nama');
            $table->string('alamat')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_m')->default(1000); // radius area layanan
            // FR-02: posko boleh di luar 3 kelurahan pilot, tetapi komunitas wajib menyatakan siap dengan risikonya
            $table->timestamp('pernyataan_luar_cakupan_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posko');
    }
};
