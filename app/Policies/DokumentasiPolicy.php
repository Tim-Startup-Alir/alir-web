<?php

namespace App\Policies;

use App\Models\Dokumentasi;
use App\Models\User;

class DokumentasiPolicy
{
    /** Komunitas relawan yang disetujui dan aktif boleh mengunggah. */
    public function create(User $user): bool
    {
        $komunitas = $user->isKomunitas() ? $user->komunitasRelawan : null;

        return $user->is_active && $komunitas !== null && $komunitas->status_pendaftaran === 'disetujui';
    }

    public function moderasi(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Perbaikan hanya untuk dokumentasi milik sendiri yang ditolak. */
    public function update(User $user, Dokumentasi $dokumentasi): bool
    {
        return $user->isKomunitas() && $this->view($user, $dokumentasi) && $dokumentasi->status_moderasi === 'ditolak';
    }

    /** Dokumentasi yang sudah disetujui tampil publik, jadi tidak dapat dihapus oleh komunitas. */
    public function delete(User $user, Dokumentasi $dokumentasi): bool
    {
        return $user->isKomunitas()
            && $this->view($user, $dokumentasi)
            && in_array($dokumentasi->status_moderasi, ['menunggu', 'ditolak'], true);
    }

    /** Pratinjau berkas di panel: admin atau komunitas pemilik. */
    public function view(User $user, Dokumentasi $dokumentasi): bool
    {
        return $user->isAdmin()
            || ($user->isKomunitas() && $user->komunitasRelawan?->getKey() === $dokumentasi->komunitas_relawan_id);
    }
}
