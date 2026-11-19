@extends('layouts.app')
@section('title', 'Map')
@section('main_class', 'full')

@push('head')
    {{-- The app's map script loads its files relative to the page
         (map/..., assets/...): they live in public/agos/. --}}
    <base href="{{ asset('agos') }}/">
    <style>
        main.full { max-width: none; padding: 0 !important; }
        .foot { display: none; }
        #map { position: fixed; top: 64px; left: 0; right: 0; bottom: 0; border-radius: 0; border: 0; height: auto; min-height: 0; }
        .legend { position: fixed; top: 80px; left: 16px; z-index: 5; }
        .mapbtns { position: fixed; top: 80px; right: 16px; z-index: 5; display: flex; flex-direction: column; gap: 8px; }
        .mapbtns button { width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--edge-lit); cursor: pointer; color: var(--text);
            background: rgba(20, 22, 32, .78); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); font: 700 13px var(--font); }
        dialog.sheet { width: min(480px, calc(100vw - 32px)); border: 1px solid var(--edge-lit); border-radius: 28px; background: #12131b; color: var(--text); padding: 0; }
        dialog.sheet::backdrop { background: rgba(0, 0, 0, .45); backdrop-filter: blur(4px); }
        dialog.sheet .in { padding: 22px; }
        dialog.sheet img { width: 100%; border-radius: 16px; margin-top: 12px; max-height: 320px; object-fit: cover; }
        @media (max-width: 760px) { .legend { top: 76px; left: 12px; } .mapbtns { top: 76px; right: 12px; } }
    </style>
@endpush

@section('content')
    <div id="map"></div>
    {{-- Glass legend + buttons floating on the map, like the app. --}}
    <div class="legend">
        <b>Water depth</b>
        <div><span class="dot normal"></span> Up to 20 cm</div>
        <div><span class="dot moderate"></span> 21-50 cm</div>
        <div><span class="dot high"></span> Over 50 cm</div>
        <div><span class="dot" style="background:var(--shelter)"></span> Shelter @if ($sample)<span class="sample">SAMPLE</span>@endif</div>
        @if ($me)
            <a class="btn blue sm" href="{{ route('reports.create') }}" style="margin-top:10px"><i class="i" data-i="plus"></i> Report flooding</a>
        @endif
    </div>
    <div class="mapbtns">
        <button type="button" id="b3d" title="Tilt the map (3D)">3D</button>
        <button type="button" onclick="agos && agos.reset(120.5714, 15.2236, 13.3)" title="Show the whole city"><i class="i" data-i="location_fill"></i></button>
    </div>

    <dialog class="sheet" id="pinSheet"><div class="in" id="pinBody"></div>
        <form method="dialog" style="padding:0 22px 22px"><button class="btn gray wide">Close</button></form></dialog>
@endsection

@push('scripts')
    <script src="{{ asset('agos/agos_map.js') }}"></script>
    <script>
        const reports = @json($reports);
        const centers = @json($centers);
        const colors = { normal: '#30D158', moderate: '#FF9F0A', high: '#FF453A' };
        // Same icons as the app's pins (kIncidentTypes): [code point, font].
        const glyphs = { Flooding: [0xe001, 'AgosIcons'], 'Blocked road': [0xe008, 'AgosIcons'], Landslide: [0xe005, 'AgosIcons'],
            'Fallen power line': [0xf598, 'CupertinoIcons'], 'Stranded people': [0xf47c, 'CupertinoIcons'], Other: [0xf6cf, 'CupertinoIcons'] };
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        let agos = null;

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

        function showPin(id) {
            let html;
            if (id.startsWith('report-')) {
                const r = reports.find(x => 'report-' + x.id === id);
                html = `<span class="chip" style="color:${colors[r.risk]}"><span class="dot ${r.risk}"></span> ${r.risk} risk</span>
                    <h3 style="margin:10px 0 2px">${esc(r.type)} · Brgy. ${esc(r.barangay)}</h3>
                    ${r.depth !== null ? `<div class="muted">${r.depth} cm deep</div>` : ''}
                    ${r.note ? `<p style="margin:8px 0 0">${esc(r.note)}</p>` : ''}
                    ${r.photo ? `<img src="${esc(r.photo)}" alt="Flood photo">` : ''}`;
            } else {
                const c = centers[+id.split('-')[1]];
                html = `<span class="tile ${c.status === 'open' ? 'indigo' : 'gray'}"><i class="i" data-i="house_alt_fill"></i></span>
                    <h3 style="margin:10px 0 2px">${esc(c.name)}</h3>
                    <div class="muted">Evacuation center · Brgy. ${esc(c.barangay)} · ${esc(c.status)}</div>
                    <a class="btn blue wide" style="margin-top:16px" target="_blank" rel="noopener"
                       href="https://www.google.com/maps/dir/?api=1&destination=${c.lat},${c.lng}">Directions</a>`;
            }
            document.getElementById('pinBody').innerHTML = html;
            document.getElementById('pinSheet').showModal();
        }

        agosMap.load().then(() => {
            const focus = (location.hash.match(/report-(\d+)/) || [])[1];
            const r = focus && reports.find(x => String(x.id) === focus);
            agos = agosMap.create(document.getElementById('map'), JSON.stringify({
                theme: 'dark', interactive: true, minZoom: 13, fit: !r,
                center: r ? [r.lng, r.lat] : [120.5714, 15.2236], zoom: r ? 16 : 13.3,
                // Locked to Mabalacat (kCityBounds), like the app.
                bounds: [[120.490, 15.130], [120.670, 15.300]],
            }), { onPinTap: showPin, onCamera: () => {}, onMoveEnd: () => {}, onReady: () => {} });
            // Re-measure once the page has settled (and on window resizes),
            // or MapLibre keeps the size it saw first and leaves a strip.
            setTimeout(() => agos.resize(), 300);
            addEventListener('resize', () => agos.resize());
            agos.setPins(JSON.stringify(pins));
            agos.setZones(JSON.stringify(zones));
            if (r) setTimeout(() => showPin('report-' + r.id), 600);
            let tilted = false;
            document.getElementById('b3d').onclick = () => { tilted = !tilted; agos.set3d(tilted); };
        }).catch(() => {
            document.getElementById('map').innerHTML = '<p class="empty">The map couldn\'t load. Refresh the page.</p>';
        });
    </script>
@endpush
