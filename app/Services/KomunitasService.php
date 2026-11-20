<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\Posko;
use App\Models\User;
use App\Models\Wilayah;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class KomunitasService
{
    /**
     * Membuat akun User, profil KomunitasRelawan, dan satu Posko Utama dalam satu transaksi (F12, A1).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): KomunitasRelawan
    {
        return DB::transaction(function () use ($data): KomunitasRelawan {
            $wilayah = Wilayah::findOrFail($data['wilayah_id']);

            $user = User::create([
                'name' => $data['nama'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => User::ROLE_KOMUNITAS,
                'is_active' => true,
                'wajib_ganti_sandi' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $komunitas = KomunitasRelawan::create([
                'user_id' => $user->id,
                'wilayah_id' => $wilayah->id,
                'nama' => $data['nama'],
                'nama_pic' => $data['nama_pic'],
                'nomor_whatsapp_pic' => $data['nomor_whatsapp_pic'],
                'total_anggota' => $data['total_anggota'],
                'kapasitas_max' => $data['kapasitas_max'],
                'jumlah_perahu' => $data['jumlah_perahu'],
                'status_pendaftaran' => 'disetujui',
                'menyetujui_syarat_pada' => now(),
                'diverifikasi_pada' => now(),
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

            AuditLog::catat('komunitas.buat', $komunitas, "Menambahkan komunitas {$komunitas->nama} (wilayah {$wilayah->nama_kelurahan})");

            return $komunitas;
        });
    }

    /**
     * Memperbarui data master komunitas oleh Admin (PBI-1.1).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMaster(KomunitasRelawan $komunitas, array $data): KomunitasRelawan
    {
        return DB::transaction(function () use ($komunitas, $data): KomunitasRelawan {
            $komunitas->update([
                'wilayah_id' => $data['wilayah_id'],
                'nama' => $data['nama'],
                'nama_pic' => $data['nama_pic'],
                'nomor_whatsapp_pic' => $data['nomor_whatsapp_pic'],
                'total_anggota' => $data['total_anggota'],
                'kapasitas_max' => $data['kapasitas_max'],
                'jumlah_perahu' => $data['jumlah_perahu'],
            ]);

            $userUpdates = ['name' => $data['nama']];
            if (! empty($data['email']) && $data['email'] !== $komunitas->user->email) {
                $userUpdates['email'] = $data['email'];
            }
            $komunitas->user->update($userUpdates);

            AuditLog::catat('komunitas.ubah', $komunitas, "Memperbarui data master komunitas {$komunitas->nama}");

            return $komunitas->refresh();
        });
    }

    public function toggleStatus(KomunitasRelawan $komunitas): KomunitasRelawan
    {
        $user = $komunitas->user;
        $user->update(['is_active' => ! $user->is_active]);

        $aksi = $user->is_active ? 'komunitas.aktifkan' : 'komunitas.nonaktifkan';
        $ket = $user->is_active ? 'Mengaktifkan' : 'Menonaktifkan';
        AuditLog::catat($aksi, $komunitas, "{$ket} komunitas {$komunitas->nama}");

        return $komunitas->refresh();
    }

    /**
     * Menghapus komunitas beserta akun user-nya bila belum dipakai (A4, F4).
     *
     * @throws DomainException bila komunitas sedang dipakai atau terikat FK restrictOnDelete
     */
    public function delete(KomunitasRelawan $komunitas): void
    {
        if ($komunitas->isInUse()) {
            throw new DomainException("Komunitas {$komunitas->nama} sedang dipakai oleh penugasan, inventaris, atau konfirmasi lapangan dan tidak dapat dihapus. Silakan nonaktifkan.");
        }

        $nama = $komunitas->nama;
        $user = $komunitas->user;

        try {
            DB::transaction(function () use ($komunitas, $user, $nama): void {
                // Catat audit log terlebih dahulu sebelum record dihapus
                AuditLog::catat('komunitas.hapus', null, "Menghapus komunitas {$nama} beserta akun {$user->email}");
                $komunitas->delete();
                $user->delete();
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23000' || $e->getCode() === '23000') {
                throw new DomainException("Komunitas {$nama} masih terhubung dengan data lain (logistik, wishlist, atau dokumentasi) dan tidak dapat dihapus. Silakan nonaktifkan.");
            }

            throw $e;
        }
    }
}
