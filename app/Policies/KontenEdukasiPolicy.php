<?php

namespace App\Policies;

use App\Models\KontenEdukasi;
use App\Models\User;

class KontenEdukasiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, KontenEdukasi $konten): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, KontenEdukasi $konten): bool
    {
        return $user->isAdmin();
    }
}
