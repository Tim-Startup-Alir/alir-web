<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\JadwalKesiapsiagaan;
use DomainException;

class JadwalKesiapsiagaanService
{
    public function __construct(private readonly JadwalBentrokService $bentrokService) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException bila jadwal bentrok dengan shift lain pada komunitas yang sama
     */
    public function create(array $data): JadwalKesiapsiagaan
    {
        $bentrok = $this->bentrokService->cariBentrok(
            (int) $data['komunitas_relawan_id'],
            $data['hari'],
            $data['jam_mulai'],
            $data['jam_selesai'],
        );

        if ($bentrok !== null) {
            throw new DomainException($this->pesanBentrok($bentrok));
        }

        $jadwal = JadwalKesiapsiagaan::create($data);
        $jadwal->loadMissing('komunitasRelawan');

        AuditLog::catat(
            'jadwal.buat',
            $jadwal,
            "Menambah jadwal {$jadwal->hari} {$jadwal->jam_mulai}-{$jadwal->jam_selesai} untuk {$jadwal->komunitasRelawan->nama}"
        );

        return $jadwal;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException bila jadwal bentrok dengan shift lain pada komunitas yang sama
     */
    public function update(JadwalKesiapsiagaan $jadwal, array $data): JadwalKesiapsiagaan
    {
        $bentrok = $this->bentrokService->cariBentrok(
            (int) $data['komunitas_relawan_id'],
            $data['hari'],
            $data['jam_mulai'],
            $data['jam_selesai'],
            $jadwal->id,
        );

        if ($bentrok !== null) {
            throw new DomainException($this->pesanBentrok($bentrok));
        }

        $jadwal->update($data);
        $jadwal->loadMissing('komunitasRelawan');

        AuditLog::catat(
            'jadwal.ubah',
            $jadwal,
            "Mengubah jadwal {$jadwal->hari} {$jadwal->jam_mulai}-{$jadwal->jam_selesai} milik {$jadwal->komunitasRelawan->nama}"
        );

        return $jadwal->refresh();
    }

    public function delete(JadwalKesiapsiagaan $jadwal): void
    {
        $jadwal->loadMissing('komunitasRelawan');
        $deskripsi = "Menghapus jadwal {$jadwal->hari} {$jadwal->jam_mulai}-{$jadwal->jam_selesai} milik {$jadwal->komunitasRelawan->nama}";

        AuditLog::catat('jadwal.hapus', $jadwal, $deskripsi);
        $jadwal->delete();
    }

    private function pesanBentrok(JadwalKesiapsiagaan $bentrok): string
    {
        $mulai = substr($bentrok->jam_mulai, 0, 5);
        $selesai = substr($bentrok->jam_selesai, 0, 5);
        $hari = ucfirst($bentrok->hari);

        return "Jadwal bentrok dengan shift {$bentrok->label} ({$hari}, {$mulai}–{$selesai}) pada komunitas yang sama.";
    }
}
