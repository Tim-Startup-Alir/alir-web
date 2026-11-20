# PBI-007: Pemetaan Titik Laporan

## Ringkasan

Panel admin menampilkan laporan pada peta interaktif. Memilih titik memuat foto dan detail laporan serta laporan sekitar untuk menilai kemungkinan duplikat. Titik admin memakai koordinat asli.

Radius dan rentang waktu berasal dari `config('alir.kepercayaan.radius_m')` dan `config('alir.kepercayaan.rentang_jam')`. Laporan sekitar memakai jarak maksimal 500 m dan `created_at` dalam ±24 jam dari laporan terpilih, termasuk tepat pada batas. Semua status selain laporan terpilih ditampilkan; status `ditolak` dan `digabung` tetap terlihat dengan gaya redup.

## Route

Semua route berada dalam grup `auth`, `password.changed`, dan `role:admin`.

| Route name | Method dan URL | Fungsi |
| --- | --- | --- |
| `admin.peta-laporan.index` | `GET /admin/peta-laporan` | Halaman peta; menerima `?laporan={id}` untuk memilih dan memusatkan peta ke laporan. |
| `admin.peta-laporan.data` | `GET /admin/peta-laporan/data` | Marker ringan di bounding box, dengan filter status/wilayah; throttle 60 per menit. |
| `admin.peta-laporan.sekitar` | `GET /admin/peta-laporan/{laporan}/sekitar` | Detail laporan terpilih dan daftar laporan sekitar; throttle 60 per menit. |
| `admin.laporan.foto` | `GET /admin/laporan/{laporan}/foto/{foto}` | Streaming foto dari disk privat `local`; foto harus milik laporan pada URL. |

Data marker memerlukan `south`, `west`, `north`, `east`. Keempatnya harus valid, batas selatan lebih kecil dari utara, batas barat lebih kecil dari timur, dan luas tiap sumbu maksimal dua derajat. Filter opsional: `status[]` dari `LaporanBanjir::STATUS` dan `wilayah_id` yang ada. Jumlah marker dibatasi `alir.peta.batas_marker`; `terpotong` memberitahu UI bila hasil berlebih.

## Kontrak JSON

`admin.peta-laporan.data` mengembalikan `{ data: [...], terpotong: boolean }`. Marker memuat `id`, `kode`, `lat`, `lng`, `severity`, `status`, dan `dibuat_pada`.

`admin.peta-laporan.sekitar` mengembalikan:

```json
{
  "laporan": {
    "id": 1,
    "kode": "ALR-ABC123",
    "alamat_singkat": "Jalan Contoh",
    "wilayah": "Tirto",
    "lat": -6.89,
    "lng": 109.65,
    "severity": "sedang",
    "jumlah_warga": 4,
    "status": "menunggu_verifikasi",
    "tingkat_kepercayaan": null,
    "catatan": null,
    "dibuat_pada": "2026-11-20T10:00:00+07:00",
    "foto": [{"id": 1, "url": "/admin/laporan/1/foto/1"}],
    "kepercayaan": null,
    "konfirmasi": []
  },
  "sekitar": [],
  "parameter": {"radius_m": 500, "rentang_jam": 24}
}
```

Items in `sekitar` use the detail shape plus `jarak_m` and `selisih_menit`. Pelapor name, phone, and contact consent never appear in either payload. PBI-008 may fill `kepercayaan`; PBI-009 may fill `konfirmasi`. The frontend already renders these only when populated.

## Reuse by PBI-008 and PBI-009

Use `App\Services\LaporanSekitarService::cariSekitar($laporan, $radiusM = null, $rentangJam = null)` for nearby reports. It applies bounding-box prefiltering, then `Geo::jarakMeter()`, eager-loads photos and wilayah, excludes the selected report, and sorts by distance. `titikDalamArea($batas, $filter, $limit)` handles bounded viewport queries.

Use `<x-badge-kepercayaan :level="$level" />` with `null`, `tinggi`, `sedang`, or `rendah`. Null renders “Belum dinilai”; JS counterpart is the map's `badge()` helper.

