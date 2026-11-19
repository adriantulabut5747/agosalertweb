@extends('layouts.app')
@section('title', 'Weather')

@use('App\Services\Weather')
@use('App\Support\AgosData')
@use('Carbon\Carbon')

{{-- The Flutter Weather tab (lib/screens/weather_tab.dart +
     weather_dashboard.dart), same order and same numbers:
     desktop = Tugak's stage beside greeting + 5 days, then On the map now,
     Right now, Rain 48 hours; phones = greeting, stage, 5 days, Right now,
     Rain, On the map now. --}}
@section('content')
    @php
        $h = (int) now()->format('G');
        [$hello, $kap] = match (true) {
            $h >= 5 && $h < 11 => ['Good morning', 'Mayap a abak'],
            $h >= 11 && $h < 13 => ['Good noon', 'Mayap a ugtu'],
            $h >= 13 && $h < 18 => ['Good afternoon', 'Mayap a gatpanapun'],
            default => ['Good evening', 'Mayap a bengi'],
        };
        if ($w) {
            $hr = $w['hourly'][0];
            $line = Weather::tugakLine($w, $alert);
            $serious = $alert !== null || Weather::face($w, $alert) === 'alert';
            $lo = min(array_column($w['days'], 'min'));
            $hi = max(array_column($w['days'], 'max'));
            $at = fn ($t) => $hi - $lo < 0.5 ? 0.5 : ($t - $lo) / ($hi - $lo);
            $raining = Weather::rainStartsAt($w) !== null;
            $scene = ['code' => $w['code'], 'isDay' => $w['is_day'], 'hour' => now()->hour + now()->minute / 60,
                'cloud' => $w['clouds'], 'rain' => $hr['rain'], 'wind' => $w['wind'], 'mood' => Weather::face($w, $alert)];
            // Right now (ConditionsGrid): the same 8 cells, numbers and lines.
            $compass = fn ($deg) => ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'][(int) round(($deg % 360) / 45) % 8];
            $compassName = fn ($deg) => ['north', 'northeast', 'east', 'southeast', 'south', 'southwest', 'west', 'northwest'][(int) round(($deg % 360) / 45) % 8];
            $feels = $w['feels'];
            $uv = (int) round($w['uv']);
            $km = $w['visibility'] / 1000;
            $later = $w['hourly'][3]['pressure'] ?? null;
            $dp = $later === null ? 0 : $later - $w['pressure'];
            $today = $w['days'][0];
            $tomorrow = $w['days'][1] ?? null;
            $rise = Carbon::parse($today['sunrise']);
            $set = Carbon::parse($today['sunset']);
            [$sunLabel, $sunAt] = now()->lt($rise) ? ['Sunrise', $rise] : (now()->lt($set) || ! $tomorrow ? ['Sunset', $set] : ['Sunrise', Carbon::parse($tomorrow['sunrise'])]);
            $daylight = $rise->diffInMinutes($set);
            $air = $w['air'];
            $aqiLabel = fn ($a) => match (true) {
                $a <= 50 => 'Good', $a <= 100 => 'Moderate', $a <= 150 => 'Unhealthy for sensitive groups',
                $a <= 200 => 'Unhealthy', $a <= 300 => 'Very unhealthy', default => 'Hazardous',
            };
            $cells = [
                ['wind', 'Wind', round($w['wind']), ' km/h', 'From '.$compass($w['wind_dir']).' · gusts '.round($w['gusts']),
                    'Wind from the '.$compassName($w['wind_dir']).'. PAGASA raises Wind Signal No. 1 at 39-61 km/h: loose roofing and branches start to fly.'],
                ['thermometer', 'Feels like', round($feels).'°', '', match (true) {
                    $feels >= 52 => 'Extreme danger', $feels >= 42 => 'Danger', $feels >= 33 => 'Extreme caution', $feels >= 27 => 'Caution', default => 'No heat risk',
                }.' · actual '.round($w['temp']).'°', 'Heat index: how hot humid air feels. PAGASA: 33-41° extreme caution, 42° and up danger. Drink water, rest in shade.'],
                ['drop', 'Humidity', $w['humidity'].'%', '', $w['humidity'] >= 80 ? 'Very humid' : ($w['humidity'] >= 60 ? 'Humid' : 'Comfortable'),
                    'How much water the air holds. Humid air makes heat feel worse and slows wet clothes and floors from drying.'],
                ['sun_max', 'UV index', $uv, '', match (true) { $uv >= 11 => 'Extreme', $uv >= 8 => 'Very high', $uv >= 6 => 'High', $uv >= 3 => 'Moderate', default => 'Low' },
                    'Sunburn strength (WHO scale). 3 and up: sunscreen and a hat. 8 and up: stay in the shade around midday.'],
                ['smoke', 'Air quality', $air ? $air['aqi'] : '–', '', $air ? $aqiLabel($air['aqi']).' · PM2.5 '.number_format($air['pm25'], 1) : 'Unavailable right now',
                    'US EPA index from Open-Meteo model data, not a local sensor. 0-50 good, 51-100 moderate, over 100 unhealthy for sensitive groups.'],
                ['eye', 'Visibility', $km >= 10 ? round($km) : number_format($km, 1), ' km', $km < 1 ? 'Fog' : ($km < 5 ? 'Mist or haze' : 'Clear view'),
                    'How far you can see clearly. Under 1 km counts as fog (WMO): drive slowly with your lights on.'],
                ['gauge', 'Pressure', round($w['pressure']), ' hPa', $later === null ? 'Sea level' : ($dp >= 1 ? 'Rising next 3 h' : ($dp <= -1 ? 'Falling next 3 h' : 'Steady next 3 h')),
                    'Air pressure at sea level. A steady fall over a few hours often comes before a storm or typhoon.'],
                [$sunLabel === 'Sunrise' ? 'sunrise' : 'sunset', $sunLabel, $sunAt->format('g:i A'), '', 'Daylight '.intdiv($daylight, 60).' h '.($daylight % 60).' m',
                    'If heavy rain is coming, travel in daylight: flood water on roads is much harder to judge in the dark.'],
            ];
            // Rain, 48 hours (RainTotalsCard).
            $bars = array_merge($w['past_rain'], array_map(fn ($x) => [$x['time'], $x['rain']], $w['hourly']));
            $nowAt = count($w['past_rain']);
            $pastMm = array_sum(array_column($w['past_rain'], 1));
            $nextMm = array_sum(array_column($w['hourly'], 'rain'));
            $scale = max(2.0, max(array_column($bars, 1) ?: [0]));
        }
        $myBarangay = $me?->barangay;
    @endphp

    @php
        $greeting = view()->make('partials.greeting', compact('hello', 'kap', 'myBarangay'))->render();
    @endphp

    <div class="wx">
        @if ($w)
            {{-- Tugak's stage = the "now" card (_Terrace + _NowReadout). --}}
            <div class="stage glass-edge" id="stage" data-scene='@json($scene)' data-line="{{ $line }}"
                 data-tips='@json($serious ? [] : Weather::TIPS)' aria-label="Tugak says: {{ $line }}">
                <img class="still" src="{{ asset('img/tugak/hero.webp') }}" alt="">
                <div class="scene" id="scene"></div>
                <div class="say fallback" hidden><div class="who">TUGAK</div><p>{{ $line }}</p></div>
                <div class="readout">
                    <div class="row live"><span class="dot live-dot"></span><span class="grow">MABALACAT CITY · LIVE</span><span class="clock">{{ now()->format('g:i A') }}</span></div>
                    <div class="head">
                        <div class="temp">{{ round($w['temp']) }}°</div>
                        <div class="grow">
                            <div class="cond">{{ Weather::describe($w['code'], $w['is_day']) }}</div>
                            <div class="hl">H {{ round($today['max']) }}°&nbsp; · &nbsp;L {{ round($today['min']) }}°&nbsp; · &nbsp;Feels {{ round($w['feels']) }}°</div>
                            <div class="next"><i class="i" data-i="{{ $raining ? 'cloud_rain_fill' : 'checkmark_alt' }}"></i>{{ Weather::nextTwoHoursText($w) }}</div>
                        </div>
                    </div>
                    <div class="stats">
                        <div><i class="i" data-i="umbrella_fill"></i><b>{{ $hr['chance'] }}%</b><span>Rain chance</span></div>
                        <div><i class="i" data-i="drop_fill"></i><b>{{ number_format($hr['rain'], 1) }} mm</b><span>Rain this hour</span></div>
                        <div><i class="i" data-i="cloud"></i><b>{{ $w['clouds'] }}%</b><span>Cloud cover</span></div>
                    </div>
                </div>
            </div>

            <div class="side">
                {!! $greeting !!}
                <div class="card days">
                    <div class="section"><h2>Next {{ count($w['days']) }} days</h2></div>
                    @foreach ($w['days'] as $d)
                        <div class="d">
                            <span class="name">{{ $loop->first ? 'Today' : Carbon::parse($d['date'])->format('D') }}</span>
                            <span title="{{ Weather::describe($d['code']) }}">{!! Weather::glyph($d['code']) !!}</span>
                            <span class="pct">{{ ($d['chance'] ?? 0) >= 20 ? $d['chance'].'%' : '' }}</span>
                            <span class="lo">{{ round($d['min']) }}°</span>
                            <span class="track">
                                <i style="left:{{ $at($d['min']) * 100 }}%;right:{{ 100 - $at($d['max']) * 100 }}%;background:linear-gradient(90deg,{{ Weather::tempColor($d['min']) }},{{ Weather::tempColor($d['max']) }})"></i>
                                @if ($loop->first)<b style="left:{{ max(0, min(1, $at($w['temp']))) * 100 }}%"></b>@endif
                            </span>
                            <span class="hi">{{ round($d['max']) }}°</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="side">{!! $greeting !!}</div>
            <div class="card row" style="grid-column:1/-1">
                <span class="tile gray"><i class="i" data-i="wifi_slash"></i></span>
                <div class="grow"><b>Weather unavailable</b><div class="small muted">Open-Meteo can't be reached just now.</div></div>
                <a class="btn tint sm" href="{{ route('home') }}">Retry</a>
            </div>
        @endif

        {{-- On the map now (FloodOpsCard): newest first, 3 rows per column. --}}
        <div class="card ops-card {{ $reports->count() <= 6 ? 'fits' : '' }} {{ $reports->count() > 3 ? 'more3' : '' }}">
            <div class="section"><h2>On the map now</h2><a class="more" href="{{ route('map') }}">Open map <i class="i" data-i="chevron_right"></i></a></div>
            @if ($reports->isEmpty())
                <p class="muted" style="margin:0">No flood reports right now. Posted reports show here for 14 days.</p>
            @else
                <p class="small muted" style="margin:-4px 0 14px">{{ $reports->count() }} active {{ Str::plural('report', $reports->count()) }} in {{ $reports->pluck('barangay')->unique()->count() }} {{ Str::plural('barangay', $reports->pluck('barangay')->unique()->count()) }}, from people nearby</p>
                <div class="ops">
                    @foreach ($reports->take(6) as $r)
                        @php [$f, $ic] = AgosData::typeIcon($r->incident_type); @endphp
                        <a class="op" href="{{ route('map') }}#report-{{ $r->report_id }}">
                            <span class="tile"><i class="{{ $f }}" data-i="{{ $ic }}"></i></span>
                            <div class="grow">
                                <b>{{ ucfirst($r->incident_type) }} · {{ $r->barangay }}</b>
                                <span class="small"><span class="dot {{ $r->risk() }}"></span>
                                    @if ($r->depth_cm)
                                        {{ $r->depth_cm }} cm, {{ AgosData::depthLabel($r->depth_cm) }} ·
                                    @endif
                                    @if ($r->status === 'verified')
                                        Verified ·
                                    @endif
                                    {{ $r->created_at->shortRelativeDiffForHumans() }}
                                </span>
                            </div>
                            <i class="i" data-i="chevron_right"></i>
                        </a>
                    @endforeach
                </div>
                @if ($reports->count() > 3)
                    <a class="small seeall" href="{{ route('reports.index') }}">See all {{ $reports->count() }} in Reports</a>
                @endif
            @endif
        </div>

        @if ($w)
            {{-- Right now (ConditionsGrid): one card, hairlines, tap = what it means. --}}
            <div class="conditions">
                <h2 class="block-title">Right now</h2>
                <div class="card cells">
                    @foreach ($cells as [$icon, $label, $value, $unit, $sub, $info])
                        <button type="button" class="cell" data-info="{{ $info }}" data-head="{{ $value }}{{ $unit }} · {{ $sub }}" data-label="{{ $label }}" data-icon="{{ $icon }}">
                            <span class="lbl"><i class="i" data-i="{{ $icon }}"></i>{{ $label }}</span>
                            <span class="val">{{ $value }}<small>{{ $unit }}</small></span>
                            <span class="sub">{{ $sub }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Rain, 48 hours (RainTotalsCard). --}}
            @if ($w['past_rain'])
                <div class="card rain48" id="rain48" data-bars='@json($bars)' data-now="{{ $nowAt }}" data-today="{{ now()->toDateString() }}">
                    <div class="section"><h2>Rain, 48 hours</h2></div>
                    <div class="totals">
                        @foreach ([['LAST 24 H', $pastMm], ['NEXT 24 H', $nextMm]] as [$lbl, $mm])
                            <div>
                                <div class="cap">{{ $lbl }}</div>
                                <div class="mm">{{ $mm < 0.1 ? '0' : number_format($mm, 1) }}<small> mm</small></div>
                                <div class="meter"><i style="width:{{ max(2, min(100, $mm / 30 * 100)) }}%;background:{{ $mm >= 30 ? 'var(--danger)' : ($mm >= 10 ? 'var(--caution)' : 'var(--blue)') }}"></i></div>
                                <div class="tiny muted">Bar ends at 30 mm: high risk</div>
                            </div>
                        @endforeach
                    </div>
                    <div class="readout-line" id="rain-read">Drag across the bars to read any hour</div>
                    <div class="bars" id="rain-bars">
                        <span class="scale">{{ number_format($scale, $scale < 10 ? 1 : 0) }} mm/h</span>
                        @foreach ($bars as $i => [$t, $mm])
                            <i class="{{ $i < $nowAt ? 'past' : ($mm >= 0.1 ? 'wet' : 'dry') }}" style="height:{{ max(2, 82 * min(1, $mm / $scale)) }}px"></i>
                        @endforeach
                        <b class="nowline" style="left:{{ $nowAt / count($bars) * 100 }}%"></b>
                    </div>
                    <div class="axis"><span>24 h ago</span><span>12 h ago</span><span class="on">Now</span><span>+12 h</span><span>+24 h</span></div>
                    <p class="tiny muted" style="margin:10px 0 0">Past hours are Open-Meteo model estimates, not a rain gauge. 30 mm in a day is the line our flood outlook calls high risk.</p>
                </div>
            @endif
        @endif
    </div>
    <p class="wx-foot">Weather by Open-Meteo · Unofficial demo</p>

    {{-- The explain sheet for a Right now cell (showAppSheet). --}}
    <dialog class="sheet info-sheet" id="cell-sheet">
        <div class="lblrow"><i class="i" id="cs-icon"></i><span id="cs-label"></span></div>
        <h3 id="cs-head"></h3>
        <p id="cs-info"></p>
        <button class="btn gray wide" type="button" onclick="this.closest('dialog').close()">Done</button>
    </dialog>
@endsection

@push('scripts')
    <script>
        // Right now: tap a cell = what the number means.
        document.querySelectorAll('.cell').forEach(function (b) {
            b.addEventListener('click', function () {
                var d = document.getElementById('cell-sheet');
                d.querySelector('#cs-icon').dataset.i = b.dataset.icon;
                d.querySelector('#cs-label').textContent = b.dataset.label;
                d.querySelector('#cs-head').textContent = b.dataset.head;
                d.querySelector('#cs-info').textContent = b.dataset.info;
                d.showModal();
            });
        });

        // Rain, 48 hours: hover / drag across the bars to read any hour.
        (function () {
            var card = document.getElementById('rain48');
            if (!card) return;
            var bars = JSON.parse(card.dataset.bars), nowAt = +card.dataset.now, today = card.dataset.today;
            var box = document.getElementById('rain-bars'), read = document.getElementById('rain-read'), cols = box.querySelectorAll('i'), sel = -1;
            function hour(t) { var h = +t.slice(11, 13); return (h % 12 || 12) + ' ' + (h < 12 ? 'AM' : 'PM'); }
            function at(x) {
                var r = box.getBoundingClientRect(), i = Math.max(0, Math.min(bars.length - 1, Math.floor((x - r.left) / r.width * bars.length)));
                if (i === sel) return;
                if (sel >= 0) cols[sel].classList.remove('sel');
                sel = i;
                cols[i].classList.add('sel');
                var t = bars[i][0], day = t.slice(0, 10) === today ? 'Today' : t.slice(0, 10) < today ? 'Yesterday' : 'Tomorrow';
                read.textContent = day + ' ' + hour(t) + ' · ' + bars[i][1].toFixed(1) + ' mm' + (i < nowAt ? '' : ' forecast');
                read.classList.add('on');
            }
            function off() {
                if (sel >= 0) cols[sel].classList.remove('sel');
                sel = -1;
                read.textContent = 'Drag across the bars to read any hour';
                read.classList.remove('on');
            }
            box.addEventListener('pointermove', function (e) { at(e.clientX); });
            box.addEventListener('pointerdown', function (e) { at(e.clientX); });
            box.addEventListener('pointerleave', off);
            box.addEventListener('pointerup', function (e) { if (e.pointerType !== 'mouse') off(); });
        })();

        // Tugak's live 3D stage: the Flutter app's own scene (web/tugak/scene.js),
        // fed the same weather, line and framing (StageReserve) as weather_tab.dart.
        (function () {
            var stage = document.getElementById('stage');
            if (!stage) return;
            var host = document.getElementById('scene'), handle = null, tip = -1;
            var tips = JSON.parse(stage.dataset.tips), line = stage.dataset.line;
            function reserve() {
                var w = stage.clientWidth;
                return w < 560 ? { left: 140, top: 92, bottom: 78, bubble: 'below', zoom: 1.15 }
                    : { left: Math.max(300, Math.min(420, w * 0.5)), top: 0, bottom: 0, bubble: 'side', zoom: 1 };
            }
            function place() {
                var r = reserve();
                stage.classList.toggle('below', r.bubble === 'below');
                stage.style.setProperty('--reserve', r.left + 'px');
                if (handle) handle.setReserve(JSON.stringify(r));
            }
            place();
            new ResizeObserver(place).observe(stage);
            function failed() {
                stage.classList.add('failed');
                stage.querySelector('.fallback').hidden = false;
            }
            var s = document.createElement('script');
            s.type = 'module';
            s.src = '{{ asset('app/tugak/scene.js') }}';
            s.onerror = failed;
            s.onload = function () {
                try {
                    handle = window.agosTugak.create(host, JSON.stringify({ dark: true, weather: JSON.parse(stage.dataset.scene), reserve: reserve() }), {
                        onReady: function () { stage.classList.add('ready'); },
                        onError: failed,
                    });
                    handle.say(line);
                    handle.setActive(true, true);
                    document.addEventListener('visibilitychange', function () { handle.setActive(true, !document.hidden); });
                } catch (e) { failed(); }
            };
            document.head.appendChild(s);
            function norm(e) {
                var r = stage.getBoundingClientRect();
                return [(e.clientX - r.left) / r.width * 2 - 1, -((e.clientY - r.top) / r.height * 2 - 1)];
            }
            stage.addEventListener('pointermove', function (e) { if (handle) handle.pointer.apply(null, norm(e)); });
            stage.addEventListener('pointerleave', function () { if (handle) handle.pointer(0, 0); });
            stage.addEventListener('click', function (e) {
                if (!handle || !stage.classList.contains('ready')) return;
                // Tap Tugak: a tip (never on a high alert or storm: the instruction stays).
                if (handle.tap.apply(null, norm(e)) === 'tugak' && tips.length) {
                    tip = (tip + 1) % tips.length;
                    handle.say(tips[tip]);
                }
            });
        })();
    </script>
@endpush
