<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NFR-05: siapa (user_id, null = sistem/webhook), kapan (waktu), apa (aksi + subjek + deskripsi).
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 100); // mis. laporan.verifikasi, stok.mutasi, donasi.status
            $table->nullableMorphs('subjek');
            $table->text('deskripsi')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('waktu')->useCurrent();

            $table->index('aksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
