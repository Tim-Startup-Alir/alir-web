<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuaca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wilayah_id')->constrained('wilayah')->cascadeOnDelete();
            $table->enum('sumber', ['bmkg', 'openweather']);
            $table->enum('jenis', ['terkini', 'prakiraan']); // data apa adanya dari API, bukan prediksi ALIR
            $table->decimal('suhu_c', 5, 2)->nullable();
            $table->unsignedTinyInteger('kelembaban')->nullable();
            $table->string('kondisi')->nullable();
            $table->decimal('curah_hujan_mm', 6, 2)->nullable();
            $table->decimal('kecepatan_angin_kmh', 6, 2)->nullable();
            $table->decimal('pasang_laut_m', 5, 2)->nullable();
            $table->dateTime('waktu_data');
            $table->dateTime('diambil_pada');
            $table->timestamps();

            $table->unique(['wilayah_id', 'sumber', 'jenis', 'waktu_data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuaca');
    }
};
