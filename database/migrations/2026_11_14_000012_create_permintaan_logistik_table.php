<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_logistik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('komunitas_relawan_id')->constrained('komunitas_relawan')->restrictOnDelete();
            $table->foreignId('penugasan_id')->constrained('penugasan')->restrictOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('tenggat')->nullable();
            $table->enum('status', [
                'diajukan', 'disetujui', 'disetujui_sebagian', 'ditolak', 'dibatalkan', 'didistribusikan',
            ])->default('diajukan');
            $table->text('alasan')->nullable(); // alasan penolakan
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_logistik');
    }
};
