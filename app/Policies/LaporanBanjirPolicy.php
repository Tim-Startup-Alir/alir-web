<?php

namespace App\Policies;

use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\User;
use App\Services\KonfirmasiLapanganService;

class LaporanBanjirPolicy
{
    public function konfirmasi(User $user, LaporanBanjir $laporan): bool
    {
        if (! $user->isKomunitas() || ! $user->hasVerifiedEmail()) {
            return false;
        }

        $komunitas = $user->komunitasRelawan()->first();

        return $komunitas instanceof KomunitasRelawan
            && app(KonfirmasiLapanganService::class)->bolehMengonfirmasi($komunitas, $laporan);
    }
}
