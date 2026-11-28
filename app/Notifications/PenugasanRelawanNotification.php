<?php

namespace App\Notifications;

use App\Models\Penugasan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PenugasanRelawanNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Penugasan $penugasan,
        private readonly ?string $pesan = null
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'penugasan_id' => $this->penugasan->id,
            'laporan_id' => $this->penugasan->laporan_banjir_id,
            'kode_laporan' => $this->penugasan->laporanBanjir?->kode_pelacakan,
            'pesan' => $this->pesan ?? "Anda menerima penugasan untuk laporan {$this->penugasan->laporanBanjir?->kode_pelacakan}.",
            'url' => route('komunitas.penugasan.index'),
        ];
    }
}
