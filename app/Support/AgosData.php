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

    public const INCIDENT_TYPES = [
        'flooding', 'blocked road', 'landslide', 'fallen power line', 'stranded people', 'other',
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
