<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Wilayah;
use DomainException;
use Illuminate\Database\QueryException;

class WilayahService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Wilayah
    {
        $wilayah = Wilayah::create([
            'kode_adm4' => $data['kode_adm4'],
            'nama_kelurahan' => $data['nama_kelurahan'],
            'kecamatan' => $data['kecamatan'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius_cakupan_m' => $data['radius_cakupan_m'] ?? null,
            'is_aktif' => $data['is_aktif'] ?? true,
        ]);

        AuditLog::catat('wilayah.buat', $wilayah, "Menambahkan wilayah {$wilayah->nama_kelurahan} ({$wilayah->kode_adm4})");

        return $wilayah;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Wilayah $wilayah, array $data): Wilayah
    {
        $wilayah->update([
            'kode_adm4' => $data['kode_adm4'],
            'nama_kelurahan' => $data['nama_kelurahan'],
            'kecamatan' => $data['kecamatan'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius_cakupan_m' => $data['radius_cakupan_m'] ?? null,
        ]);

        AuditLog::catat('wilayah.ubah', $wilayah, "Memperbarui wilayah {$wilayah->nama_kelurahan} ({$wilayah->kode_adm4})");

        return $wilayah;
    }

    public function toggleStatus(Wilayah $wilayah): Wilayah
    {
        $wilayah->update(['is_aktif' => ! $wilayah->is_aktif]);

        $aksi = $wilayah->is_aktif ? 'wilayah.aktifkan' : 'wilayah.nonaktifkan';
        $ket = $wilayah->is_aktif ? 'Mengaktifkan' : 'Menonaktifkan';
        AuditLog::catat($aksi, $wilayah, "{$ket} wilayah {$wilayah->nama_kelurahan}");

        return $wilayah;
    }

    /**
     * @throws DomainException bila wilayah sedang dipakai laporan atau masih terikat relasi restrictOnDelete
     */
    public function delete(Wilayah $wilayah): void
    {
        if ($wilayah->isInUse()) {
            throw new DomainException("Wilayah {$wilayah->nama_kelurahan} sedang dipakai oleh laporan banjir dan tidak dapat dihapus. Silakan nonaktifkan.");
        }

        $nama = $wilayah->nama_kelurahan;
        $kode = $wilayah->kode_adm4;

        try {
            $wilayah->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[0] ?? null) === '23000' || $e->getCode() === '23000') {
                throw new DomainException("Wilayah {$nama} masih terhubung dengan data lain (mis. komunitas relawan) dan tidak dapat dihapus. Silakan nonaktifkan.");
            }

            throw $e;
        }

        AuditLog::catat('wilayah.hapus', null, "Menghapus wilayah {$nama} ({$kode})");
    }
}
