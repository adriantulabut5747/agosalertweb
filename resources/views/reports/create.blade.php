@extends('layouts.app')
@section('title', 'Report flooding')

@push('head')
    <base href="{{ asset('agos') }}/">
    <style>
        #pick { position: relative; height: 300px; border-radius: 16px; overflow: hidden; border: 1px solid var(--edge); }
        /* A fixed pin in the middle: drag the map under it, like the app. */
        #pick .center-pin { position: absolute; left: 50%; top: 50%; z-index: 3; width: 34px; height: 43px; margin: -43px 0 0 -17px; pointer-events: none;
            filter: drop-shadow(0 3px 5px rgba(0, 0, 0, .4)); }
    </style>
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

            <label>Where exactly? Drag the map so the pin sits on the spot</label>
            <div id="pick"><svg class="center-pin" viewBox="0 0 34 43"><path d="M17 42C14 34 1 29 1 17A16 16 0 1 1 33 17C33 29 20 34 17 42Z" fill="#0A84FF" stroke="#fff" stroke-width="2.2"/></svg></div>
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
    <script src="{{ asset('agos/agos_map.js') }}"></script>
    <script>
        // The app's map; the spot = the map's center under the fixed pin.
        const lat = document.getElementById('lat'), lng = document.getElementById('lng'), hint = document.getElementById('pickhint');
        function set(la, lo) {
            lat.value = la.toFixed(6); lng.value = lo.toFixed(6);
            hint.textContent = 'Spot set. Drag the map to move it.';
        }
        agosMap.load().then(() => {
            const start = lat.value ? [+lng.value, +lat.value] : [120.5714, 15.2236];
            agosMap.create(document.getElementById('pick'), JSON.stringify({
                theme: 'dark', interactive: true, minZoom: 13, fit: false, center: start, zoom: lat.value ? 16 : 14,
                bounds: [[120.490, 15.130], [120.670, 15.300]],
            }), { onPinTap: () => {}, onCamera: () => {}, onMoveEnd: (la, lo) => set(la, lo), onReady: () => {} });
        });
    </script>
@endpush
