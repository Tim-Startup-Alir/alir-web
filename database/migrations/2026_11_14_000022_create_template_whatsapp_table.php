<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('status_laporan', 30)->unique(); // nilai sama dengan laporan_banjir.status
            $table->text('isi_pesan'); // placeholder: {kode}, {lokasi}, {keterangan}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_whatsapp');
    }
};
