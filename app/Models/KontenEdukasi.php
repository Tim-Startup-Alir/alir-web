<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('konten_edukasi')]
#[Fillable(['judul', 'slug', 'kategori', 'tipe', 'isi', 'media_url', 'status', 'diterbitkan_pada'])]
class KontenEdukasi extends Model
{
    public const KATEGORI = ['pencegahan', 'kesiapsiagaan', 'saat_banjir', 'setelah_banjir', 'fun_fact', 'mitos_fakta'];

    protected function casts(): array
    {
        return [
            'diterbitkan_pada' => 'datetime',
        ];
    }
}
