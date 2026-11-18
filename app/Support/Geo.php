<?php

namespace App\Support;

/**
 * Utilitas geospasial sederhana tanpa ekstensi spasial database.
 */
class Geo
{
    private const RADIUS_BUMI_M = 6371000;

    /**
     * Jarak dua titik (meter) dengan rumus Haversine.
     */
    public static function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::RADIUS_BUMI_M * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Apakah titik berada di dalam geometri GeoJSON (Polygon atau MultiPolygon, boleh dibungkus Feature).
     * Koordinat GeoJSON berurutan [longitude, latitude]. Lubang (inner ring) diperhitungkan.
     *
     * @param  array<string, mixed>  $geojson
     */
    public static function dalamGeoJson(float $lat, float $lng, array $geojson): bool
    {
        $geometri = $geojson['type'] === 'Feature' ? $geojson['geometry'] : $geojson;

        $poligon = match ($geometri['type'] ?? null) {
            'Polygon' => [$geometri['coordinates']],
            'MultiPolygon' => $geometri['coordinates'],
            default => [],
        };

        foreach ($poligon as $ring) {
            if (! self::dalamRing($lat, $lng, $ring[0])) {
                continue;
            }

            $diLubang = false;
            foreach (array_slice($ring, 1) as $lubang) {
                if (self::dalamRing($lat, $lng, $lubang)) {
                    $diLubang = true;
                    break;
                }
            }

            if (! $diLubang) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ray casting pada satu ring [[lng, lat], ...].
     *
     * @param  array<int, array{0: float, 1: float}>  $ring
     */
    private static function dalamRing(float $lat, float $lng, array $ring): bool
    {
        $dalam = false;
        $n = count($ring);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $dalam = ! $dalam;
            }
        }

        return $dalam;
    }
}
