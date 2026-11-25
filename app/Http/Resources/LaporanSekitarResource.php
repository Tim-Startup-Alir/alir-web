<?php

namespace App\Http\Resources;

use App\Services\KepercayaanLaporanService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanSekitarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $analisis = app(KepercayaanLaporanService::class)->analisis($this->resource);

        return [
            'id' => $this->id, 'kode' => $this->kode_pelacakan, 'alamat_singkat' => $this->alamat_singkat,
            'wilayah_id' => $this->wilayah_id, 'wilayah' => $this->wilayah?->nama_kelurahan, 'lat' => (float) $this->latitude, 'lng' => (float) $this->longitude,
            'severity' => $this->severity, 'jumlah_warga' => $this->jumlah_warga, 'status' => $this->status,
            'tingkat_kepercayaan' => $analisis['level'], 'catatan' => $this->catatan,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'foto' => $this->foto->map(fn ($foto) => ['id' => $foto->id, 'url' => route('admin.laporan.foto', [$this->id, $foto->id])])->values(),
            'kepercayaan' => $analisis,
            'konfirmasi' => $this->resource->relationLoaded('konfirmasiLapangan')
                ? $this->konfirmasiLapangan
                    ->where('status', 'dijawab')
                    ->sortByDesc('dijawab_pada')
                    ->values()
                    ->map(fn ($konfirmasi) => [
                        'hasil' => $konfirmasi->hasil,
                        'komunitas' => $konfirmasi->komunitasRelawan?->nama,
                        'waktu' => $konfirmasi->dijawab_pada?->toIso8601String(),
                        'catatan' => $konfirmasi->catatan,
                        'foto_url' => filled($konfirmasi->foto_path)
                            ? route('admin.konfirmasi.foto', [$this->id, $konfirmasi->id])
                            : null,
                    ])
                : [],
            ...($this->resource->getAttribute('jarak_m') !== null ? ['jarak_m' => $this->jarak_m, 'selisih_menit' => $this->selisih_menit] : []),
        ];
    }
}
