<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('nomor_darurat')]
#[Fillable(['nama', 'nomor', 'keterangan', 'urutan', 'is_aktif'])]
class NomorDarurat extends Model
{
    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'is_aktif' => 'boolean',
        ];
    }
}
