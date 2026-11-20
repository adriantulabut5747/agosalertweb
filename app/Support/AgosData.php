<?php

namespace App\Support;

/**
 * Fixed lists shared with the Flutter app (lib/data/demo_data.dart,
 * lib/services/rescue.dart). Keep them in sync.
 */
class AgosData
{
    public const BARANGAYS = [
        'Atlu-Bola', 'Bical', 'Bundagul', 'Cacutud', 'Calumpang', 'Camachiles',
        'Dapdap', 'Dau', 'Dolores', 'Duquit', 'Lakandula', 'Mabiga',
        'Macapagal Village', 'Mamatitang', 'Mangalit', 'Marcos Village',
        'Mawaque', 'Paralayunan', 'Poblacion', 'San Francisco', 'San Joaquin',
        'Santa Ines', 'Santa Maria', 'Santo Rosario', 'Sapang Balen',
        'Sapang Biabas', 'Tabun',
    ];

    /** Barangay centers [lat, lng] from OpenStreetMap (kBarangayPoints in the app). */
    public const BARANGAY_POINTS = [
        'Atlu-Bola' => [15.2355, 120.5823],
        'Bical' => [15.1949, 120.6198],
        'Bundagul' => [15.2273, 120.6095],
        'Calumpang' => [15.2205, 120.5326],
        'Macapagal Village' => [15.2104, 120.5361],
        'Mangalit' => [15.2266, 120.5782],
        'Marcos Village' => [15.2094, 120.5301],
        'San Joaquin' => [15.2307, 120.5714],
        'Santa Maria' => [15.2270, 120.5922],
        'Santo Rosario' => [15.2155, 120.5789],
        'Sapang Balen' => [15.2504, 120.6005],
        'Cacutud' => [15.2381, 120.5715],
        'Camachiles' => [15.1924, 120.5861],
        'Dapdap' => [15.2245, 120.6127],
        'Dau' => [15.1761, 120.5887],
        'Dolores' => [15.2161, 120.5589],
        'Duquit' => [15.1779, 120.6014],
        'Lakandula' => [15.1727, 120.5846],
        'Mabiga' => [15.2133, 120.5799],
        'Mamatitang' => [15.2301, 120.5726],
        'Mawaque' => [15.2088, 120.5914],
        'Paralayunan' => [15.2327, 120.6144],
        'Poblacion' => [15.2236, 120.5717],
        'San Francisco' => [15.2177, 120.5786],
        'Santa Ines' => [15.2238, 120.5787],
        'Sapang Biabas' => [15.1988, 120.6026],
        'Tabun' => [15.2477, 120.5659],
    ];

    /** timeAgo() in the app: Just now, 5m ago, 3h ago, 2d ago. */
    public static function timeAgo(\Carbon\CarbonInterface $t): string
    {
        $m = (int) $t->diffInMinutes(now());

        return match (true) {
            $m < 1 => 'Just now', $m < 60 => "{$m}m ago", $m < 1440 => intdiv($m, 60).'h ago', default => intdiv($m, 1440).'d ago',
        };
    }

    /** EvacuationCenter.icon in the app: by what the place is. */
    public static function centerIcon(string $name): string
    {
        $n = strtolower($name);

        return match (true) {
            str_contains($n, 'school') => 'book_fill',
            str_contains($n, 'sports') || str_contains($n, 'gym') => 'sportscourt_fill',
            str_contains($n, 'hall') => 'building_2_fill',
            default => 'house_alt_fill',
        };
    }

    /** Straight-line km from the center of [barangay], or null (no made-up numbers). */
    public static function distanceKm(?string $barangay, float $lat, float $lng): ?float
    {
        $from = self::BARANGAY_POINTS[$barangay ?? ''] ?? null;
        if (! $from) {
            return null;
        }
        [$la, $lo] = array_map('deg2rad', $from);
        [$lb, $lob] = [deg2rad($lat), deg2rad($lng)];
        $h = sin(($lb - $la) / 2) ** 2 + cos($la) * cos($lb) * sin(($lob - $lo) / 2) ** 2;

        return 6371.0 * 2 * asin(sqrt($h));
    }

    /** Real hotlines (same as kHotlines in the app). */
    public const HOTLINES = [
        ['National Emergency Hotline', '911', 'Emergency'],
        ['Mabalacat CDRRMO', '0998-999-4357', 'CDRRMO'],
        ['Bureau of Fire Protection', '0933-990-9960', 'Fire'],
        ['Mabalacat Police (PNP)', '0998-598-5458', 'Police'],
        ['Mabalacat City Hall', '(045) 649-8620', 'City Hall'],
    ];

    public const RESCUE_NEEDS = [
        'elderly' => 'Elderly',
        'pwd' => 'PWD',
        'infant_child' => 'Infant / child',
        'pregnant' => 'Pregnant',
        'medical_care' => 'Needs medical care',
    ];

    public const RESCUE_STATUSES = [
        'received' => 'Received',
        'verifying' => 'Verifying',
        'help_on_the_way' => 'Help on the way',
        'resolved' => 'Resolved',
        'flagged_false' => 'Marked as false',
    ];

    // No 'landslide' (Nov 20 2026, Adrian); the database still accepts it.
    public const INCIDENT_TYPES = [
        'flooding', 'blocked road', 'fallen power line', 'stranded people', 'other',
    ];

    /** Water depth in body terms (lib/widgets/flood_depth.dart FloodLevel). */
    public static function depthLabel(int $cm): string
    {
        return match (true) {
            $cm < 5 => 'Dry', $cm < 30 => 'ankle-deep', $cm < 70 => 'knee-deep',
            $cm < 107 => 'waist-deep', $cm < 140 => 'chest-deep', default => 'over head',
        };
    }

    /** Incident type -> [icon font class, icon name] (kIncidentTypes in the app). */
    public static function typeIcon(string $type): array
    {
        return match ($type) {
            'flooding' => ['ai', 'flood'],
            'blocked road' => ['ai', 'road_block'],
            'landslide' => ['ai', 'landslide'],
            'fallen power line' => ['i', 'bolt_fill'],
            'stranded people' => ['i', 'person_3_fill'],
            default => ['i', 'info_circle_fill'],
        };
    }

    /** Water depth -> risk (FR-S2): normal up to 20 cm, moderate to 50, high above. */
    public static function riskForDepth(?int $cm): string
    {
        if ($cm === null) {
            return 'normal';
        }

        return $cm <= 20 ? 'normal' : ($cm <= 50 ? 'moderate' : 'high');
    }
}
