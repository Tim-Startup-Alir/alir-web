<?php

namespace App\Policies;

use App\Models\KomunitasRelawan;
use App\Models\User;

class KomunitasRelawanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, KomunitasRelawan $komunitas): bool
    {
        return $user->isAdmin()
            || ($user->isKomunitas() && $komunitas->user_id === $user->id);
    }

    public function update(User $user, KomunitasRelawan $komunitas): bool
    {
        return $user->isAdmin()
            || ($user->isKomunitas() && $komunitas->user_id === $user->id);
    }
}
