<?php

namespace Database\Seeders;

use App\Models\NomorDarurat;
use Illuminate\Database\Seeder;

class NomorDaruratSeeder extends Seeder
{
    /**
     * FR-20: daftar nomor darurat Kota Pekalongan di landing page.
     *
     * Sumber (dicek 14-11-2026):
     * - BPBD Kota Pekalongan, halaman "Nomor Telpon Darurat":
     *   https://bpbd.pekalongankota.go.id/halaman/nomor-telpon-darurat.html
     *   (Contact Center Darurat Penanggulangan Bencana, Pemadam Kebakaran, PMI, Polres Kota)
     * - Portal Pemkot Pekalongan, "Nomor Telepon Penting":
     *   https://portal.pekalongankota.go.id/front/site/telepon
     *   (112, Damkar, Polres, PLN Area Pekalongan, RSUD Bendan)
     * Nomor dapat berubah; periksa ulang secara berkala dan perbarui lewat panel Tim Startup ALIR.
     */
    public function run(): void
    {
        $nomor = [
            [
                'nama' => 'Call Center Kegawatdaruratan Kota Pekalongan',
                'nomor' => '112',
                'keterangan' => 'Bebas pulsa, 24 jam. Terhubung ke BPBD, Damkar, Dinkes, PMI, Polres, dan PLN.',
            ],
            [
                'nama' => 'BPBD Kota Pekalongan',
                'nomor' => '0815-4800-4000',
                'keterangan' => 'Contact center darurat penanggulangan bencana (banjir/rob).',
            ],
            [
                'nama' => 'Pemadam Kebakaran Kota Pekalongan',
                'nomor' => '(0285) 435301',
                'keterangan' => 'Kebakaran dan penyelamatan (rescue).',
            ],
            [
                'nama' => 'PMI Kota Pekalongan',
                'nomor' => '(0285) 421580',
                'keterangan' => 'Ambulans dan pertolongan pertama.',
            ],
            [
                'nama' => 'RSUD Bendan Kota Pekalongan',
                'nomor' => '(0285) 437222',
                'keterangan' => 'Rumah sakit umum daerah.',
            ],
            [
                'nama' => 'Polres Pekalongan Kota',
                'nomor' => '(0285) 421692',
                'keterangan' => 'Keamanan dan bantuan kepolisian.',
            ],
            [
                'nama' => 'PLN Area Pekalongan',
                'nomor' => '(0285) 434785',
                'keterangan' => 'Gangguan listrik. Call center nasional PLN 24 jam: 123.',
            ],
        ];

        NomorDarurat::query()->delete();

        foreach ($nomor as $i => $data) {
            NomorDarurat::create($data + ['urutan' => $i + 1, 'is_aktif' => true]);
        }
    }
}
