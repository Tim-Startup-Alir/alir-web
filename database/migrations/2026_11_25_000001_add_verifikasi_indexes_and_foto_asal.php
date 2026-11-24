<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_banjir', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'laporan_banjir_status_created_at_index');
            $table->index('laporan_banjir_induk_id', 'laporan_banjir_induk_id_index');
        });

        Schema::table('foto_laporan', function (Blueprint $table) {
            $table->foreignId('id_laporan_asal')
                ->nullable()
                ->after('laporan_banjir_id')
                ->constrained('laporan_banjir')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('foto_laporan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_laporan_asal');
        });

        Schema::table('laporan_banjir', function (Blueprint $table) {
            $table->dropIndex('laporan_banjir_status_created_at_index');
            $table->dropIndex('laporan_banjir_induk_id_index');
        });
    }
};
