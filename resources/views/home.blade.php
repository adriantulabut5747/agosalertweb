@extends('layouts.app')
@section('title', 'Weather')

@use('App\Services\Weather')
@use('App\Support\AgosData')

@section('content')
    @php
        $h = (int) now()->format('G');
        [$hello, $kap] = match (true) {
            $h >= 5 && $h < 11 => ['Good morning', 'Mayap a abak'],
            $h >= 11 && $h < 13 => ['Good noon', 'Mayap a ugtu'],
            $h >= 13 && $h < 18 => ['Good afternoon', 'Mayap a gatpanapun'],
            default => ['Good evening', 'Mayap a bengi'],
        };
        // Tugak's line from the real forecast (deadpan; no joke on high risk).
        $line = ! $w ? "I can't reach the weather right now. Check again in a minute."
            : ($outlook[0] === 'high' ? 'Heavy rain today. Move valuables up and charge your phone now.'
            : (($w['days'][0]['chance'] ?? 0) >= 60 ? 'Rain likely today. Cross low streets early, not after.'
            : 'Dry for now. Good day to check your go-bag, not the sky.'));
        if ($w) {
            $lo = min(array_column($w['days'], 'min'));
            $hi = max(array_column($w['days'], 'max'));
            $span = max(1, $hi - $lo);
        }
    @endphp

    @if ($alert)
        <a class="card row" href="{{ route('alerts') }}" style="margin-bottom:18px;border-color:rgba(255,69,58,.5);background:rgba(255,69,58,.1);color:inherit">
            <span class="tile red"><i class="i" data-i="exclamationmark_triangle_fill"></i></span>
            <div class="grow"><b>{{ $alert->title }}</b><div class="small muted">{{ $alert->area }} · {{ $alert->summary }}</div></div>
            <i class="i muted" data-i="chevron_right"></i>
        </a>
    @endif

    <div class="wx">
        {{-- The "now" card: Tugak's navy stage with the live readings on it. --}}
        <div class="card now">
            @if ($w)
                <div class="row">
                    <span class="live"><span class="dot"></span>MABALACAT CITY · LIVE</span>
                    <span class="small" style="margin-left:auto;color:#aab0c4">{{ \Carbon\Carbon::parse($w['time'])->format('g:i A') }}</span>
                </div>
                <div class="head">
                    <div class="temp">{{ round($w['temp']) }}°</div>
                    <div>
                        <div class="cond">{{ Weather::describe($w['code']) }}</div>
                        <div class="hl">H {{ round($w['days'][0]['max']) }}° · L {{ round($w['days'][0]['min']) }}° · Feels {{ round($w['feels']) }}°</div>
                        <div class="outlook">
                            <span class="dot {{ $outlook[0] }}"></span>{{ $outlook[1] }}
                        </div>
                    </div>
                </div>
                <div class="stats">
                    <div><i class="i" data-i="umbrella_fill"></i><b>{{ $w['days'][0]['chance'] ?? 0 }}%</b><span>Rain chance</span></div>
                    <div><i class="i" data-i="drop_fill"></i><b>{{ $w['days'][0]['rain'] }} mm</b><span>Rain today</span></div>
                    <div><i class="i" data-i="cloud_fill"></i><b>{{ $w['clouds'] }}%</b><span>Cloud cover</span></div>
                </div>
            @else
                <span class="live"><span class="dot" style="background:var(--gray)"></span>MABALACAT CITY</span>
                <h3 style="margin:14px 0 4px">Weather unavailable</h3>
                <p class="muted" style="margin:0">Open-Meteo can't be reached just now.</p>
            @endif
            <img class="tugak" src="{{ asset('img/tugak/hero.webp') }}" alt="Tugak, AgosAlert's toad">
            <div class="say"><div class="who">TUGAK</div><p>{{ $line }}</p></div>
        </div>

        <div class="greet">
            <div class="eyebrow date">{{ strtoupper(now()->format('D, M j')) }}</div>
            <div class="hello">{{ $hello }}</div>
            <div class="kap">{{ $kap }}</div>
        </div>
        <div class="fivedays">
            @if ($w)
                <div class="card days">
                    <div class="section" style="margin-bottom:4px"><h2>Next 5 days</h2></div>
                    @foreach ($w['days'] as $d)
                        @php [$gi, $gc] = Weather::glyph($d['code']); @endphp
                        <div class="d">
                            <span>{{ $loop->first ? 'Today' : \Carbon\Carbon::parse($d['date'])->format('D') }}</span>
                            <i class="i glyph" data-i="{{ $gi }}" style="color:{{ $gc }}"></i>
                            <span class="pct">{{ $d['chance'] ?? 0 }}%</span>
                            <span class="lo">{{ round($d['min']) }}°</span>
                            <span class="track"><i style="left:{{ ($d['min'] - $lo) / $span * 100 }}%;right:{{ 100 - ($d['max'] - $lo) / $span * 100 }}%"></i></span>
                            <span class="hi">{{ round($d['max']) }}°</span>
                        </div>
                    @endforeach
                    <p class="tiny muted" style="margin:10px 0 0">Flood outlook: {{ $outlook[2] }} An estimate, not an official warning.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- On the map now (FloodOpsCard): newest first, 3 rows max per column. --}}
    <div class="card" style="margin-top:20px">
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
                                @if ($r->depth_cm !== null){{ $r->depth_cm }} cm, {{ AgosData::depthLabel($r->depth_cm) }} · @endif
                                @if ($r->status === 'verified')Verified · @endif
                                {{ $r->created_at->shortRelativeDiffForHumans() }}
                            </span>
                        </div>
                        <i class="i" data-i="chevron_right"></i>
                    </a>
                @endforeach
            </div>
            @if ($reports->count() > 6)
                <a class="small" style="display:inline-block;margin-top:12px" href="{{ route('reports.index') }}">See all {{ $reports->count() }} in Reports</a>
            @endif
        @endif
    </div>
@endsection
