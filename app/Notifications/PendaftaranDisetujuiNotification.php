<?php

namespace App\Notifications;

use App\Models\KomunitasRelawan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendaftaranDisetujuiNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly KomunitasRelawan $komunitas) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pendaftaran Komunitas Disetujui - ALIR')
            ->greeting("Selamat, {$this->komunitas->nama}!")
            ->line('Pendaftaran komunitas relawan Anda telah disetujui oleh Tim Startup ALIR.')
            ->line('Akun Anda kini sudah aktif dan dapat digunakan untuk masuk ke Portal Komunitas Relawan.')
            ->action('Masuk ke Portal ALIR', route('login'))
            ->line('Terima kasih telah bergabung dalam kesiapsiagaan dan penanganan banjir Kota Pekalongan.');
    }
}
