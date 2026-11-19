@extends('layouts.app')
@section('title', 'Map')
@section('main_class', 'full')
@section('body_class', 'map-page')

@use('App\Support\AgosData')

{{-- The Flutter Map tab (lib/screens/map_tab.dart): the map fills the
     window under the glass top bar. Desktop: a glass side panel (search,
     layer chips, deepest first, centers, Report flooding), the controls
     capsule top right, the depth legend bottom left. Phones / tablets: the
     search on top, the list in a bottom sheet. --}}
@push('head')
    {{-- The app's map script loads its files relative to the page
         (map/..., assets/...): they live in public/agos/. --}}
    <base href="{{ asset('agos') }}/">
@endpush

@section('content')
    @php $openCount = collect($centers)->where('status', 'open')->count(); @endphp
    <div id="map"></div>

    <aside class="mp-panel" id="panel">
        <button class="mp-peek" type="button" id="peek" aria-expanded="false">
            <span class="grip"></span>
            <span class="row"><span class="dot live-dot" style="background:var(--danger);box-shadow:0 0 0 3px rgba(255,69,58,.2)"></span>
                <b class="grow">{{ count($reports) }} reports · {{ $openCount }} shelters</b>
                @if ($sample)<span class="sample">SAMPLE</span>@endif<i class="i chev" data-i="chevron_up"></i></span>
        </button>
        <div class="mp-head">
            <div class="row"><h2>Flood map</h2>@if ($sample)<span class="sample">SAMPLE</span>@endif</div>
            <p>Reports from residents · Mabalacat City</p>
        </div>
        <div class="mp-search">
            <i class="i" data-i="search"></i>
            <input type="search" class="brgy" list="brgys" placeholder="Search a barangay" autocomplete="off" aria-label="Search a barangay">
            <datalist id="brgys">@foreach (AgosData::BARANGAY_POINTS as $name => $pt)<option value="{{ $name }}">@endforeach</datalist>
        </div>
        <div class="mp-chips">
            <button type="button" class="selchip on" data-layer="flood"><i class="ai" data-i="flood"></i>Reports · {{ count($reports) }}</button>
            <button type="button" class="selchip on" data-layer="shelter"><i class="i" data-i="house_alt_fill"></i>Shelters · {{ $openCount }}/{{ count($centers) }}</button>
        </div>
        <div class="mp-list">
            <div class="fieldlabel">Deepest first</div>
            @forelse ($reports as $r)
                <button type="button" class="mp-row" data-pin="report-{{ $r['id'] }}">
                    <span class="lead"><b class="risk-{{ $r['risk'] }}">{{ $r['depth'] ?? '–' }}</b>@if ($r['depth'] !== null)<small> cm</small>@endif</span>
                    <span class="grow"><b>Brgy. {{ $r['barangay'] }}</b><span>{{ $r['level'] ?? $r['type'] }} · {{ $r['updated'] }}</span></span>
                    <i class="i" data-i="chevron_right"></i>
                </button>
            @empty
                <p class="small muted" style="margin:4px 10px 10px">No flood reports right now.</p>
            @endforelse
            <div class="fieldlabel" style="margin-top:10px">Evacuation centers</div>
            @foreach ($centers as $i => $c)
                <button type="button" class="mp-row" data-pin="center-{{ $i }}">
                    <span class="lead"><span class="sq {{ $c['status'] === 'open' ? 'open' : '' }}"><i class="i" data-i="{{ $c['icon'] }}"></i></span></span>
                    <span class="grow"><b>{{ $c['name'] }}</b><span>{{ ucfirst($c['status']) }}@if ($c['distance']) · {{ $c['distance'] }} away @endif</span></span>
                    <i class="i" data-i="chevron_right"></i>
                </button>
            @endforeach
        </div>
        @if ($me)
            <a class="btn white wide mp-cta" href="{{ route('reports.create') }}">Report flooding <i class="i" data-i="plus"></i></a>
        @else
            {{-- Guests: the same button; it asks them to log in (showLoginRequired). --}}
            <a class="btn white wide mp-cta" href="{{ route('login') }}">Report flooding <i class="i" data-i="plus"></i></a>
        @endif
    </aside>

    {{-- Phones / tablets: the same search, floating on top of the map (outside
         the panel: its blur would trap a fixed element inside it). --}}
    <div class="mp-search top">
        <i class="i" data-i="search"></i>
        <input type="search" class="brgy" list="brgys" placeholder="Search a barangay" autocomplete="off" aria-label="Search a barangay">
    </div>

    {{-- iPhone Maps style controls: one glass capsule. --}}
    <div class="mp-ctrls">
        <button type="button" id="b3d" title="3D view">3D</button>
        <button type="button" id="bhome" title="Back to the city"><i class="i" data-i="location_fill"></i></button>
        <button type="button" class="zoom" id="bin" title="Zoom in"><i class="i" data-i="plus"></i></button>
        <button type="button" class="zoom" id="bout" title="Zoom out"><i class="i" data-i="minus"></i></button>
    </div>

    <div class="mp-legend">
        <div class="cap">STREET FLOOD DEPTH</div>
        <div class="steps"><div><i style="background:var(--safe)"></i>≤ 20 cm</div><div><i style="background:var(--caution)"></i>21–50</div><div><i style="background:var(--danger)"></i>50+ cm</div></div>
    </div>

    <dialog class="sheet pin-sheet" id="pinSheet"><div id="pinBody"></div></dialog>
