<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * NFR-05: siapa (user_id, null = sistem/webhook), kapan (waktu), apa (aksi + subjek + deskripsi).
 */
#[Table('audit_log', timestamps: false)]
#[Fillable(['user_id', 'aksi', 'subjek_type', 'subjek_id', 'deskripsi', 'ip_address', 'waktu'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjek(): MorphTo
    {
        return $this->morphTo();
    }

    public static function catat(string $aksi, ?Model $subjek = null, ?string $deskripsi = null, ?User $user = null, bool $sistem = false): self
    {
        return self::create([
            'user_id' => $sistem ? null : ($user?->id ?? auth()->id()),
            'aksi' => $aksi,
            'subjek_type' => $subjek?->getMorphClass(),
            'subjek_id' => $subjek?->getKey(),
            'deskripsi' => $deskripsi,
            'ip_address' => request()?->ip(),
            'waktu' => now(),
        ]);
    }
}
