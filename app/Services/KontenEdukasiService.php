<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KontenEdukasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class KontenEdukasiService
{
    /**
     * @param  array<string, mixed>  $data  judul, kategori, tipe, isi, media_url, status
     */
    public function simpan(array $data): KontenEdukasi
    {
        $konten = new KontenEdukasi($this->siapkan($data));
        $konten->slug = $this->buatSlug($data['judul']);
        $konten->save();

        AuditLog::catat('edukasi.buat', $konten, "Menambah konten edukasi \"{$konten->judul}\" ({$konten->status})");

        return $konten;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ubah(KontenEdukasi $konten, array $data): KontenEdukasi
    {
        $konten->fill($this->siapkan($data, $konten));
        if ($konten->isDirty('judul')) {
            $konten->slug = $this->buatSlug($konten->judul, $konten->getKey());
        }
        $konten->save();

        AuditLog::catat('edukasi.ubah', $konten, "Mengubah konten edukasi \"{$konten->judul}\" ({$konten->status})");

        return $konten;
    }

    public function hapus(KontenEdukasi $konten): void
    {
        AuditLog::catat('edukasi.hapus', $konten, "Menghapus konten edukasi \"{$konten->judul}\"");
        $konten->delete();
    }

    public function ubahStatus(KontenEdukasi $konten, string $status): KontenEdukasi
    {
        return $this->ubah($konten, ['status' => $status] + $konten->only(['judul', 'kategori', 'tipe', 'isi', 'media_url']));
    }

    /** Pencarian publik: konten terbit, filter kategori, kata kunci pada judul dan isi. */
    public function cari(?string $kategori, ?string $kataKunci): Builder
    {
        $kataKunci = trim((string) $kataKunci);

        return KontenEdukasi::query()
            ->terbit()
            ->when(in_array($kategori, KontenEdukasi::KATEGORI, true), fn (Builder $q) => $q->where('kategori', $kategori))
            ->when($kataKunci !== '', function (Builder $q) use ($kataKunci) {
                $like = '%'.addcslashes($kataKunci, '\\%_').'%';
                $q->where(fn (Builder $w) => $w->where('judul', 'like', $like)->orWhere('isi', 'like', $like));
            })
            ->orderByDesc('diterbitkan_pada')
            ->orderByDesc('id');
    }

    /**
     * Ubah URL YouTube/Vimeo menjadi URL embed. Null bila bukan penyedia yang diizinkan.
     */
    public static function embedUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (preg_match('~^https://(?:www\.|m\.)?youtube\.com/watch\?(?:[^#]*&)?v=([A-Za-z0-9_-]{11})(?:[&#]|$)~', $url, $m)
            || preg_match('~^https://youtu\.be/([A-Za-z0-9_-]{11})(?:[?#]|$)~', $url, $m)
            || preg_match('~^https://(?:www\.)?youtube\.com/embed/([A-Za-z0-9_-]{11})(?:[?#]|$)~', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        if (preg_match('~^https://(?:www\.|player\.)?vimeo\.com/(?:video/)?(\d+)(?:[/?#]|$)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function siapkan(array $data, ?KontenEdukasi $lama = null): array
    {
        $tipe = $data['tipe'] ?? $lama?->tipe;
        $mediaUrl = $data['media_url'] ?? null;
        if ($tipe === 'video_sematan') {
            $mediaUrl = self::embedUrl($mediaUrl);
        }

        $hasil = [
            'judul' => $data['judul'] ?? $lama?->judul,
            'kategori' => $data['kategori'] ?? $lama?->kategori,
            'tipe' => $tipe,
            'isi' => $data['isi'] ?? null,
            'media_url' => $mediaUrl,
            'status' => $data['status'] ?? $lama?->status ?? 'draft',
        ];

        // tanggal terbit dicatat saat pertama kali terbit
        if ($hasil['status'] === 'terbit' && $lama?->diterbitkan_pada === null) {
            $hasil['diterbitkan_pada'] = now();
        }

        return $hasil;
    }

    private function buatSlug(string $judul, ?int $abaikanId = null): string
    {
        $dasar = Str::slug($judul) ?: 'konten';
        $slug = $dasar;
        $i = 2;

        while (KontenEdukasi::query()->where('slug', $slug)->when($abaikanId, fn ($q) => $q->where('id', '!=', $abaikanId))->exists()) {
            $slug = $dasar.'-'.$i++;
        }

        return $slug;
    }
}
