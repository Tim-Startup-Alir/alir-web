<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Table('konten_edukasi')]
#[Fillable(['judul', 'slug', 'kategori', 'tipe', 'isi', 'media_url', 'status', 'diterbitkan_pada'])]
class KontenEdukasi extends Model
{
    public const KATEGORI = ['pencegahan', 'kesiapsiagaan', 'saat_banjir', 'setelah_banjir', 'fun_fact', 'mitos_fakta'];

    public const LABEL_KATEGORI = [
        'pencegahan' => 'Pencegahan',
        'kesiapsiagaan' => 'Kesiapsiagaan',
        'saat_banjir' => 'Saat Banjir',
        'setelah_banjir' => 'Setelah Banjir',
        'fun_fact' => 'Fun Fact',
        'mitos_fakta' => 'Mitos & Fakta',
    ];

    public const TIPE = ['artikel', 'infografis', 'video_sematan'];

    public const LABEL_TIPE = [
        'artikel' => 'Artikel',
        'infografis' => 'Infografis',
        'video_sematan' => 'Video Sematan',
    ];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return [
            'diterbitkan_pada' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    #[Scope]
    protected function terbit(Builder $query): void
    {
        $query->where('status', 'terbit');
    }

    public function labelKategori(): string
    {
        return self::LABEL_KATEGORI[$this->kategori] ?? $this->kategori;
    }

    public function labelTipe(): string
    {
        return self::LABEL_TIPE[$this->tipe] ?? $this->tipe;
    }

    public function isTerbit(): bool
    {
        return $this->status === 'terbit';
    }
}
