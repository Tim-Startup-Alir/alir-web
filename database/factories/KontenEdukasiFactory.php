<?php

namespace Database\Factories;

use App\Models\KontenEdukasi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class KontenEdukasiFactory extends Factory
{
    protected $model = KontenEdukasi::class;

    public function definition(): array
    {
        $judul = fake()->unique()->sentence(4);

        return [
            'judul' => $judul,
            'slug' => Str::slug($judul).'-'.fake()->unique()->numberBetween(100, 99999),
            'kategori' => fake()->randomElement(KontenEdukasi::KATEGORI),
            'tipe' => 'artikel',
            'isi' => fake()->paragraphs(2, true),
            'media_url' => null,
            'status' => 'terbit',
            'diterbitkan_pada' => now(),
        ];
    }

    public function draft(): self
    {
        return $this->state(fn () => ['status' => 'draft', 'diterbitkan_pada' => null]);
    }
}
