<?php

namespace Database\Seeders;

use App\Models\TemplateWhatsapp;
use Illuminate\Database\Seeder;

class TemplateWhatsappSeeder extends Seeder
{
    /**
     * Satu template per status laporan (FR-30). Placeholder: {kode}, {lokasi}, {keterangan}.
     */
    public function run(): void
    {
        $template = [
            'menunggu_verifikasi' => [
                'Laporan diterima',
                'Halo, laporan banjir Anda dengan kode {kode} di {lokasi} sudah kami terima dan sedang diverifikasi oleh Tim ALIR. Terima kasih.',
            ],
            'terverifikasi' => [
                'Laporan valid',
                'Laporan {kode} di {lokasi} telah diverifikasi valid. Tim ALIR sedang menyiapkan penugasan relawan.',
            ],
            'ditugaskan' => [
                'Relawan ditugaskan',
                'Laporan {kode} di {lokasi} sudah ditugaskan ke komunitas relawan. {keterangan}',
            ],
            'ditangani' => [
                'Tim menuju / menangani lokasi',
                'Relawan sedang menuju/menangani lokasi laporan {kode} di {lokasi}. {keterangan}',
            ],
            'selesai' => [
                'Penanganan selesai',
                'Penanganan laporan {kode} di {lokasi} telah selesai. Terima kasih telah melapor melalui ALIR.',
            ],
            'ditolak' => [
                'Laporan ditolak',
                'Mohon maaf, laporan {kode} di {lokasi} tidak dapat kami proses. Alasan: {keterangan}',
            ],
            'digabung' => [
                'Laporan digabung',
                'Laporan {kode} di {lokasi} digabung dengan laporan lain di lokasi yang sama. {keterangan}',
            ],
        ];

        foreach ($template as $status => [$judul, $isi]) {
            TemplateWhatsapp::updateOrCreate(
                ['status_laporan' => $status],
                ['judul' => $judul, 'isi_pesan' => $isi],
            );
        }
    }
}
