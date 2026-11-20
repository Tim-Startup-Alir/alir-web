<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('komunitas_keterampilan');
    }

    public function down(): void
    {
        // Fitur keterampilan dihapus permanen sesuai keputusan tim (PRD v3).
    }
};
