<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_permintaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_logistik_id')->constrained('permintaan_logistik')->cascadeOnDelete();
            $table->foreignId('inventaris_id')->constrained('inventaris')->restrictOnDelete();
            $table->unsignedInteger('jumlah_diminta');
            $table->unsignedInteger('jumlah_disetujui')->nullable();
            $table->timestamps();

            $table->unique(['permintaan_logistik_id', 'inventaris_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_permintaan');
    }
};