@endsection

@push('scripts')
    <script src="{{ asset('agos/agos_map.js') }}"></script>
    <script>
        const reports = @json($reports);
        const centers = @json($centers);
        const points = @json(AgosData::BARANGAY_POINTS);
        const colors = { normal: '#30D158', moderate: '#FF9F0A', high: '#FF453A' };
        const riskWord = { normal: 'Normal', moderate: 'Moderate', high: 'High' };
        const guidance = {
            high: 'Move to higher ground now and avoid this area until the water goes down.',
            moderate: 'Stay alert and keep checking for updates. Avoid unnecessary travel here.',
            normal: 'Conditions are normal. No action needed right now.',
        };
        // Same icons as the app's pins (kIncidentTypes): [code point, font].
        const glyphs = { Flooding: [0xe001, 'AgosIcons'], 'Blocked road': [0xe008, 'AgosIcons'], Landslide: [0xe005, 'AgosIcons'],
            'Fallen power line': [0xf598, 'CupertinoIcons'], 'Stranded people': [0xf47c, 'CupertinoIcons'], Other: [0xf6cf, 'CupertinoIcons'] };
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const dirs = (lat, lng) => `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
        const desktop = () => innerWidth >= 1024;
        let agos = null, tilted = false;

        const pins = [
            ...reports.map(r => {
                const [glyph, font] = glyphs[r.type] || glyphs.Other;
                return { id: 'report-' + r.id, kind: 'flood', lng: r.lng, lat: r.lat, color: colors[r.risk], glyph, font,
                    title: r.barangay, chip: r.depth === null ? r.type : (r.depth < 5 ? 'Dry' : r.depth + ' cm'),
                    pulse: r.risk === 'high', a11y: r.type + ' in Brgy. ' + r.barangay };
            }),
            ...centers.map((c, i) => ({ id: 'center-' + i, kind: 'shelter', lng: c.lng, lat: c.lat,
                color: c.status === 'open' ? '#5E5CE6' : '#8E8E93', glyph: 0xf8e4, font: 'CupertinoIcons',
                title: c.name, chip: null, pulse: false, a11y: c.name + ', evacuation center, ' + c.status })),
        ];
        const zones = reports.map(r => ({ lng: r.lng, lat: r.lat, radius: r.risk === 'high' ? 650 : 480, color: colors[r.risk] }));

        // _showZone / _showCenter: fly there, then the details sheet.
        function showPin(id) {
            document.querySelectorAll('.mp-row').forEach(x => x.classList.toggle('on', x.dataset.pin === id));
            let html;
            if (id.startsWith('report-')) {
                const r = reports.find(x => 'report-' + x.id === id), col = colors[r.risk];
                if (agos) agos.flyTo(r.lng, r.lat, 15);
                const left = r.leftHours >= 24 ? Math.floor(r.leftHours / 24) + (r.leftHours < 48 ? ' day' : ' days') : r.leftHours + (r.leftHours === 1 ? ' hour' : ' hours');
                html = `${r.photo ? `<img class="photo" src="${esc(r.photo)}" alt="Flood photo" tabindex="0" data-zoom data-name="${esc(r.by || '')}" data-when="${esc('Photo ' + (r.takenAgo || r.updated) + ' · Brgy. ' + r.barangay)}">` : ''}
                    <div class="row" style="align-items:flex-start;margin-top:16px"><div class="grow"><h3>Brgy. ${esc(r.barangay)}</h3>
                    <div class="small muted">${esc(r.type)} · posted ${esc(r.updated)}</div></div>
                    <span class="chip" style="background:${col}26;color:${col}">${riskWord[r.risk]} risk</span></div>
                    ${r.by ? `<div class="small" style="margin-top:10px">Posted by <b>${esc(r.by)}</b></div>` : ''}
                    <div class="dates">${r.taken ? `<div><i class="i" data-i="camera"></i>Photo taken ${esc(r.taken)} (${esc(r.takenAgo)})</div>` : ''}
                    <div class="${r.leftHours < 48 ? 'ending' : 'muted'}"><i class="i" data-i="hourglass"></i>Pin will be removed in ${left}</div></div>
                    <hr>
                    ${r.depth !== null ? `<div class="depthline"><b style="color:${col}">${r.depth} cm</b><span>${esc(r.level)}</span></div>` : ''}
                    ${r.note ? `<p class="note-text">${esc(r.note)}</p>` : ''}
                    <div class="guide" style="border-color:${col}">${guidance[r.risk]}</div>
                    <a class="btn white wide" style="margin-top:22px" target="_blank" rel="noopener" href="${dirs(r.lat, r.lng)}">Directions <i class="i" data-i="arrow_up_right_diamond_fill"></i></a>`;
            } else {
                const c = centers[+id.split('-')[1]], open = c.status === 'open', col = open ? '#5E5CE6' : '#8E8E93';
                if (agos) agos.flyTo(c.lng, c.lat, 15);
                html = `<div class="row"><span class="tile" style="width:48px;height:48px;background:${col}2e;color:${col};font-size:22px"><i class="i" data-i="${c.icon}"></i></span>
                    <div class="grow"><h3 style="font-size:17px">${esc(c.name)}</h3><div class="small muted">Evacuation center · Brgy. ${esc(c.barangay)}</div></div></div>
                    <div class="stats2"><div style="background:${col}1a;border-color:${col}40"><span>Status</span><b>${esc(c.status.charAt(0).toUpperCase() + c.status.slice(1))}</b></div>
                    <div style="background:rgba(10,132,255,.1);border-color:rgba(10,132,255,.25)"><span>Distance</span><b>${esc(c.distance || 'Pick your barangay')}</b></div></div>
                    <a class="btn white wide" style="margin-top:20px" target="_blank" rel="noopener" href="${dirs(c.lat, c.lng)}">Directions <i class="i" data-i="arrow_up_right_diamond_fill"></i></a>`;
            }
            document.getElementById('pinBody').innerHTML = html;
            document.getElementById('pinSheet').showModal();
        }
        document.getElementById('pinSheet').addEventListener('click', e => { if (e.target.id === 'pinSheet') e.target.close(); });
        document.querySelectorAll('.mp-row').forEach(b => b.addEventListener('click', () => showPin(b.dataset.pin)));

        // Phones / tablets: the list is a bottom sheet; tap the handle row.
        const panel = document.getElementById('panel');
        document.getElementById('peek').addEventListener('click', () => {
            const open = panel.classList.toggle('open');
            document.getElementById('peek').setAttribute('aria-expanded', open);
        });

        agosMap.load().then(() => {
            const focus = (location.hash.match(/report-(\d+)/) || [])[1];
            const r = focus && reports.find(x => String(x.id) === focus);
            agos = agosMap.create(document.getElementById('map'), JSON.stringify({
                theme: 'dark', interactive: true, minZoom: 13, fit: !r,
                center: r ? [r.lng, r.lat] : [120.5714, 15.2236], zoom: r ? 16 : 13.3,
                // Locked to Mabalacat (kCityBounds), like the app.
                bounds: [[120.490, 15.130], [120.670, 15.300]],
            }), {
                onPinTap: showPin, onMoveEnd: () => {}, onReady: () => {},
                onCamera: (bearing, pitch) => { tilted = pitch > 12; document.getElementById('b3d').textContent = tilted ? '2D' : '3D'; },
            });
            // The map's own padding = the parts the glass covers (desktop: the side panel).
            const pad = () => desktop() ? agos.setPadding(16, 72, 60, 392) : agos.setPadding(70, 64, 90, 0);
            setTimeout(() => { agos.resize(); pad(); }, 300);
            addEventListener('resize', () => { agos.resize(); pad(); });
            agos.setPins(JSON.stringify(pins));
            agos.setZones(JSON.stringify(zones));
            if (r) setTimeout(() => showPin('report-' + r.id), 600);
            document.getElementById('b3d').onclick = () => agos.set3d(!tilted);
            document.getElementById('bhome').onclick = () => agos.reset(120.5714, 15.2236, 13.3);
            document.getElementById('bin').onclick = () => agos.zoomBy(1);
            document.getElementById('bout').onclick = () => agos.zoomBy(-1);
            document.querySelectorAll('.selchip').forEach(b => b.addEventListener('click', () => {
                const on = b.classList.toggle('on');
                agos.setHidden(b.dataset.layer, !on);
            }));
            // Barangay search (barangays only): lands on the map's own label.
            document.querySelectorAll('input.brgy').forEach(input => input.addEventListener('change', () => {
                const name = Object.keys(points).find(n => n.toLowerCase() === input.value.trim().toLowerCase());
                if (!name) return;
                agos.flyTo(points[name][1], points[name][0], 15, name);
                input.blur();
            }));
        }).catch(() => {
            document.getElementById('map').innerHTML = '<p class="empty">The map couldn\'t load. Refresh the page.</p>';
        });
    </script>
@endpush
