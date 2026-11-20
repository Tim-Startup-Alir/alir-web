<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\Posko;
use App\Models\User;
use App\Models\Wilayah;
use App\Notifications\PendaftaranBaruAdminNotification;
use App\Notifications\PendaftaranDisetujuiNotification;
use App\Notifications\PendaftaranDitolakNotification;
use App\Notifications\PendaftaranVerifikasiEmailNotification;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class PendaftaranKomunitasService
{
    /**
     * Mendaftarkan calon komunitas baru (status menunggu, akun user is_active=false sampai disetujui admin).
     *
     * @param  array<string, mixed>  $data
     */
    public function daftar(array $data, UploadedFile $dokumen): KomunitasRelawan
    {
        $dokumenPath = $dokumen->store('dokumen-komunitas', 'local');

        try {
            $komunitas = DB::transaction(function () use ($data, $dokumenPath): KomunitasRelawan {
                $wilayah = Wilayah::findOrFail($data['wilayah_id']);

                $user = User::create([
                    'name' => $data['nama'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => User::ROLE_KOMUNITAS,
                    'is_active' => false,
                    'wajib_ganti_sandi' => false,
                ]);

                $komunitas = KomunitasRelawan::create([
                    'user_id' => $user->id,
                    'wilayah_id' => $wilayah->id,
                    'nama' => $data['nama'],
                    'nama_pic' => $data['nama_pic'],
                    'nomor_whatsapp_pic' => $data['nomor_whatsapp_pic'],
                    'total_anggota' => $data['total_anggota'],
                    'kapasitas_max' => $data['kapasitas_max'] ?? $data['total_anggota'],
                    'jumlah_perahu' => $data['jumlah_perahu'],
                    'status_pendaftaran' => 'menunggu',
                    'dokumen_pendukung_path' => $dokumenPath,
                    'menyetujui_syarat_pada' => now(),
                ]);

                $poskoData = [
                    'komunitas_relawan_id' => $komunitas->id,
                    'nama' => filled($data['posko_nama'] ?? null) ? $data['posko_nama'] : "Posko Utama {$wilayah->nama_kelurahan}",
                    'alamat' => $data['posko_alamat'] ?? null,
                    'latitude' => $data['posko_latitude'],
                    'longitude' => $data['posko_longitude'],
                    'radius_m' => $data['posko_radius_m'] ?? 1500,
                ];

                $poskoPreview = new Posko($poskoData);
                if ($poskoPreview->diLuarCakupan()) {
                    if (empty($data['pernyataan_luar_cakupan'])) {
                        throw new DomainException('Lokasi posko berada di luar 3 kelurahan pilot. Centang pernyataan kesiapan risiko.');
                    }
                    $poskoData['pernyataan_luar_cakupan_pada'] = now();
                }

                Posko::create($poskoData);

                AuditLog::catat('komunitas.daftar', $komunitas, "Pendaftaran komunitas baru {$komunitas->nama} menunggu verifikasi email", $user);

                return $komunitas;
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($dokumenPath);

            throw $e;
        }

        $komunitas->user->notify(new PendaftaranVerifikasiEmailNotification($komunitas));

        return $komunitas;
    }

    /**
     * Memverifikasi email pendaftar lalu memberi notifikasi ke admin bahwa pendaftaran siap ditinjau.
     */
    public function verifikasiEmail(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $komunitas = $user->komunitasRelawan;
        if ($komunitas) {
            AuditLog::catat('komunitas.verifikasi_email', $komunitas, "Email pendaftaran {$komunitas->nama} terverifikasi", $user);
            $this->kirimNotifikasiAdmin($komunitas, false);
        }
    }

    /**
     * Memperbaiki data pendaftaran yang ditolak melalui link signed URL lalu mengajukan ulang.
     *
     * @param  array<string, mixed>  $data
     */
    public function ajukanUlang(KomunitasRelawan $komunitas, array $data, ?UploadedFile $dokumenBaru = null): KomunitasRelawan
    {
        if ($komunitas->status_pendaftaran !== 'ditolak') {
            throw new DomainException('Hanya pendaftaran berstatus ditolak yang dapat diperbaiki dan diajukan ulang.');
        }

        $dokumenLama = $komunitas->dokumen_pendukung_path;
        $dokumenPathBaru = $dokumenBaru?->store('dokumen-komunitas', 'local');

        try {
            DB::transaction(function () use ($komunitas, $data, $dokumenPathBaru): void {
                $wilayah = Wilayah::findOrFail($data['wilayah_id']);

                $komunitas->user->update(['name' => $data['nama']]);

                $poskoUtama = $komunitas->poskoUtama();
                $poskoData = [
                    'nama' => filled($data['posko_nama'] ?? null) ? $data['posko_nama'] : "Posko Utama {$wilayah->nama_kelurahan}",
                    'alamat' => $data['posko_alamat'] ?? null,
                    'latitude' => $data['posko_latitude'],
                    'longitude' => $data['posko_longitude'],
                    'radius_m' => $data['posko_radius_m'] ?? 1500,
                ];

                $poskoPreview = new Posko($poskoData);
                if ($poskoPreview->diLuarCakupan()) {
                    if (empty($data['pernyataan_luar_cakupan'])) {
                        throw new DomainException('Lokasi posko berada di luar 3 kelurahan pilot. Centang pernyataan kesiapan risiko.');
                    }
                    $poskoData['pernyataan_luar_cakupan_pada'] = $poskoUtama?->pernyataan_luar_cakupan_pada ?? now();
                } else {
                    $poskoData['pernyataan_luar_cakupan_pada'] = null;
                }

                if ($poskoUtama) {
                    $poskoUtama->update($poskoData);
                } else {
                    $komunitas->posko()->create($poskoData);
                }

                $updateKomunitas = [
                    'wilayah_id' => $wilayah->id,
                    'nama' => $data['nama'],
                    'nama_pic' => $data['nama_pic'],
                    'nomor_whatsapp_pic' => $data['nomor_whatsapp_pic'],
                    'total_anggota' => $data['total_anggota'],
                    'kapasitas_max' => $data['kapasitas_max'] ?? $data['total_anggota'],
                    'jumlah_perahu' => $data['jumlah_perahu'],
                    'menyetujui_syarat_pada' => now(),
                ];

                if ($dokumenPathBaru !== null) {
                    $updateKomunitas['dokumen_pendukung_path'] = $dokumenPathBaru;
                }

                $komunitas->ajukanUlang($updateKomunitas);
            });
        } catch (\Throwable $e) {
            if ($dokumenPathBaru !== null) {
                Storage::disk('local')->delete($dokumenPathBaru);
            }

            throw $e;
        }

        if ($dokumenPathBaru !== null && $dokumenLama && $dokumenLama !== $dokumenPathBaru) {
            Storage::disk('local')->delete($dokumenLama);
        }

        $komunitas->refresh();
        $this->kirimNotifikasiAdmin($komunitas, true);

        return $komunitas;
    }

    /**
     * Admin menyetujui pendaftaran: aktifkan user, ubah status menjadi disetujui, dan kirim email pemberitahuan.
     */
    public function setujui(KomunitasRelawan $komunitas, User $admin): KomunitasRelawan
    {
        if ($komunitas->status_pendaftaran !== 'menunggu') {
            throw new DomainException("Pendaftaran berstatus '{$komunitas->status_pendaftaran}' tidak dapat disetujui.");
        }

        if (! $komunitas->user->hasVerifiedEmail()) {
            throw new DomainException('Email pendaftar belum terverifikasi.');
        }

        DB::transaction(function () use ($komunitas, $admin): void {
            $komunitas->update([
                'status_pendaftaran' => 'disetujui',
                'alasan_tolak_registrasi' => null,
                'diverifikasi_pada' => now(),
            ]);

            $komunitas->user->forceFill(['is_active' => true])->save();

            AuditLog::catat('komunitas.setujui', $komunitas, "Menyetujui pendaftaran komunitas {$komunitas->nama}", $admin);
        });

        $komunitas->user->notify(new PendaftaranDisetujuiNotification($komunitas));

        return $komunitas->refresh();
    }

    /**
     * Admin menolak pendaftaran dengan alasan wajib dan mengirim link perbaikan via email.
     */
    public function tolak(KomunitasRelawan $komunitas, User $admin, string $alasan): KomunitasRelawan
    {
        if ($komunitas->status_pendaftaran !== 'menunggu') {
            throw new DomainException("Pendaftaran berstatus '{$komunitas->status_pendaftaran}' tidak dapat ditolak.");
        }

        DB::transaction(function () use ($komunitas, $admin, $alasan): void {
            $komunitas->update([
                'status_pendaftaran' => 'ditolak',
                'alasan_tolak_registrasi' => $alasan,
                'diverifikasi_pada' => now(),
            ]);

            $komunitas->user->forceFill(['is_active' => false])->save();

            AuditLog::catat('komunitas.tolak', $komunitas, "Menolak pendaftaran komunitas {$komunitas->nama}: {$alasan}", $admin);
        });

        $komunitas->user->notify(new PendaftaranDitolakNotification($komunitas, $alasan));

        return $komunitas->refresh();
    }

    private function kirimNotifikasiAdmin(KomunitasRelawan $komunitas, bool $pengajuanUlang): void
    {
        $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new PendaftaranBaruAdminNotification($komunitas, $pengajuanUlang));
        }
    }
}
