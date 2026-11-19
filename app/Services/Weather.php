<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Live Mabalacat weather from Open-Meteo (free, no key): the SAME three
 * requests and the same rules as the Flutter app (lib/services/weather.dart
 * MabalacatWeather._openMeteo, AirQuality; lib/widgets/tugak.dart for
 * Tugak's mood and line). Keep the two in sync.
 * Cached 10 minutes like the app's load(). Returns null when Open-Meteo
 * can't be reached: the page then says so (no made-up numbers).
 */
class Weather
{
    private const LAT = '15.2236'; // Mabalacat City

    private const LON = '120.5714';

    public function now(): ?array
    {
        return Cache::remember('weather.v3', 600, fn () => $this->fetch());
    }

    private function fetch(): ?array
    {
        $at = ['latitude' => self::LAT, 'longitude' => self::LON, 'timezone' => 'Asia/Manila'];
        $res = Http::pool(fn (Pool $p) => [
            $p->as('main')->timeout(10)->get('https://api.open-meteo.com/v1/forecast', $at + [
                'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,is_day,'
                    .'wind_direction_10m,wind_gusts_10m,pressure_msl,cloud_cover,uv_index,visibility',
                'hourly' => 'temperature_2m,precipitation_probability,precipitation,weather_code,is_day,relative_humidity_2m,'
                    .'wind_speed_10m,apparent_temperature,wind_direction_10m,pressure_msl,cloud_cover,uv_index,visibility',
                'past_hours' => 24,
                'forecast_hours' => 26, // +1: an hour's rain sits on the NEXT entry
                'minutely_15' => 'precipitation',
                'forecast_minutely_15' => 9, // the current quarter + the next 2 hours
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,precipitation_sum,sunrise,sunset',
                'forecast_days' => 5,
            ]),
            // Rain chance from 5 models at once: ECMWF alone is often the wettest.
            $p->as('chance')->timeout(10)->get('https://api.open-meteo.com/v1/forecast', $at + [
                'hourly' => 'precipitation_probability',
                'models' => 'ecmwf_ifs025,gfs_seamless,icon_seamless,gem_seamless,ukmo_seamless',
                'forecast_days' => 6,
            ]),
            $p->as('air')->timeout(10)->get('https://air-quality-api.open-meteo.com/v1/air-quality', $at + [
                'current' => 'us_aqi,pm2_5,pm10',
            ]),
        ]);
        $main = $res['main'];
        if (! $main instanceof \Illuminate\Http\Client\Response || ! $main->ok()) {
            return null;
        }
        $j = $main->json();
        $chance = $res['chance'] instanceof \Illuminate\Http\Client\Response && $res['chance']->ok()
            ? self::averageChances($res['chance']->json()) : [];
        $n = fn (array $list, int $i) => $list[$i] ?? 0;

        $cur = $j['current'];
        $now = Carbon::parse($cur['time']);
        $thisHour = $now->copy()->startOfHour();
        $h = $j['hourly'];
        $hourly = [];
        $past = [];
        // Open-Meteo's rain, chance and code at 8:00 describe 7-8; the app's
        // hour T means T to T+1, so those three come from the next entry.
        for ($i = 0; $i + 1 < count($h['time']) && count($hourly) < 24; $i++) {
            $t = Carbon::parse($h['time'][$i]);
            if ($t->lt($thisHour)) {
                $past[] = [$h['time'][$i], (float) $n($h['precipitation'], $i + 1)];

                continue;
            }
            $hourly[] = [
                'time' => $h['time'][$i],
                'temp' => (float) $n($h['temperature_2m'], $i),
                'chance' => $chance[$h['time'][$i]] ?? (int) $n($h['precipitation_probability'], $i + 1),
                'rain' => (float) $n($h['precipitation'], $i + 1),
                'code' => (int) $n($h['weather_code'], $i + 1),
                'is_day' => $n($h['is_day'], $i) == 1,
                'humidity' => (int) $n($h['relative_humidity_2m'], $i),
                'wind' => (float) $n($h['wind_speed_10m'], $i),
                'feels' => (float) $n($h['apparent_temperature'], $i),
                'wind_dir' => (int) $n($h['wind_direction_10m'], $i),
                'pressure' => (float) $n($h['pressure_msl'], $i),
                'cloud' => (int) $n($h['cloud_cover'], $i),
                'uv' => (float) $n($h['uv_index'], $i),
                'visibility' => (float) $n($h['visibility'], $i),
            ];
        }

        // A day's chance = its highest 5-model hourly chance, hours not yet over.
        $dayChance = [];
        foreach ($chance as $t => $pct) {
            if (Carbon::parse($t)->lt($thisHour)) {
                continue;
            }
            $day = substr($t, 0, 10);
            $dayChance[$day] = max($dayChance[$day] ?? 0, $pct);
        }
        $d = $j['daily'];
        $days = [];
        foreach ($d['time'] as $i => $date) {
            $days[] = [
                'date' => $date,
                'max' => (float) $n($d['temperature_2m_max'], $i),
                'min' => (float) $n($d['temperature_2m_min'], $i),
                'chance' => $dayChance[$date] ?? (int) $n($d['precipitation_probability_max'], $i),
                'rain' => (float) $n($d['precipitation_sum'], $i),
                'code' => (int) $n($d['weather_code'], $i),
                'sunrise' => $d['sunrise'][$i],
                'sunset' => $d['sunset'][$i],
            ];
        }

        // 15-minute rain: the quarters that haven't ended yet.
        $next2h = [];
        foreach ($j['minutely_15']['time'] ?? [] as $i => $t) {
            if (Carbon::parse($t)->addMinutes(15)->gt($now)) {
                $next2h[] = [$t, (float) $n($j['minutely_15']['precipitation'], $i)];
            }
        }

        $air = $res['air'] instanceof \Illuminate\Http\Client\Response && $res['air']->ok() ? $res['air']->json('current') : null;

        return [
            'temp' => (float) $cur['temperature_2m'],
            'feels' => (float) $cur['apparent_temperature'],
            'humidity' => (int) $cur['relative_humidity_2m'],
            'wind' => (float) $cur['wind_speed_10m'],
            'wind_dir' => (int) ($cur['wind_direction_10m'] ?? 0),
            'gusts' => (float) ($cur['wind_gusts_10m'] ?? 0),
            'pressure' => (float) ($cur['pressure_msl'] ?? 0),
            'clouds' => (int) ($cur['cloud_cover'] ?? 0),
            'uv' => (float) ($cur['uv_index'] ?? 0),
            'visibility' => (float) ($cur['visibility'] ?? 0),
            'code' => (int) $cur['weather_code'],
            'is_day' => $cur['is_day'] == 1,
            'hourly' => $hourly,
            'past_rain' => $past,
            'days' => $days,
            'next2h' => $next2h,
            'air' => $air && isset($air['us_aqi']) ? ['aqi' => (int) $air['us_aqi'], 'pm25' => (float) $air['pm2_5']] : null,
        ];
    }

    /** averageChances() in the app: the models' mean per hour, keyed by the hour it covers. */
    public static function averageChances(array $json): array
    {
        $h = $json['hourly'];
        $cols = array_values(array_filter($h, fn ($k) => str_starts_with($k, 'precipitation_probability_'), ARRAY_FILTER_USE_KEY));
        $out = [];
        foreach ($h['time'] as $i => $t) {
            $vals = array_values(array_filter(array_map(fn ($c) => $c[$i] ?? null, $cols), fn ($v) => $v !== null));
            if ($vals) {
                $out[Carbon::parse($t)->subHour()->format('Y-m-d\TH:i')] = (int) round(array_sum($vals) / count($vals));
            }
        }

        return $out;
    }

    /** When rain starts in the next 2 hours (0.1 mm in 15 min), or null. */
    public static function rainStartsAt(array $w): ?Carbon
    {
        foreach ($w['next2h'] as [$t, $mm]) {
            if ($mm >= 0.1) {
                return Carbon::parse($t);
            }
        }
        if ($w['next2h']) {
            return null;
        }
        $end = now()->addHours(2);
        foreach ($w['hourly'] as $h) {
            if (Carbon::parse($h['time'])->lt($end) && $h['rain'] >= 0.1) {
                return Carbon::parse($h['time']);
            }
        }

        return null;
    }

    public static function nextTwoHoursText(array $w): string
    {
        $start = self::rainStartsAt($w);
        if (! $start) {
            return 'No rain in the next 2 hours';
        }

        return $start->lte(now()) ? 'Raining now' : 'Rain likely from '.$start->format('g:i A');
    }

    /**
     * floodOutlook in the app, from today's rain forecast. Not an official
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

    /** tugakMoodFor -> the scene's face (TugakWeather.face). */
    public static function face(array $w, $alert = null): string
    {
        return match (true) {
            $alert !== null || self::outlook($w)[0] === 'high' => 'alert',
            $w['code'] >= 51 || self::rainStartsAt($w) !== null => 'wet',
            ! $w['is_day'] => 'sleepy',
            default => 'stern',
        };
    }

    /** tugakLine in the app: deadpan, what to do first; no joke when serious. */
    public static function tugakLine(array $w, $alert = null): string
    {
        if ($alert) {
            $steps = $alert->steps();

            return $steps ? "{$alert->title}. {$steps[0]}." : "{$alert->title}. Stay tuned here.";
        }
        if (self::outlook($w)[0] === 'high') {
            return 'Heavy rain today. Keep your go-bag by the door and stay off low streets.';
        }
        if ($w['code'] >= 51) {
            return "It's raining now. Skip the shortcuts by the creek.";
        }
        if ($start = self::rainStartsAt($w)) {
            return 'Rain from '.$start->format('g:i A').'. Cross low streets before then, not after.';
        }
        foreach (array_slice($w['hourly'], 1) as $h) {
            if (($h['chance'] ?? 0) >= 50) {
                return 'Rain likely around '.Carbon::parse($h['time'])->format('g A').'. Bring the umbrella, not the excuses.';
            }
        }

        return $w['is_day'] ? "Dry roads for now. Go run your errands, I'll hold the fort."
            : "No rain tonight. Charge your phone and sleep. I've got the watch.";
    }

    public const TIPS = [
        'Save the CDRRMO number now. During a flood is too late.',
        "See a flooded street? Post one photo in Reports. That's all I need.",
        'Pack the go-bag before the rain does. The checklist is in Help.',
        "Safe after a storm? Send \"I'm safe\" from Help so nobody has to guess.",
    ];

    /** weatherInfo() label in the app. */
    public static function describe(int $code, bool $isDay = true): string
    {
        return match (true) {
            $code === 0 => $isDay ? 'Clear sky' : 'Clear night',
            $code <= 2 => 'Partly cloudy',
            $code === 3 => 'Overcast',
            $code <= 48 => 'Foggy',
            $code <= 57 => 'Drizzle',
            $code <= 67 => 'Rain',
            $code <= 77 => 'Snow',
            $code <= 82 => 'Rain showers',
            default => 'Thunderstorm',
        };
    }

    /** tempColor() in the app: Apple Weather's range-bar colors. */
    public static function tempColor(float $c): string
    {
        $stops = [[22, [0x40, 0xC8, 0xFF]], [27, [0xFF, 0xD6, 0x0A]], [31, [0xFF, 0x9F, 0x0A]], [35, [0xFF, 0x64, 0x82]]];
        $rgb = $stops[3][1];
        if ($c <= 22) {
            $rgb = $stops[0][1];
        } else {
            for ($i = 1; $i < 4; $i++) {
                if ($c <= $stops[$i][0]) {
                    $f = ($c - $stops[$i - 1][0]) / ($stops[$i][0] - $stops[$i - 1][0]);
                    $rgb = array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $f), $stops[$i - 1][1], $stops[$i][1]);
                    break;
                }
            }
        }

        return sprintf('#%02x%02x%02x', ...$rgb);
    }

    /**
     * WeatherGlyph (lib/widgets/weather_glyph.dart) as inline SVG, in its
     * resting pose: yellow sun, white clouds, blue drops, a yellow bolt.
     */
    public static function glyph(int $code, bool $isDay = true, int $size = 36): string
    {
        static $n = 0;
        $id = 'g'.(++$n);
        $kind = match (true) {
            $code === 0 => 'clear', $code <= 2 => 'partly', $code === 3 => 'cloudy', $code <= 48 => 'fog',
            $code <= 57 => 'drizzle', $code <= 82 => 'rain', default => 'storm',
        };
        $sun = function (float $cx, float $cy, float $r) use ($id) {
            $s = '';
            for ($i = 0; $i < 8; $i++) {
                $a = $i * M_PI / 4;
                $s .= sprintf('<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f"/>', $cx + cos($a) * ($r + 1.6), $cy + sin($a) * ($r + 1.6), $cx + cos($a) * ($r + 3.4), $cy + sin($a) * ($r + 3.4));
            }

            return '<g stroke="#FFB627" stroke-width="1.7" stroke-linecap="round">'.$s.'</g>'
                ."<circle cx=\"$cx\" cy=\"$cy\" r=\"".($r + 1.2).'" fill="#FFC233" fill-opacity=".2"/>'
                ."<circle cx=\"$cx\" cy=\"$cy\" r=\"$r\" fill=\"url(#{$id}s)\"/>";
        };
        $moon = fn (float $cx, float $cy, float $r) => "<mask id=\"{$id}x\"><circle cx=\"$cx\" cy=\"$cy\" r=\"$r\" fill=\"#fff\"/>"
            .'<circle cx="'.($cx + $r * .55).'" cy="'.($cy - $r * .4).'" r="'.($r * .85).'" fill="#000"/></mask>'
            ."<circle cx=\"$cx\" cy=\"$cy\" r=\"$r\" fill=\"url(#{$id}n)\" mask=\"url(#{$id}x)\"/>";
        $cloud = function (float $cx, float $cy, float $k, string $tone = 'w') use ($id) {
            $shape = '<circle cx="-4.2" cy="1.2" r="3.6"/><circle cx="0.6" cy="-1.4" r="5"/><circle cx="5" cy="1.4" r="3.4"/>'
                .'<rect x="-7.8" y="0.8" width="16.2" height="4" rx="2"/>';

            return "<g transform=\"translate($cx $cy) scale($k)\"><g fill=\"#000\" fill-opacity=\".2\" transform=\"translate(0 .7)\">$shape</g>"
                ."<g fill=\"url(#{$id}$tone)\">$shape</g></g>";
        };
        $rain = function (int $drops, bool $long) use ($id) {
            $xs = $drops == 2 ? [9.0, 14.5] : [7.5, 12.0, 16.5];
            $s = '';
            foreach ($xs as $i => $x) {
                $phase = fmod($i * 0.37, 1);
                $y = 13.5 + $phase * 8.5;
                $len = $long ? 3.4 : 1.8;
                $s .= sprintf('<line x1="%.2f" y1="%.2f" x2="%.2f" y2="%.2f" stroke-opacity="%.2f"/>', $x + .8, $y, $x - .2, $y + $len, 1 - $phase);
            }

            return "<g stroke=\"url(#{$id}d)\" stroke-width=\"2.1\" stroke-linecap=\"round\">$s</g>";
        };
        $body = match ($kind) {
            'clear' => $isDay ? $sun(12, 12, 5.2) : $moon(12, 12, 7),
            'partly' => ($isDay ? $sun(15.5, 8.5, 4.2) : $moon(16, 8, 5)).$cloud(10.5, 15.5, .92),
            'cloudy' => $cloud(15.5, 9.5, .62, 'm').$cloud(11, 14.5, 1),
            'fog' => $cloud(12, 10.5, .9).'<g stroke="#B8C2D1" stroke-width="1.6" stroke-linecap="round"><line x1="4.5" y1="18" x2="17" y2="18"/><line x1="7" y1="21" x2="19.5" y2="21"/></g>',
            'drizzle' => $rain(2, false).$cloud(12, 9.5, 1),
            'rain' => $rain(3, true).$cloud(12, 9.5, 1, 'k'),
            default => $rain(2, true).$cloud(12, 9.5, 1, 'k').'<path d="M12.6 12.2 9.4 17.6h2.4l-1.2 4.8 4.4-6.4h-2.5l1.5-3.8z" fill="#FFD60A"/>',
        };
        $defs = "<defs><radialGradient id=\"{$id}s\" cx=\"32%\" cy=\"30%\" r=\"70%\"><stop offset=\"0\" stop-color=\"#FFE066\"/><stop offset=\"1\" stop-color=\"#FFA51F\"/></radialGradient>"
            ."<linearGradient id=\"{$id}n\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"1\"><stop offset=\"0\" stop-color=\"#FFF4C2\"/><stop offset=\"1\" stop-color=\"#E3C96A\"/></linearGradient>"
            ."<linearGradient id=\"{$id}w\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"#fff\"/><stop offset=\"1\" stop-color=\"#D3DBE6\"/></linearGradient>"
            ."<linearGradient id=\"{$id}m\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"#C9D1DC\"/><stop offset=\"1\" stop-color=\"#9AA6B6\"/></linearGradient>"
            ."<linearGradient id=\"{$id}k\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"#D6DCE6\"/><stop offset=\"1\" stop-color=\"#8C97A8\"/></linearGradient>"
            ."<linearGradient id=\"{$id}d\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"#7FD4FF\"/><stop offset=\"1\" stop-color=\"#2E8BFF\"/></linearGradient></defs>";

        return "<svg class=\"glyph\" width=\"$size\" height=\"$size\" viewBox=\"0 0 24 24\" aria-hidden=\"true\">$defs$body</svg>";
    }
}
