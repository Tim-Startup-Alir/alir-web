<?php

namespace App\Policies;

use App\Models\JadwalKesiapsiagaan;
use App\Models\User;

class JadwalKesiapsiagaanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isKomunitas();
    }

    public function view(User $user, JadwalKesiapsiagaan $jadwal): bool
    {
        return $user->isAdmin()
            || ($user->isKomunitas() && $jadwal->komunitasRelawan?->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, JadwalKesiapsiagaan $jadwal): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, JadwalKesiapsiagaan $jadwal): bool
    {
        return $user->isAdmin();
    }
}
