<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\JadwalKesiapsiagaan;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\Posko;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\KepercayaanLaporanService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PenugasanDemoSeeder extends Seeder
{
    public function run(): void
    {
        $wilayah = Wilayah::query()
            ->whereIn('nama_kelurahan', ['Tirto', 'Pasirkratonkramat', 'Degayu'])
            ->get()
            ->keyBy('nama_kelurahan');
        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();

        foreach (['Tirto', 'Pasirkratonkramat', 'Degayu'] as $nama) {
            if (! $wilayah->has($nama)) {
                throw new RuntimeException("Wilayah demo F-05 {$nama} tidak ditemukan.");
            }
        }

        DB::transaction(function () use ($wilayah, $admin): void {
            $laporan = $this->buatLaporanDemo($wilayah, $admin);
            $komunitas = $this->buatKomunitasDemo($wilayah);
            $this->buatPenugasanDemo(
                $laporan['tirto-medium-01'],
                $komunitas['sibuk-pasir'],
                $admin,
                2,
                1
            );
            $this->buatPenugasanDemo(
                $laporan['pasir-high-01'],
                $komunitas['penuh-degayu'],
                $admin,
                $komunitas['penuh-degayu']->kapasitas_max,
                $komunitas['penuh-degayu']->jumlah_perahu
            );
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Wilayah>  $wilayah
     * @return array<string, LaporanBanjir>
     */
    private function buatLaporanDemo($wilayah, User $admin): array
    {
        $jumlahSedang = max(2, (int) config('alir.kepercayaan.laporan_sedang'));
        $jumlahTinggi = max(
            $jumlahSedang + 1,
            (int) config('alir.kepercayaan.laporan_tinggi')
        );
        $waktuDasar = now()->subDays(30)->startOfDay()->addHours(12);
        $spesifikasi = [];

        for ($i = 1; $i <= $jumlahSedang; $i++) {
            $spesifikasi[] = [
                'key' => 'tirto-medium-'.sprintf('%02d', $i),
                'kode' => 'DEMO-F05-TIR-M'.sprintf('%02d', $i),
                'wilayah' => $wilayah['Tirto'],
                'latitude' => -6.8925,
                'longitude' => 109.6515,
                'severity' => ['ringan', 'sedang', 'berat'][($i - 1) % 3],
                'expected_level' => 'sedang',
                'created_at' => $waktuDasar->copy()->addMinutes($i),
            ];
        }

        for ($i = 1; $i <= $jumlahTinggi; $i++) {
            $spesifikasi[] = [
                'key' => 'pasir-high-'.sprintf('%02d', $i),
                'kode' => 'DEMO-F05-PKR-H'.sprintf('%02d', $i),
                'wilayah' => $wilayah['Pasirkratonkramat'],
                'latitude' => -6.8837,
                'longitude' => 109.6569,
                'severity' => ['berat', 'sedang', 'ringan'][($i - 1) % 3],
                'expected_level' => 'tinggi',
                'created_at' => $waktuDasar->copy()->addMinutes($i),
            ];
        }

        $spesifikasi[] = [
            'key' => 'dega-low-01',
            'kode' => 'DEMO-F05-DEG-L01',
            'wilayah' => $wilayah['Degayu'],
            'latitude' => -6.877037,
            'longitude' => 109.703114,
            'severity' => 'ringan',
            'expected_level' => 'rendah',
            'created_at' => $waktuDasar,
        ];
        $spesifikasi[] = [
            'key' => 'dega-low-02',
            'kode' => 'DEMO-F05-DEG-L02',
            'wilayah' => $wilayah['Degayu'],
            'latitude' => -6.883037,
            'longitude' => 109.703114,
            'severity' => 'berat',
            'expected_level' => 'rendah',
            'created_at' => $waktuDasar,
        ];

        $laporan = LaporanBanjir::withoutEvents(function () use ($spesifikasi, $admin): array {
            $hasil = [];
            $nomorUrut = 1;

            foreach ($spesifikasi as $spec) {
                $titik = $this->titikDiWilayah(
                    $spec['wilayah'],
                    $spec['latitude'],
                    $spec['longitude']
                );
                $hasil[$spec['key']] = LaporanBanjir::updateOrCreate(
                    ['kode_pelacakan' => $spec['kode']],
                    [
                        'wilayah_id' => $spec['wilayah']->id,
                        'diverifikasi_oleh' => $admin->id,
                        'alamat_singkat' => "Laporan demo F-05 {$spec['key']}",
                        'latitude' => $titik['latitude'],
                        'longitude' => $titik['longitude'],
                        'sumber_lokasi' => 'pin',
                        'severity' => $spec['severity'],
                        'jumlah_warga' => 8 + $nomorUrut,
                        'status' => 'terverifikasi',
                        'nomor_wa_pelapor' => '628559900'.str_pad((string) $nomorUrut, 4, '0', STR_PAD_LEFT),
                        'setuju_dihubungi' => false,
                        'diverifikasi_pada' => now(),
                        'created_at' => $spec['created_at'],
                    ]
                );
                $nomorUrut++;
            }

            return $hasil;
        });

        $service = app(KepercayaanLaporanService::class);
        foreach ($spesifikasi as $spec) {
            $service->hitungUlang($laporan[$spec['key']]);
            $level = $laporan[$spec['key']]->fresh()->tingkat_kepercayaan;
            if ($level !== $spec['expected_level']) {
                throw new RuntimeException(
                    "Tingkat kepercayaan laporan demo {$spec['kode']} seharusnya {$spec['expected_level']}, didapat {$level}."
                );
            }
        }

        return $laporan;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Wilayah>  $wilayah
     * @return array<string, KomunitasRelawan>
     */
    private function buatKomunitasDemo($wilayah): array
    {
        $spesifikasi = [
            'siaga-tirto' => ['Demo F-05 Siaga Tirto', 'Tirto', 'tirto', true, true, 10, 2, 'tersedia'],
            'sibuk-pasir' => ['Demo F-05 Sibuk Pasir', 'Pasirkratonkramat', 'pasir', true, true, 10, 3, 'tersedia'],
            'penuh-degayu' => ['Demo F-05 Penuh Degayu', 'Degayu', 'dega', true, true, 4, 2, 'tersedia'],
            'luar-jam-tirto' => ['Demo F-05 Luar Jam Tirto', 'Tirto', 'tirto', true, true, 10, 2, 'luar_jam'],
            'tanpa-jadwal-pasir' => ['Demo F-05 Tanpa Jadwal Pasir', 'Pasirkratonkramat', 'pasir', true, true, 10, 2, null],
            'terbatas-degayu' => ['Demo F-05 Terbatas Degayu', 'Degayu', 'dega', true, true, 10, 2, 'siaga_terbatas'],
            'belum-terverifikasi-tirto' => ['Demo F-05 Belum Terverifikasi', 'Tirto', 'tirto', false, true, 10, 2, 'tersedia'],
            'nonaktif-pasir' => ['Demo F-05 Nonaktif Pasir', 'Pasirkratonkramat', 'pasir', true, false, 10, 2, 'tersedia'],
            'posko-luar-degayu' => ['Demo F-05 Posko Luar Degayu', 'Degayu', 'luar', true, true, 10, 2, 'tersedia'],
        ];
        $hasil = [];
        $nomorUrut = 1;

        foreach ($spesifikasi as $slug => [$nama, $namaWilayah, $titik, $terverifikasi, $aktif, $kapasitas, $perahu, $jadwal]) {
            $area = $wilayah[$namaWilayah];
            $email = "demo.f05.{$slug}@alir.test";
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $nama,
                    'password' => env('F05_DEMO_PASSWORD', 'password'),
                    'role' => User::ROLE_KOMUNITAS,
                    'is_active' => $aktif,
                    'wajib_ganti_sandi' => false,
                    'email_verified_at' => now(),
                ]
            );
            $komunitas = KomunitasRelawan::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'wilayah_id' => $area->id,
                    'nama' => $nama,
                    'nama_pic' => "PIC {$nama}",
                    'nomor_whatsapp_pic' => '628559910'.str_pad((string) $nomorUrut, 3, '0', STR_PAD_LEFT),
                    'total_anggota' => $kapasitas,
                    'kapasitas_max' => $kapasitas,
                    'jumlah_perahu' => $perahu,
                    'status_pendaftaran' => $terverifikasi ? 'disetujui' : 'menunggu',
                    'izin_tampil_publik' => false,
                    'menyetujui_syarat_pada' => now(),
                    'diverifikasi_pada' => $terverifikasi ? now() : null,
                ]
            );
            $koordinat = $titik === 'luar'
                ? $this->titikDiLuarCakupan(-6.86, 109.68)
                : $this->titikDiWilayah(...[
                    $area,
                    ...match ($titik) {
                        'tirto' => [-6.8928121, 109.6515333],
                        'pasir' => [-6.8839083, 109.6567382],
                        default => [-6.8770370, 109.7031140],
                    },
                ]);
            $komunitas->posko()->updateOrCreate(
                ['nama' => "Posko Demo F-05 {$slug}"],
                [
                    'alamat' => "Posko demo {$namaWilayah}",
                    'latitude' => $koordinat['latitude'],
                    'longitude' => $koordinat['longitude'],
                    'radius_m' => 1000,
                    'pernyataan_luar_cakupan_pada' => $titik === 'luar' ? now() : null,
                ]
            );
            $this->buatJadwalDemo($komunitas, $jadwal);
            $hasil[$slug] = $komunitas;
            $nomorUrut++;
        }

        return $hasil;
    }

    private function buatJadwalDemo(KomunitasRelawan $komunitas, ?string $jenis): void
    {
        if ($jenis === null) {
            return;
        }

        if ($jenis === 'luar_jam') {
            $mulai = now()->copy()->addHours(3);
            $selesai = $mulai->copy()->addHour();
            $jamMulai = $mulai->format('H:i:s');
            $jamSelesai = $selesai->format('H:i:s');
        } else {
            $jamMulai = '00:00:00';
            $jamSelesai = '00:00:00';
        }

        $status = $jenis === 'siaga_terbatas' ? 'siaga_terbatas' : 'tersedia';
        foreach (JadwalKesiapsiagaan::HARI as $hari) {
            $komunitas->jadwalKesiapsiagaan()->updateOrCreate(
                ['hari' => $hari, 'nama_shift' => 'Demo F-05'],
                [
                    'jam_mulai' => $jamMulai,
                    'jam_selesai' => $jamSelesai,
                    'status_ketersediaan' => $status,
                    'nama_pic' => "PIC Demo {$komunitas->nama}",
                    'nomor_whatsapp_pic' => $komunitas->nomor_whatsapp_pic,
                ]
            );
        }
    }

    private function buatPenugasanDemo(
        LaporanBanjir $laporan,
        KomunitasRelawan $komunitas,
        User $admin,
        int $personil,
        int $perahu
    ): void {
        $penugasan = Penugasan::query()->firstOrNew([
            'laporan_banjir_id' => $laporan->id,
            'komunitas_relawan_id' => $komunitas->id,
        ]);
        $baru = ! $penugasan->exists;
        $penugasan->fill([
            'ditugaskan_oleh' => $admin->id,
            'jumlah_personil_tugas' => $personil,
            'armada_perahu_tugas' => $perahu,
            'stage' => 'menunggu',
            'catatan' => 'Penugasan demo F-05.',
        ]);
        $penugasan->save();

        if ($baru) {
            AuditLog::catat(
                'penugasan.buat',
                $penugasan,
                "Membuat penugasan demo F-05 untuk laporan {$laporan->kode_pelacakan}",
                $admin
            );
        }

        $laporan->sinkronStatusPenanganan();
    }

    /**
     * @return array{latitude: float, longitude: float}
     */
    private function titikDiWilayah(Wilayah $expected, float $latitude, float $longitude): array
    {
        $actual = Wilayah::temukanDari($latitude, $longitude);
        if (! $actual?->is($expected)) {
            throw new RuntimeException("Koordinat demo tidak jatuh di wilayah {$expected->nama_kelurahan}.");
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }

    /**
     * @return array{latitude: float, longitude: float}
     */
    private function titikDiLuarCakupan(float $latitude, float $longitude): array
    {
        if (Wilayah::temukanDari($latitude, $longitude) !== null) {
            throw new RuntimeException('Koordinat posko demo yang direncanakan di luar cakupan ternyata masuk wilayah pilot.');
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }
}
