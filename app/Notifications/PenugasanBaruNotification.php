<?php

namespace App\Notifications;

use App\Models\Penugasan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PenugasanBaruNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Penugasan $penugasan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $laporan = $this->penugasan->laporanBanjir;

        return [
            'penugasan_id' => $this->penugasan->id,
            'kode_tugas' => 'TK-'.$this->penugasan->id,
            'laporan_id' => $laporan?->id,
            'kode_laporan' => $laporan?->kode_pelacakan,
            'kelurahan' => $laporan?->wilayah?->nama_kelurahan,
            'jumlah_personel' => $this->penugasan->jumlah_personil_tugas,
            'jumlah_perahu' => $this->penugasan->armada_perahu_tugas,
            'pesan' => "Tugas baru TK-{$this->penugasan->id} untuk laporan {$laporan?->kode_pelacakan}.",
        ];
    }
}
