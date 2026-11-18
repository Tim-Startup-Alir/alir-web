<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log kabar status ke pelapor yang dikirim manual lewat tautan wa.me (FR-30).
 */
#[Table('kabar_whatsapp')]
#[Fillable([
    'laporan_banjir_id', 'template_whatsapp_id', 'dikirim_oleh', 'status_laporan', 'nomor_tujuan', 'pesan',
    'dikirim_pada',
])]
class KabarWhatsapp extends Model
{
    protected function casts(): array
    {
        return [
            'dikirim_pada' => 'datetime',
        ];
    }

    public function laporanBanjir(): BelongsTo
    {
        return $this->belongsTo(LaporanBanjir::class);
    }

    public function templateWhatsapp(): BelongsTo
    {
        return $this->belongsTo(TemplateWhatsapp::class);
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikirim_oleh');
    }
}
