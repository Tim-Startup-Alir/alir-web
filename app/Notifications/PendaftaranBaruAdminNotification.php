<?php

namespace App\Notifications;

use App\Models\KomunitasRelawan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PendaftaranBaruAdminNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly KomunitasRelawan $komunitas,
        public readonly bool $pengajuanUlang = false,
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
            'komunitas_id' => $this->komunitas->id,
            'nama_komunitas' => $this->komunitas->nama,
            'pengajuan_ulang' => $this->pengajuanUlang,
            'pesan' => $this->pengajuanUlang
                ? "Komunitas {$this->komunitas->nama} mengajukan ulang perbaikan pendaftaran."
                : "Pendaftaran komunitas baru dari {$this->komunitas->nama} siap ditinjau.",
            'url' => route('admin.komunitas.index'),
        ];
    }
}
