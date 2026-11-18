<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('template_whatsapp')]
#[Fillable(['judul', 'status_laporan', 'isi_pesan'])]
class TemplateWhatsapp extends Model
{
    public function kabarWhatsapp(): HasMany
    {
        return $this->hasMany(KabarWhatsapp::class);
    }

    /**
     * Isi placeholder {kode}, {lokasi}, {keterangan} dari data laporan.
     */
    public function susunPesan(LaporanBanjir $laporan, ?string $keterangan = null): string
    {
        return strtr($this->isi_pesan, [
            '{kode}' => $laporan->kode_pelacakan,
            '{lokasi}' => $laporan->alamat_singkat,
            '{keterangan}' => $keterangan ?? $laporan->alasan_tolak ?? '-',
        ]);
    }
}
