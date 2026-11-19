<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Live Mabalacat weather from Open-Meteo (free, no key), the same request
 * and the same flood outlook rule as the Flutter app (lib/services/
 * weather.dart, floodOutlook). Cached 10 minutes so every page view
 * doesn't call Open-Meteo. Returns null when Open-Meteo can't be reached:
 * the page then says so (no made-up numbers).
 */
class Weather
{
    public function now(): ?array
    {
        return Cache::remember('weather.v2', 600, function () {
            $res = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => 15.2236, // Mabalacat City
                'longitude' => 120.5714,
                'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,is_day,cloud_cover,precipitation',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,precipitation_sum',
                'forecast_days' => 5,
                'timezone' => 'Asia/Manila',
            ]);
            if (! $res->ok()) {
                return null;
            }
            $j = $res->json();
            $days = [];
            foreach ($j['daily']['time'] as $i => $date) {
                $days[] = [
                    'date' => $date,
                    'code' => $j['daily']['weather_code'][$i],
                    'max' => $j['daily']['temperature_2m_max'][$i],
                    'min' => $j['daily']['temperature_2m_min'][$i],
                    'chance' => $j['daily']['precipitation_probability_max'][$i],
                    'rain' => $j['daily']['precipitation_sum'][$i],
                ];
            }

            return [
                'temp' => $j['current']['temperature_2m'],
                'feels' => $j['current']['apparent_temperature'],
                'humidity' => $j['current']['relative_humidity_2m'],
                'wind' => $j['current']['wind_speed_10m'],
                'code' => $j['current']['weather_code'],
                'clouds' => $j['current']['cloud_cover'],
                'rain_now' => $j['current']['precipitation'],
                'is_day' => $j['current']['is_day'],
                'time' => $j['current']['time'],
                'days' => $days,
            ];
        });
    }

    /**
     * The app's flood outlook, from today's rain forecast. Not an official
     * warning. HIGH: 30 mm+ today, or a storm with a 60%+ chance.
     * MODERATE: 10 mm+, or a 70%+ chance. LOW: everything else.
     */
    public static function outlook(array $w): array
    {
        $today = $w['days'][0];
        $chance = $today['chance'] ?? 0;
        $stormy = $w['code'] >= 95 || $w['code'] == 65 || $w['code'] == 82;
        if ($today['rain'] >= 30 || ($stormy && $chance >= 60)) {
            return ['high', 'High flood risk', 'Heavy rain expected. Flooding is likely in low-lying areas. Prepare to evacuate if told to.'];
        }
        if ($today['rain'] >= 10 || $chance >= 70) {
            return ['moderate', 'Moderate flood risk', 'Significant rain expected. Stay alert near rivers and low-lying streets.'];
        }

        return ['normal', 'Low flood risk', 'Little rain expected today. No flooding expected.'];
    }

    /** WMO code -> [CupertinoIcons name, color], like the app's WeatherGlyph. */
    public static function glyph(int $code): array
    {
        return match (true) {
            $code === 0 => ['sun_max_fill', '#ffd60a'],
            $code <= 2 => ['cloud_sun_fill', '#ffd60a'],
            $code === 3 => ['cloud_fill', '#e5e5ea'],
            $code <= 48 => ['cloud_fog_fill', '#c7c7cc'],
            $code <= 57 => ['cloud_drizzle_fill', '#64b5ff'],
            $code <= 67, $code >= 80 && $code <= 82 => ['cloud_rain_fill', '#64b5ff'],
            $code <= 77, $code <= 86 => ['snow', '#e5e5ea'],
            default => ['cloud_bolt_rain_fill', '#ffd60a'],
        };
    }

    /** WMO weather code -> words (same groups as weatherInfo() in the app). */
    public static function describe(int $code): string
    {
        return match (true) {
            $code === 0 => 'Clear sky',
            $code <= 2 => 'Partly cloudy',
            $code === 3 => 'Cloudy',
            $code <= 48 => 'Fog',
            $code <= 57 => 'Drizzle',
            $code <= 67 => 'Rain',
            $code <= 77 => 'Snow',
            $code <= 82 => 'Rain showers',
            $code <= 86 => 'Snow showers',
            default => 'Thunderstorm',
        };
    }
}
