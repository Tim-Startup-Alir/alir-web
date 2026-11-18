<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shift kesiapsiagaan komunitas. Setiap shift punya PIC yang dapat dihubungi saat kondisi gawat.
 *
 * Shift bersifat dinamis: komunitas bebas menentukan jumlah shift, jam, dan namanya per hari.
 * - `hari` adalah hari shift DIMULAI.
 * - Jika jam_selesai <= jam_mulai, shift melewati tengah malam (mis. Senin 22:00 - 06:00 berlaku
 *   sampai Selasa 06:00). jam_selesai == jam_mulai berarti shift 24 jam.
 */
#[Table('jadwal_kesiapsiagaan')]
#[Fillable([
    'komunitas_relawan_id', 'hari', 'nama_shift', 'jam_mulai', 'jam_selesai', 'status_ketersediaan',
    'nama_pic', 'nomor_whatsapp_pic', 'keterangan',
])]
class JadwalKesiapsiagaan extends Model
{
    /** Urutan sesuai ISO-8601 (1 = Senin ... 7 = Minggu). */
    public const HARI = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    public function komunitasRelawan(): BelongsTo
    {
        return $this->belongsTo(KomunitasRelawan::class);
    }

    public static function hariDari(CarbonInterface $waktu): string
    {
        return self::HARI[$waktu->dayOfWeekIso - 1];
    }

    /**
     * Shift yang sedang berjalan pada waktu tertentu (default: sekarang) dan komunitasnya tidak
     * berstatus tidak_tersedia. Menangani shift yang melewati tengah malam.
     */
    #[Scope]
    protected function siagaPada(Builder $query, ?CarbonInterface $waktu = null): void
    {
        $waktu ??= now();
        $hariIni = self::hariDari($waktu);
        $kemarin = self::hariDari($waktu->copy()->subDay());
        $jam = $waktu->format('H:i:s');

        $query->where('status_ketersediaan', '!=', 'tidak_tersedia')
            ->where(function (Builder $q) use ($hariIni, $kemarin, $jam) {
                // 1) Shift biasa (tidak melewati tengah malam) hari ini.
                $q->where(fn (Builder $q) => $q->where('hari', $hariIni)
                    ->whereColumn('jam_selesai', '>', 'jam_mulai')
                    ->where('jam_mulai', '<=', $jam)
                    ->where('jam_selesai', '>', $jam))
                // 2) Shift lintas tengah malam yang dimulai hari ini (bagian sebelum 24:00).
                    ->orWhere(fn (Builder $q) => $q->where('hari', $hariIni)
                        ->whereColumn('jam_selesai', '<=', 'jam_mulai')
                        ->where('jam_mulai', '<=', $jam))
                // 3) Shift lintas tengah malam yang dimulai kemarin (bagian setelah 00:00).
                    ->orWhere(fn (Builder $q) => $q->where('hari', $kemarin)
                        ->whereColumn('jam_selesai', '<=', 'jam_mulai')
                        ->where('jam_selesai', '>', $jam));
            });
    }

    /**
     * cekKetersediaan(hari, jam) pada class diagram: apakah shift ini mencakup waktu tertentu.
     */
    public function mencakup(CarbonInterface $waktu): bool
    {
        $jam = $waktu->format('H:i:s');
        $mulai = $this->normalJam($this->jam_mulai);
        $selesai = $this->normalJam($this->jam_selesai);
        $hariIni = self::hariDari($waktu);
        $kemarin = self::hariDari($waktu->copy()->subDay());

        if ($selesai > $mulai) {
            return $this->hari === $hariIni && $jam >= $mulai && $jam < $selesai;
        }

        return ($this->hari === $hariIni && $jam >= $mulai)
            || ($this->hari === $kemarin && $jam < $selesai);
    }

    public function isLintasTengahMalam(): bool
    {
        return $this->normalJam($this->jam_selesai) <= $this->normalJam($this->jam_mulai);
    }

    /**
     * Nama shift: pakai nama_shift bila diisi, selain itu diturunkan dari jam_mulai.
     */
    protected function label(): Attribute
    {
        return Attribute::get(function (): string {
            if (filled($this->nama_shift)) {
                return $this->nama_shift;
            }

            $jamMulai = (int) substr($this->normalJam($this->jam_mulai), 0, 2);

            return match (true) {
                $jamMulai >= 4 && $jamMulai < 10 => 'Pagi',
                $jamMulai >= 10 && $jamMulai < 15 => 'Siang',
                $jamMulai >= 15 && $jamMulai < 18 => 'Sore',
                default => 'Malam',
            };
        });
    }

    private function normalJam(?string $jam): string
    {
        return strlen((string) $jam) === 5 ? $jam.':00' : (string) $jam;
    }
}
