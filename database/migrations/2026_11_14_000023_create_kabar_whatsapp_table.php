<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Log kabar status ke pelapor yang dikirim manual lewat tautan wa.me (FR-30).
        Schema::create('kabar_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_banjir_id')->constrained('laporan_banjir')->cascadeOnDelete();
            $table->foreignId('template_whatsapp_id')->nullable()->constrained('template_whatsapp')->nullOnDelete();
            $table->foreignId('dikirim_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_laporan', 30);
            $table->string('nomor_tujuan', 20);
            $table->text('pesan');
            $table->timestamp('dikirim_pada');
            $table->timestamps();

            $table->index(['laporan_banjir_id', 'status_laporan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kabar_whatsapp');
    }
};
