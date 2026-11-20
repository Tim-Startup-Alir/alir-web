<?php

namespace App\Services;

use App\Models\LaporanBanjir;
use App\Support\Geo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LaporanSekitarService
{
    private ?Collection $candidatePool = null;

    /** @var array<int, true> */
    private array $prefetchedTargetIds = [];

    private int $prefetchedRadiusM = 0;

    private int $prefetchedRentangJam = 0;

    public function siapkanKonteks(Collection $laporan): void
    {
        $this->candidatePool = null;
        $this->prefetchedTargetIds = [];
        $target = $laporan->filter(fn (LaporanBanjir $item) => $item->exists && $item->created_at !== null);

        if ($target->isEmpty()) {
            return;
        }

        $this->prefetchedRadiusM = (int) config('alir.kepercayaan.radius_m');
        $this->prefetchedRentangJam = (int) config('alir.kepercayaan.rentang_jam');
        $this->prefetchedTargetIds = $target->mapWithKeys(fn (LaporanBanjir $item) => [$item->getKey() => true])->all();
        $batas = $target->map(function (LaporanBanjir $item) {
            $lat = (float) $item->latitude;
            $lng = (float) $item->longitude;
            $dLat = $this->prefetchedRadiusM / 110_000;
            $dLng = $this->prefetchedRadiusM / (110_000 * max(cos(deg2rad($lat)), 0.01));

            return [
                'south' => $lat - $dLat, 'north' => $lat + $dLat,
                'west' => $lng - $dLng, 'east' => $lng + $dLng,
                'from' => $item->created_at->copy()->subHours($this->prefetchedRentangJam),
                'to' => $item->created_at->copy()->addHours($this->prefetchedRentangJam),
            ];
        });
        $south = $batas->min('south');
        $north = $batas->max('north');
        $west = $batas->min('west');
        $east = $batas->max('east');
        $from = $batas->min('from');
        $to = $batas->max('to');

        $this->candidatePool = LaporanBanjir::query()->with(['foto', 'wilayah', 'duplikat'])
            ->whereBetween('latitude', [$south, $north])
            ->whereBetween('longitude', [$west, $east])
            ->whereBetween('created_at', [$from, $to])
            ->get();
    }

    public function cariSekitar(LaporanBanjir $laporan, ?int $radiusM = null, ?int $rentangJam = null): Collection
    {
        $radiusM ??= config('alir.kepercayaan.radius_m');
        $rentangJam ??= config('alir.kepercayaan.rentang_jam');
        $lat = (float) $laporan->latitude;
        $lng = (float) $laporan->longitude;
        $dLat = $radiusM / 110_000;
        $dLng = $radiusM / (110_000 * max(cos(deg2rad($lat)), 0.01));
        $waktu = $laporan->created_at ?? now();

        $batasWaktu = [$waktu->copy()->subHours($rentangJam), $waktu->copy()->addHours($rentangJam)];
        $kandidat = isset($this->prefetchedTargetIds[$laporan->getKey()])
            && $radiusM <= $this->prefetchedRadiusM
            && $rentangJam <= $this->prefetchedRentangJam
            ? $this->candidatePool->filter(fn (LaporanBanjir $item) => $item->getKey() !== $laporan->getKey()
                && (float) $item->latitude >= $lat - $dLat && (float) $item->latitude <= $lat + $dLat
                && (float) $item->longitude >= $lng - $dLng && (float) $item->longitude <= $lng + $dLng
                && $item->created_at >= $batasWaktu[0] && $item->created_at <= $batasWaktu[1])
                ->map(fn (LaporanBanjir $item) => clone $item)
            : LaporanBanjir::query()->with(['foto', 'wilayah'])
                ->where('id', '!=', $laporan->getKey())
                ->whereBetween('latitude', [$lat - $dLat, $lat + $dLat])
                ->whereBetween('longitude', [$lng - $dLng, $lng + $dLng])
                ->whereBetween('created_at', $batasWaktu)
                ->get();

        return $kandidat
            ->map(function (LaporanBanjir $item) use ($lat, $lng, $waktu) {
                $jarak = Geo::jarakMeter($lat, $lng, (float) $item->latitude, (float) $item->longitude);
                $item->setAttribute('jarak_m', $jarak);
                $item->setAttribute('selisih_menit', (int) round(abs($item->created_at->diffInMinutes($waktu, true))));

                return $item;
            })
            ->filter(fn (LaporanBanjir $item) => $item->jarak_m <= $radiusM)
            ->sortBy('jarak_m')->values();
    }

    public function titikDalamArea(array $batas, array $filter, int $limit): Collection
    {
        return LaporanBanjir::query()
            ->whereBetween('latitude', [$batas['south'], $batas['north']])
            ->whereBetween('longitude', [$batas['west'], $batas['east']])
            ->when($filter['status'] ?? [], fn (Builder $q, array $status) => $q->whereIn('status', $status))
            ->when($filter['kepercayaan'] ?? [], fn (Builder $q, array $levels) => $q->whereIn('tingkat_kepercayaan', $levels))
            ->when($filter['wilayah_id'] ?? null, fn (Builder $q, $id) => $q->where('wilayah_id', $id))
            ->orderByDesc('created_at')->limit($limit + 1)->get();
    }
}
