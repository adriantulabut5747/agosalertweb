@extends('layouts.app')
@section('title', 'Map')

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@section('content')
    <div class="mapwrap">
        <div id="map"></div>
        {{-- Glass legend floating on the map, like the app's. --}}
        <div class="legend">
            <b>Water depth</b>
            <div><span class="dot normal"></span> Up to 20 cm</div>
            <div><span class="dot moderate"></span> 21-50 cm</div>
            <div><span class="dot high"></span> Over 50 cm</div>
            <div><span class="dot" style="background:var(--shelter)"></span> Evacuation center @if ($sample)<span class="sample">SAMPLE</span>@endif</div>
            @if ($me)
                <a class="btn blue sm" href="{{ route('reports.create') }}" style="margin-top:10px"><i class="i" data-i="plus"></i> Report flooding</a>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        const reports = @json($reports);
        const centers = @json($centers);
        const colors = { normal: '#30d158', moderate: '#ff9f0a', high: '#ff453a' };
        const typeIcon = { Flooding: ['ai', 'flood'], 'Blocked road': ['ai', 'road_block'], Landslide: ['ai', 'landslide'],
            'Fallen power line': ['i', 'bolt_fill'], 'Stranded people': ['i', 'person_3_fill'] };
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

        // Locked to Mabalacat (same box as kCityBounds in the app).
        const city = L.latLngBounds([15.130, 120.490], [15.300, 120.670]);
        const map = L.map('map', { maxBounds: city.pad(0.1), minZoom: 12, zoomControl: false }).setView([15.2236, 120.5714], 13.3);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        // Esri's free dark canvas (no key), same as the app's fallback map.
        const esri = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/';
        L.tileLayer(esri + 'World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
            maxNativeZoom: 16, maxZoom: 19, attribution: 'Esri, HERE, Garmin, &copy; OpenStreetMap contributors',
        }).addTo(map);
        L.tileLayer(esri + 'World_Dark_Gray_Reference/MapServer/tile/{z}/{y}/{x}', { maxNativeZoom: 16, maxZoom: 19 }).addTo(map);

        const pin = (color, font, icon) => L.divIcon({ className: '',
            html: `<div class="pin" style="background:${color}"><i class="${font}" data-i="${icon}"></i></div>`, iconSize: [30, 30], iconAnchor: [15, 15] });
        const byId = {};
        for (const r of reports) {
            const [f, ic] = typeIcon[r.type] || ['i', 'info_circle_fill'];
            L.circle([r.lat, r.lng], { radius: r.risk === 'high' ? 650 : 480, color: colors[r.risk], weight: 1, fillOpacity: 0.14 }).addTo(map);
            byId[r.id] = L.marker([r.lat, r.lng], { icon: pin(colors[r.risk], f, ic) }).addTo(map).bindPopup(
                `<b>${esc(r.type)}</b> · Brgy. ${esc(r.barangay)}<br>` +
                (r.depth !== null ? `<span style="color:${colors[r.risk]}">${r.depth} cm deep</span><br>` : '') +
                (r.note ? `<i>${esc(r.note)}</i><br>` : '') +
                (r.photo ? `<img src="${esc(r.photo)}" style="width:230px;border-radius:12px;margin-top:6px">` : ''));
        }
        for (const c of centers) {
            L.marker([c.lat, c.lng], { icon: pin(c.status === 'open' ? '#5e5ce6' : '#8e8e93', 'i', 'house_alt_fill') }).addTo(map)
                .bindPopup(`<b>${esc(c.name)}</b><br>Evacuation center · Brgy. ${esc(c.barangay)} · ${esc(c.status)}<br>` +
                    `<a href="https://www.google.com/maps/dir/?api=1&destination=${c.lat},${c.lng}" target="_blank" rel="noopener">Directions</a>`);
        }
        // From Weather / Reports: /map#report-12 opens that pin.
        const m = location.hash.match(/report-(\d+)/);
        if (m && byId[m[1]]) { map.setView(byId[m[1]].getLatLng(), 16); byId[m[1]].openPopup(); }
    </script>
@endpush
