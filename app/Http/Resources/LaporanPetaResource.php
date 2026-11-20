<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanPetaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'kode' => $this->kode_pelacakan, 'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude, 'severity' => $this->severity, 'status' => $this->status,
            'tingkat_kepercayaan' => $this->tingkat_kepercayaan,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}
