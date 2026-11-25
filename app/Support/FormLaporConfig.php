<?php

namespace App\Support;

use App\Models\Wilayah;

/**
 * Konfigurasi modal "Lapor Banjir" (PBI-004/005) yang dikirim ke JavaScript publik.
 * Hanya data publik: batas wilayah cakupan, endpoint, site key CAPTCHA, dan batas foto.
 */
class FormLaporConfig
{
    /** @return array<string, mixed> */
    public static function data(): array
    {
        $wilayah = Wilayah::aktif()->orderBy('nama_kelurahan')
            ->get(['id', 'nama_kelurahan', 'kecamatan', 'latitude', 'longitude', 'batas_wilayah', 'radius_cakupan_m'])
            ->map(fn (Wilayah $w) => [
                'nama' => $w->nama_kelurahan,
                'kecamatan' => $w->kecamatan,
                'lat' => (float) $w->latitude,
                'lng' => (float) $w->longitude,
                'radius' => $w->radiusCakupan(),
                'batas' => $w->batas_wilayah ?: null,
            ])->values();

        return [
            'wilayah' => $wilayah,
            'pusat' => $wilayah->isNotEmpty()
                ? [$wilayah->avg('lat'), $wilayah->avg('lng')]
                : config('alir.peta.pusat_default'),
            'url' => [
                'kirim' => route('lapor.store'),
                'cek' => route('lapor.cek-lokasi'),
            ],
            'turnstile' => (string) config('services.turnstile.site_key'),
            'fotoMaksKb' => (int) config('alir.laporan.foto_maks_kb', 5120),
        ];
    }
}
