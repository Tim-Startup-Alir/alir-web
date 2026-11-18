<?php

namespace App\Console\Commands;

use App\Models\Wilayah;
use App\Support\Geo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * FR-24 (opsi A): impor batas resmi kelurahan dari file GeoJSON ke wilayah.batas_wilayah.
 *
 * Sumber yang disarankan: SIG Kota Pekalongan (gis.pekalongankota.go.id) atau batas desa/kelurahan BIG
 * (tanahair.indonesia.go.id). Bila file berupa shapefile (.shp), konversi dulu ke GeoJSON berproyeksi
 * WGS84 / EPSG:4326, mis. lewat QGIS atau mapshaper.org.
 *
 * Pencocokan fitur -> wilayah:
 *  1. kode Kemendagri (properti KDEPUM / kode_kemendagri / kode) = wilayah.kode_adm4, atau
 *  2. nama kelurahan (NAMOBJ / WADMKD / kelurahan / nama / name), dan kecamatan bila properti tersedia.
 * Bila ada beberapa kandidat, dipilih poligon yang memuat titik pusat kelurahan versi BMKG.
 */
#[Signature('alir:impor-batas-wilayah {file : Path file GeoJSON (FeatureCollection)} {--dry-run : Hanya tampilkan hasil pencocokan, tanpa menyimpan}')]
#[Description('Impor batas resmi kelurahan (GeoJSON) untuk validasi titik laporan FR-24')]
class ImporBatasWilayah extends Command
{
    private const PROP_KODE = ['KDEPUM', 'kdepum', 'kode_kemendagri', 'KODE_KEMENDAGRI', 'kode', 'KODE', 'kode_adm4'];

    private const PROP_NAMA = ['NAMOBJ', 'namobj', 'WADMKD', 'wadmkd', 'kelurahan', 'KELURAHAN', 'desa', 'DESA', 'nama', 'NAMA', 'name'];

    private const PROP_KECAMATAN = ['WADMKC', 'wadmkc', 'kecamatan', 'KECAMATAN'];

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $geojson = json_decode(file_get_contents($path), true);
        $fitur = ($geojson['type'] ?? null) === 'FeatureCollection' ? ($geojson['features'] ?? []) : [];

        if ($fitur === []) {
            $this->error('File harus berupa GeoJSON FeatureCollection yang berisi poligon.');

            return self::FAILURE;
        }

        $fitur = array_values(array_filter($fitur, fn ($f) => in_array($f['geometry']['type'] ?? null, ['Polygon', 'MultiPolygon'], true)));

        if (! $this->koordinatWgs84($fitur[0] ?? null)) {
            $this->error('Koordinat tidak terlihat seperti WGS84 (EPSG:4326). Konversi proyeksi file terlebih dahulu.');

            return self::FAILURE;
        }

        $hasil = [];
        $gagal = 0;

        foreach (Wilayah::orderBy('nama_kelurahan')->get() as $wilayah) {
            $kandidat = $this->cariKandidat($wilayah, $fitur);
            $lat = (float) $wilayah->latitude;
            $lng = (float) $wilayah->longitude;
            $memuatPusat = array_values(array_filter($kandidat, fn ($f) => Geo::dalamGeoJson($lat, $lng, $f['geometry'])));
            $dipilih = $memuatPusat[0] ?? (count($kandidat) === 1 ? $kandidat[0] : null);

            if ($dipilih === null) {
                $gagal++;
                $hasil[] = [$wilayah->nama_kelurahan, count($kandidat), '-', count($kandidat) ? 'GAGAL: kandidat ganda, tidak ada yang memuat titik pusat' : 'GAGAL: tidak ditemukan'];

                continue;
            }

            $catatan = $memuatPusat ? 'OK' : 'PERIKSA: titik pusat BMKG di luar poligon';

            if (! $this->option('dry-run')) {
                $wilayah->update(['batas_wilayah' => $dipilih['geometry']]);
            }

            $hasil[] = [$wilayah->nama_kelurahan, count($kandidat), $dipilih['geometry']['type'], $catatan];
        }

        $this->table(['Kelurahan', 'Kandidat', 'Geometri', 'Hasil'], $hasil);
        $this->info($this->option('dry-run') ? 'Dry run: tidak ada data yang disimpan.' : 'Batas wilayah tersimpan. FR-24 kini memakai poligon untuk kelurahan yang berhasil.');

        return $gagal > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fitur
     * @return array<int, array<string, mixed>>
     */
    private function cariKandidat(Wilayah $wilayah, array $fitur): array
    {
        $perKode = array_filter($fitur, fn ($f) => $this->normalKode($this->prop($f, self::PROP_KODE)) === $wilayah->kode_adm4);

        if ($perKode !== []) {
            return array_values($perKode);
        }

        return array_values(array_filter($fitur, function ($f) use ($wilayah) {
            if ($this->normalNama($this->prop($f, self::PROP_NAMA)) !== $this->normalNama($wilayah->nama_kelurahan)) {
                return false;
            }

            $kecamatan = $this->prop($f, self::PROP_KECAMATAN);

            return $kecamatan === null || $this->normalNama($kecamatan) === $this->normalNama($wilayah->kecamatan);
        }));
    }

    /**
     * @param  array<string, mixed>  $f
     * @param  array<int, string>  $kunci
     */
    private function prop(array $f, array $kunci): ?string
    {
        foreach ($kunci as $k) {
            if (filled($f['properties'][$k] ?? null)) {
                return (string) $f['properties'][$k];
            }
        }

        return null;
    }

    private function normalKode(?string $kode): ?string
    {
        if ($kode === null) {
            return null;
        }

        $angka = preg_replace('/\D/', '', $kode);

        // 3375011009 -> 33.75.01.1009
        return strlen($angka) === 10
            ? substr($angka, 0, 2).'.'.substr($angka, 2, 2).'.'.substr($angka, 4, 2).'.'.substr($angka, 6, 4)
            : trim($kode);
    }

    private function normalNama(?string $nama): string
    {
        // "Pasir Kraton Kramat" / "PASIRKRATONKRAMAT" / "Kel. Tirto" -> "pasirkratonkramat" / "tirto"
        $nama = Str::of((string) $nama)->lower()->replaceMatches('/^(kel\.?|kelurahan|desa|kec\.?|kecamatan)\s+/', '');

        return preg_replace('/[^a-z]/', '', (string) $nama);
    }

    /**
     * @param  array<string, mixed>|null  $f
     */
    private function koordinatWgs84(?array $f): bool
    {
        $c = $f['geometry']['coordinates'] ?? null;

        while (is_array($c) && is_array($c[0] ?? null)) {
            $c = $c[0];
        }

        return is_array($c) && abs($c[0]) <= 180 && abs($c[1]) <= 90;
    }
}
