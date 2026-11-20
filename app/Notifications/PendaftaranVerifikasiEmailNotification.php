<?php

namespace App\Notifications;

use App\Models\KomunitasRelawan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PendaftaranVerifikasiEmailNotification extends Notification
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
        $url = URL::temporarySignedRoute(
            'register.verify-email',
            now()->addHours(24),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Verifikasi Email Pendaftaran Komunitas - ALIR')
            ->greeting("Halo, {$this->komunitas->nama_pic}!")
            ->line("Terima kasih telah mendaftarkan {$this->komunitas->nama} di platform ALIR.")
            ->line('Klik tombol di bawah ini untuk memverifikasi alamat email Anda agar pendaftaran masuk ke antrean peninjauan Tim Startup ALIR.')
            ->action('Verifikasi Email Sekarang', $url)
            ->line('Tautan verifikasi ini berlaku selama 24 jam.');
    }
}
