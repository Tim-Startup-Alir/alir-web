<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * NFR-02 / TR-07: verifikasi token Cloudflare Turnstile di server.
 * Gagal menghubungi Cloudflare dianggap tidak lolos (fail closed).
 */
class TurnstileService
{
    public const URL_VERIFIKASI = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verifikasi(?string $token, ?string $ip = null): bool
    {
        $secret = (string) config('services.turnstile.secret_key');

        if (blank($token) || $secret === '') {
            return false;
        }

        try {
            $respon = Http::asForm()->timeout(5)->post(self::URL_VERIFIKASI, array_filter([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        return $respon->successful() && $respon->json('success') === true;
    }
}
