<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\Posko;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class ProfilKomunitasService
{
    /**
     * Memperbarui profil komunitas dan posko utama (PBI-1.2, F13, A3, A4).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(KomunitasRelawan $komunitas, User $actor, array $data): KomunitasRelawan
    {
        return DB::transaction(function () use ($komunitas, $actor, $data): KomunitasRelawan {
            $profilFields = [
                'nama_pic' => $data['nama_pic'],
                'nomor_whatsapp_pic' => $data['nomor_whatsapp_pic'],
                'total_anggota' => $data['total_anggota'],
                'kapasitas_max' => $data['kapasitas_max'],
                'jumlah_perahu' => $data['jumlah_perahu'],
            ];

            if ($actor->isAdmin()) {
                if (array_key_exists('nama', $data)) {
                    $profilFields['nama'] = $data['nama'];
                    $komunitas->user->update(['name' => $data['nama']]);
                }
                if (array_key_exists('wilayah_id', $data)) {
                    $profilFields['wilayah_id'] = $data['wilayah_id'];
                }
            }

            $komunitas->update($profilFields);

            $poskoUtama = $komunitas->poskoUtama();
            $poskoAttributes = [
                'nama' => $data['posko_nama'],
                'alamat' => $data['posko_alamat'] ?? null,
                'latitude' => $data['posko_latitude'],
                'longitude' => $data['posko_longitude'],
                'radius_m' => $data['posko_radius_m'],
            ];

            $poskoPreview = new Posko($poskoAttributes);
            if ($poskoPreview->diLuarCakupan()) {
                if (empty($data['pernyataan_luar_cakupan'])) {
                    throw new DomainException('Koordinat posko berada di luar 3 kelurahan pilot. Centang pernyataan kesiapan risiko.');
                }
                $poskoAttributes['pernyataan_luar_cakupan_pada'] = $poskoUtama?->pernyataan_luar_cakupan_pada ?? now();
            } else {
                $poskoAttributes['pernyataan_luar_cakupan_pada'] = null;
            }

            if ($poskoUtama) {
                $poskoUtama->update($poskoAttributes);
            } else {
                $komunitas->posko()->create($poskoAttributes);
            }

            AuditLog::catat('profil.ubah', $komunitas, "Memperbarui profil dan posko utama {$komunitas->nama}", $actor);

            return $komunitas->refresh();
        });
    }
}
