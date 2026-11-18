<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data cuaca dari API BMKG / OpenWeather, disimpan apa adanya sebagai informasi (bukan prediksi ALIR).
 */
#[Table('cuaca')]
#[Fillable([
    'wilayah_id', 'sumber', 'jenis', 'suhu_c', 'kelembaban', 'kondisi', 'curah_hujan_mm',
    'kecepatan_angin_kmh', 'pasang_laut_m', 'waktu_data', 'diambil_pada',
])]
class Cuaca extends Model
{
    protected function casts(): array
    {
        return [
            'suhu_c' => 'decimal:2',
            'kelembaban' => 'integer',
            'curah_hujan_mm' => 'decimal:2',
            'kecepatan_angin_kmh' => 'decimal:2',
            'pasang_laut_m' => 'decimal:2',
            'waktu_data' => 'datetime',
            'diambil_pada' => 'datetime',
        ];
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }
}
