<?php

namespace App\Services;

use App\Models\JadwalKesiapsiagaan;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\Posko;
use App\Models\Wilayah;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RekomendasiPenugasanService
{
    /**
     * @return array{
     *     wilayah_laporan: ?Wilayah,
     *     di_luar_cakupan: bool,
     *     kelompok: array<int, array{key: string, nama: string, prioritas: bool, kandidat: Collection}>
     * }
     */
    public function untukLaporan(LaporanBanjir $laporan): array
    {
        $laporan->loadMissing('wilayah', 'penugasan');
        $waktu = now();
        $wilayahAktif = Wilayah::aktif()
            ->whereIn('nama_kelurahan', ['Tirto', 'Pasirkratonkramat', 'Pasirkraton Kramat', 'Degayu'])
            ->orderBy('nama_kelurahan')
            ->get();
        $wilayahLaporan = $laporan->wilayah;
        $kelompok = $wilayahAktif->mapWithKeys(fn (Wilayah $wilayah) => [
            'wilayah:'.$wilayah->id => [
                'key' => 'wilayah:'.$wilayah->id,
                'nama' => $this->namaWilayah($wilayah->nama_kelurahan),
                'prioritas' => $wilayahLaporan?->is($wilayah) ?? false,
                'kandidat' => collect(),
            ],
        ])->all();

        $diLuarCakupan = $wilayahLaporan === null
            || ! $wilayahLaporan->is_aktif
            || ! $wilayahAktif->contains(fn (Wilayah $wilayah) => $wilayah->is($wilayahLaporan));
        $ditugaskanAktif = $laporan->penugasan
            ->whereIn('stage', Penugasan::STAGE_AKTIF)
            ->pluck('komunitas_relawan_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $komunitasList = KomunitasRelawan::aktif()
            ->whereIn('wilayah_id', $wilayahAktif->modelKeys())
            ->with([
                'wilayah',
                'posko' => fn ($query) => $query->orderBy('id'),
                'jadwalKesiapsiagaan',
                'penugasan',
            ])
            ->get();

        foreach ($komunitasList as $komunitas) {
            $posko = $komunitas->posko->first();
            $kandidat = $this->nilaiKandidat(
                $laporan,
                $komunitas,
                $posko,
                $wilayahAktif,
                $waktu,
                in_array((int) $komunitas->id, $ditugaskanAktif, true)
            );

            if ($kandidat['posko_valid'] && $kandidat['wilayah_posko'] === null) {
                continue;
            }

            $key = $kandidat['wilayah_posko']
                ? 'wilayah:'.$kandidat['wilayah_posko']->id
                : 'wilayah:'.$komunitas->wilayah_id;

            if (! isset($kelompok[$key])) {
                continue;
            }

            $kelompok[$key]['kandidat']->push($kandidat);
        }

        foreach ($kelompok as &$group) {
            $group['kandidat'] = $group['kandidat']
                ->sort(function (array $a, array $b): int {
                    return ($b['bisa_ditugaskan'] <=> $a['bisa_ditugaskan'])
                        ?: ($b['skor_rekomendasi'] <=> $a['skor_rekomendasi'])
                        ?: ($a['jarak_km'] <=> $b['jarak_km'])
                        ?: strcasecmp($a['komunitas']->nama, $b['komunitas']->nama);
                })
                ->values();
        }
        unset($group);

        if ($wilayahLaporan && isset($kelompok['wilayah:'.$wilayahLaporan->id])) {
            $prioritas = $kelompok['wilayah:'.$wilayahLaporan->id];
            unset($kelompok['wilayah:'.$wilayahLaporan->id]);
            $kelompok = ['wilayah:'.$wilayahLaporan->id => $prioritas] + $kelompok;
        }

        return [
            'wilayah_laporan' => $wilayahLaporan,
            'di_luar_cakupan' => $diLuarCakupan,
            'kelompok' => array_values($kelompok),
        ];
    }

    /**
     * @param  EloquentCollection<int, Wilayah>  $wilayahAktif
     * @return array<string, mixed>
     */
    private function nilaiKandidat(
        LaporanBanjir $laporan,
        KomunitasRelawan $komunitas,
        ?Posko $posko,
        EloquentCollection $wilayahAktif,
        Carbon $waktu,
        bool $sudahDitugaskan
    ): array {
        $poskoValid = $posko !== null
            && is_numeric($posko->latitude)
            && is_numeric($posko->longitude)
            && (float) $posko->latitude >= -90
            && (float) $posko->latitude <= 90
            && (float) $posko->longitude >= -180
            && (float) $posko->longitude <= 180;
        $laporanKoordinatValid = is_numeric($laporan->latitude)
            && is_numeric($laporan->longitude)
            && (float) $laporan->latitude >= -90
            && (float) $laporan->latitude <= 90
            && (float) $laporan->longitude >= -180
            && (float) $laporan->longitude <= 180;
        $koordinatValid = $poskoValid && $laporanKoordinatValid;
        $jarakKm = $koordinatValid
            ? Geo::jarakMeter(
                (float) $posko->latitude,
                (float) $posko->longitude,
                (float) $laporan->latitude,
                (float) $laporan->longitude
            ) / 1000
            : null;

        $wilayahPosko = $poskoValid
            ? $this->wilayahUntukTitik($wilayahAktif, (float) $posko->latitude, (float) $posko->longitude)
            : null;
        $sisaPersonil = $komunitas->sisaKapasitas();
        $personilTerpakai = max(0, $komunitas->kapasitas_max - $sisaPersonil);
        $sisaPerahu = max(
            0,
            $komunitas->jumlah_perahu - (int) $komunitas->penugasan
                ->whereIn('stage', Penugasan::STAGE_AKTIF)
                ->sum('armada_perahu_tugas')
        );
        $jadwal = $this->statusJadwal($komunitas->jadwalKesiapsiagaan, $waktu);
        $wilayahSesuai = $komunitas->wilayah_id === $laporan->wilayah_id
            || ($komunitas->wilayah?->cekKoordinat((float) $laporan->latitude, (float) $laporan->longitude) ?? false);
        $lokasiSah = $posko !== null && $poskoValid && $posko->lokasiSah();
        $jadwalAktif = in_array($jadwal['status'], ['siaga', 'siaga_terbatas'], true);
        $bisaDitugaskan = $koordinatValid
            && $lokasiSah
            && $sisaPersonil > 0
            && $jadwalAktif
            && ! $sudahDitugaskan
            && in_array($laporan->status, ['terverifikasi', 'ditugaskan'], true);

        $weights = config('alir.penugasan.bobot');
        $radiusKm = max(0.001, (float) config('alir.penugasan.radius_rekomendasi_km', 5));
        $skorJarak = $jarakKm === null
            ? 0
            : $weights['jarak'] * max(0, 1 - ($jarakKm / $radiusKm));
        $skorKapasitas = $komunitas->kapasitas_max > 0
            ? $weights['kapasitas'] * ($sisaPersonil / $komunitas->kapasitas_max)
            : 0;
        $skorWilayah = $wilayahSesuai ? $weights['wilayah'] : 0;
        $faktorJadwal = match ($jadwal['status']) {
            'siaga' => 1,
            'siaga_terbatas' => .5,
            default => 0,
        };
        $skorJadwal = $weights['jadwal'] * $faktorJadwal;
        $personilAktif = $komunitas->penugasan
            ->whereIn('stage', Penugasan::STAGE_AKTIF)
            ->sum('jumlah_personil_tugas');
        $perahuAktif = $komunitas->penugasan
            ->whereIn('stage', Penugasan::STAGE_AKTIF)
            ->sum('armada_perahu_tugas');

        return [
            'komunitas' => $komunitas,
            'posko' => $posko,
            'wilayah_posko' => $wilayahPosko,
            'jarak_km' => $jarakKm,
            'eta_menit' => $jarakKm === null
                ? null
                : (int) ceil(($jarakKm / max(0.001, (float) config('alir.penugasan.kecepatan_eta_kmh', 15))) * 60),
            'sisa_personil' => $sisaPersonil,
            'kapasitas_max' => $komunitas->kapasitas_max,
            'personil_terpakai' => $personilTerpakai,
            'sisa_perahu' => $sisaPerahu,
            'jumlah_perahu' => $komunitas->jumlah_perahu,
            'perahu_terpakai' => (int) $perahuAktif,
            'jumlah_penugasan_aktif' => $komunitas->penugasan
                ->whereIn('stage', Penugasan::STAGE_AKTIF)
                ->count(),
            'status_jadwal' => $jadwal['status'],
            'label_jadwal' => $jadwal['label'],
            'shift_aktif' => $jadwal['shift'],
            'wilayah_sesuai' => $wilayahSesuai,
            'posko_valid' => $poskoValid,
            'lokasi_valid' => $koordinatValid,
            'lokasi_sah' => $lokasiSah,
            'sudah_ditugaskan' => $sudahDitugaskan,
            'bisa_ditugaskan' => $bisaDitugaskan,
            'alasan_tidak_bisa' => $this->alasanTidakBisa(
                $sudahDitugaskan,
                $posko,
                $koordinatValid,
                $lokasiSah,
                $sisaPersonil,
                $jadwal['status']
            ),
            'skor_komponen' => [
                'jarak' => $skorJarak,
                'kapasitas' => $skorKapasitas,
                'wilayah' => $skorWilayah,
                'jadwal' => $skorJadwal,
            ],
            'skor_rekomendasi' => round($skorJarak + $skorKapasitas + $skorWilayah + $skorJadwal, 2),
        ];
    }

    /**
     * @param  EloquentCollection<int, JadwalKesiapsiagaan>  $jadwal
     * @return array{status: string, label: string, shift: ?JadwalKesiapsiagaan}
     */
    private function statusJadwal(EloquentCollection $jadwal, Carbon $waktu): array
    {
        if ($jadwal->isEmpty() || $jadwal->every(
            fn (JadwalKesiapsiagaan $item) => $item->status_ketersediaan === 'tidak_tersedia'
        )) {
            return ['status' => 'belum_siaga', 'label' => 'Belum siaga', 'shift' => null];
        }

        $sedangBerjalan = $jadwal->filter(fn (JadwalKesiapsiagaan $item) => $item->mencakup($waktu));
        if ($sedangBerjalan->isEmpty()) {
            return ['status' => 'diluar_jam', 'label' => 'Di luar jam', 'shift' => null];
        }

        $aktif = $sedangBerjalan->first(
            fn (JadwalKesiapsiagaan $item) => $item->status_ketersediaan === 'tersedia'
        );
        if ($aktif) {
            return ['status' => 'siaga', 'label' => 'Siap sekarang', 'shift' => $aktif];
        }

        $terbatas = $sedangBerjalan->first(
            fn (JadwalKesiapsiagaan $item) => $item->status_ketersediaan === 'siaga_terbatas'
        );
        if ($terbatas) {
            return ['status' => 'siaga_terbatas', 'label' => 'Siaga terbatas', 'shift' => $terbatas];
        }

        return ['status' => 'diluar_jam', 'label' => 'Di luar jam', 'shift' => null];
    }

    /**
     * @param  EloquentCollection<int, Wilayah>  $wilayah
     */
    private function wilayahUntukTitik(EloquentCollection $wilayah, float $lat, float $lng): ?Wilayah
    {
        return $wilayah
            ->filter(fn (Wilayah $item) => $item->cekKoordinat($lat, $lng))
            ->sortBy(fn (Wilayah $item) => $item->jarakDariPusat($lat, $lng))
            ->first();
    }

    private function namaWilayah(string $nama): string
    {
        return $nama === 'Pasirkratonkramat' ? 'Pasirkraton Kramat' : $nama;
    }

    private function alasanTidakBisa(
        bool $sudahDitugaskan,
        ?Posko $posko,
        bool $lokasiValid,
        bool $lokasiSah,
        int $sisaPersonil,
        string $statusJadwal
    ): ?string {
        return match (true) {
            $sudahDitugaskan => 'Komunitas ini sudah ditugaskan pada laporan ini.',
            ! $posko => 'Komunitas belum memiliki posko.',
            ! $lokasiValid => 'Koordinat posko atau laporan tidak valid.',
            ! $lokasiSah => 'Posko luar cakupan belum memiliki persetujuan risiko.',
            $sisaPersonil < 1 => 'Kapasitas personel sudah penuh.',
            $statusJadwal === 'belum_siaga' => 'Komunitas belum siaga.',
            $statusJadwal === 'diluar_jam' => 'Di luar jadwal kesiapsiagaan.',
            default => null,
        };
    }
}
