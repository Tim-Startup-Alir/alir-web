<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konfirmasi_lapangan', function (Blueprint $table): void {
            $table->enum('hasil', [
                'sesuai', 'tidak_sesuai', 'tidak_ditemukan', 'terkonfirmasi', 'tidak_terkonfirmasi',
            ])->nullable()->change();
        });

        DB::table('konfirmasi_lapangan')
            ->whereIn('hasil', ['sesuai'])
            ->update(['hasil' => 'terkonfirmasi']);

        DB::table('konfirmasi_lapangan')
            ->whereIn('hasil', ['tidak_sesuai', 'tidak_ditemukan'])
            ->update(['hasil' => 'tidak_terkonfirmasi']);

        Schema::table('konfirmasi_lapangan', function (Blueprint $table): void {
            $table->enum('hasil', ['terkonfirmasi', 'tidak_terkonfirmasi'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('konfirmasi_lapangan', function (Blueprint $table): void {
            $table->enum('hasil', [
                'sesuai', 'tidak_sesuai', 'tidak_ditemukan', 'terkonfirmasi', 'tidak_terkonfirmasi',
            ])->nullable()->change();
        });

        DB::table('konfirmasi_lapangan')
            ->where('hasil', 'terkonfirmasi')
            ->update(['hasil' => 'sesuai']);

        DB::table('konfirmasi_lapangan')
            ->where('hasil', 'tidak_terkonfirmasi')
            ->update(['hasil' => 'tidak_sesuai']);

        Schema::table('konfirmasi_lapangan', function (Blueprint $table): void {
            $table->enum('hasil', ['sesuai', 'tidak_sesuai', 'tidak_ditemukan'])->nullable()->change();
        });
    }
};
