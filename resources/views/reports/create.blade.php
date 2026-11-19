@extends('layouts.app')
@section('title', 'Report flooding')

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@section('content')
    <div class="feed">
        <h1>Report flooding</h1>
        <p class="sub">Only what you can see yourself, right now. No faces or plate numbers in photos.</p>

        <form class="card" method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
            @csrf
            <label for="incident_type">What's happening?</label>
            <select id="incident_type" name="incident_type" required>
                @foreach ($types as $t)
                    <option value="{{ $t }}" @selected(old('incident_type') === $t)>{{ ucfirst($t) }}</option>
                @endforeach
            </select>

            <label for="severity">How bad?</label>
            <select id="severity" name="severity" required>
                @foreach (['minor' => 'Minor', 'moderate' => 'Moderate', 'high' => 'High'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('severity') === $v)>{{ $l }}</option>
                @endforeach
            </select>

            <label for="depth_cm">Water depth in cm (optional)</label>
            <input id="depth_cm" type="number" name="depth_cm" min="0" max="300" value="{{ old('depth_cm') }}" placeholder="e.g. 30 = knee-deep">

            <label for="barangay">Barangay</label>
            <select id="barangay" name="barangay" required>
                @foreach ($barangays as $b)
                    <option @selected(old('barangay', $me->barangay) === $b)>{{ $b }}</option>
                @endforeach
            </select>

            <label>Where exactly? Tap the map</label>
            <div id="pick"></div>
            <input type="hidden" name="latitude" id="lat" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="lng" value="{{ old('longitude') }}">
            <p class="small muted" id="pickhint" style="margin:6px 0 0">No spot set yet.</p>

            <label for="note">Note (optional)</label>
            <textarea id="note" name="note" maxlength="500" placeholder="e.g. Knee-deep near the bridge, cars can't pass">{{ old('note') }}</textarea>

            <label for="photo">Photo (optional, up to 5 MB)</label>
            <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp">

            @if ($errors->any())
                <ul class="errors">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            @endif
            <button class="btn blue wide" type="submit" style="margin-top:18px">Post report</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        const city = L.latLngBounds([15.130, 120.490], [15.300, 120.670]);
        const map = L.map('pick', { maxBounds: city.pad(0.1), minZoom: 12 }).setView([15.2236, 120.5714], 13);
        const esri = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/';
        L.tileLayer(esri + 'World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', { maxNativeZoom: 16, maxZoom: 19, attribution: 'Esri, &copy; OpenStreetMap contributors' }).addTo(map);
        L.tileLayer(esri + 'World_Dark_Gray_Reference/MapServer/tile/{z}/{y}/{x}', { maxNativeZoom: 16, maxZoom: 19 }).addTo(map);
        let marker = null;
        const lat = document.getElementById('lat'), lng = document.getElementById('lng'), hint = document.getElementById('pickhint');
        function set(p) {
            if (!city.contains(p)) { hint.textContent = 'That spot is outside Mabalacat.'; return; }
            lat.value = p.lat.toFixed(6); lng.value = p.lng.toFixed(6);
            marker ? marker.setLatLng(p) : marker = L.marker(p).addTo(map);
            hint.textContent = 'Spot set. Tap again to move it.';
        }
        map.on('click', e => set(e.latlng));
        if (lat.value) set(L.latLng(+lat.value, +lng.value));
    </script>
@endpush
