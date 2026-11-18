<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'wajib_ganti_sandi', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
/**
 * MustVerifyEmail (FR-02): pendaftar komunitas memverifikasi email lebih dulu agar email penolakan
 * dan link perbaikan pasti sampai. Pendaftaran baru masuk antrean admin setelah email terverifikasi.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_KOMUNITAS = 'komunitas_relawan';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'wajib_ganti_sandi' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKomunitas(): bool
    {
        return $this->role === self::ROLE_KOMUNITAS;
    }

    public function komunitasRelawan(): HasOne
    {
        return $this->hasOne(KomunitasRelawan::class);
    }

    public function laporanDiverifikasi(): HasMany
    {
        return $this->hasMany(LaporanBanjir::class, 'diverifikasi_oleh');
    }

    public function penugasanDibuat(): HasMany
    {
        return $this->hasMany(Penugasan::class, 'ditugaskan_oleh');
    }

    public function permintaanDisetujui(): HasMany
    {
        return $this->hasMany(PermintaanLogistik::class, 'disetujui_oleh');
    }

    public function auditLog(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