## Demo data and configuration

Run `php artisan db:seed --class=Database\\Seeders\\LaporanDemoSeeder` after wilayah seeding. It creates three sample clusters, a remote report, and a report older than 24 hours; tiny demo photo content is generated under `storage/app/private/demo-foto` at runtime. Seeder is intentionally not called by `DatabaseSeeder`.

`ALIR_PETA_BATAS_MARKER`, `ALIR_PETA_PUSAT_LAT`, `ALIR_PETA_PUSAT_LNG`, and `ALIR_PETA_ZOOM_DEFAULT` configure the map. `ALIR_KLUSTER_RADIUS_M` and `ALIR_KLUSTER_RENTANG_JAM` remain the shared source for radius and time window.

## PBI-008: Tingkat Kepercayaan Laporan

`App\Services\KepercayaanLaporanService` is the single source for confidence analysis. Call `analisis($laporan)` for the level, Indonesian reasons, normalized independent reporter count, latest answered confirmation summary, and active thresholds. Call `hitungUlang($laporan)` after a workflow action when a caller needs a synchronous refresh; it saves quietly and returns `true` only when the level changed. The model methods `jumlahPelaporIndependen()`, `hitungKepercayaan()`, and `perbaruiKepercayaan()` remain compatible delegates.

Independent reports use normalized WhatsApp numbers: remove non-digits, convert a leading `0` to `62`, then count unique values. The report itself counts as one reporter. Rejected reports do not count. Merged child reports count for their parent, even when outside the parent's time or distance window. Other nearby reports use `LaporanSekitarService::cariSekitar()` and the PBI-007 radius and symmetric time window.

An answered field confirmation overrides reporter count. The latest `dijawab` confirmation wins: `sesuai` yields high; `tidak_sesuai` and `tidak_ditemukan` yield low. `diminta` confirmations do not affect the level. `KonfirmasiLapanganObserver` refreshes confidence after confirmation changes.

`LaporanBanjirObserver` refreshes a new or changed report and its affected neighbors. Observer failures are reported and swallowed so report submission succeeds. Confidence updates use `updateQuietly`, preventing observer loops. A `laporan.kepercayaan` audit row is written only when the value changes, with `user_id` null for the system action.

Configurable values in `.env`:

| Variable | Default | Meaning |
| --- | ---: | --- |
| `ALIR_KLUSTER_RADIUS_M` | `500` | Nearby report radius. |
| `ALIR_KLUSTER_RENTANG_JAM` | `24` | Time window on each side of report creation. |
| `ALIR_KEPERCAYAAN_LAPORAN_TINGGI` | `3` | Independent reporter count for high confidence. |
| `ALIR_KEPERCAYAAN_LAPORAN_SEDANG` | `2` | Independent reporter count for medium confidence. |
| `ALIR_KEPERCAYAAN_FOTO_SEDANG` | `false` | When true, a report with at least one photo has at least medium confidence. |

Thresholds can be adjusted after trials. Photos do not raise confidence by default because report creation already requires a photo. The team must decide whether `ALIR_KEPERCAYAAN_FOTO_SEDANG` should become true after trials.

`admin.peta-laporan.sekitar` now fills `laporan.kepercayaan` and each nearby item's `kepercayaan` with `level`, `alasan`, `jumlah_pelapor_independen`, `konfirmasi`, and `ambang`. Marker filtering accepts `kepercayaan[]` with `tinggi`, `sedang`, or `rendah`. The map shows a confidence badge in the detail panel, nearby cards, and marker popup; the detail panel includes the reasons and confirmation summary. The read-only “Aturan penilaian” card reflects current config values and names `.env` as their source.

Backfill null levels with `php artisan alir:hitung-kepercayaan`; pass `--semua` to recompute every report. The command processes rows in chunks of 100 and prints progress. Demo flow: run `php artisan db:seed --class=Database\\Seeders\\LaporanDemoSeeder`, then `php artisan alir:hitung-kepercayaan --semua`.
