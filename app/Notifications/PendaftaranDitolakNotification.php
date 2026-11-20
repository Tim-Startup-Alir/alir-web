<?php

namespace App\Notifications;

use App\Models\KomunitasRelawan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PendaftaranDitolakNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly KomunitasRelawan $komunitas,
        public readonly string $alasan,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urlPerbaikan = URL::temporarySignedRoute(
            'register.perbaikan.edit',
            now()->addDays(7),
            ['komunitas' => $this->komunitas->id]
        );

        return (new MailMessage)
            ->subject('Perbaikan Pendaftaran Komunitas Diperlukan - ALIR')
            ->greeting("Halo, {$this->komunitas->nama_pic}")
            ->line("Pendaftaran komunitas {$this->komunitas->nama} belum dapat disetujui oleh Tim Startup ALIR.")
            ->line("Alasan / Catatan Perbaikan: {$this->alasan}")
            ->line('Silakan perbaiki data pendaftaran Anda melalui tautan di bawah ini untuk diajukan ulang.')
            ->action('Perbaiki Data Pendaftaran', $urlPerbaikan)
            ->line('Tautan perbaikan ini berlaku selama 7 hari.');
    }
}
