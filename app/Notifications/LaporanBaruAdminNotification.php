<?php

namespace App\Notifications;

use App\Models\LaporanBanjir;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * PBI-006: memberi tahu Tim Startup ALIR bahwa ada laporan banjir baru menunggu verifikasi.
 */
class LaporanBaruAdminNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly LaporanBanjir $laporan) {}

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
        $kelurahan = $this->laporan->wilayah?->nama_kelurahan ?? '-';

        return [
            'laporan_banjir_id' => $this->laporan->id,
            'kode_pelacakan' => $this->laporan->kode_pelacakan,
            'kelurahan' => $kelurahan,
            'severity' => $this->laporan->severity,
            'pesan' => "Laporan banjir baru {$this->laporan->kode_pelacakan} ({$this->laporan->severity}) di {$kelurahan} menunggu verifikasi.",
            'url' => route('admin.laporan.cari', ['kode' => $this->laporan->kode_pelacakan]),
        ];
    }
}
