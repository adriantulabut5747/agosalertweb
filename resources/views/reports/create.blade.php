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

            {{-- _photoBox in the app: one box, two halves (camera | gallery); once
                 picked, the photo with an x to remove it. --}}
            <label>Photo (optional, but it helps)</label>
            <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
            <input id="photo-cam" type="file" accept="image/*" capture="environment" hidden>
            <input id="photo-lib" type="file" accept="image/jpeg,image/png,image/webp" hidden>
            <div class="photobox" id="photobox">
                <button type="button" onclick="document.getElementById('photo-cam').click()"><i class="i" data-i="camera"></i>Take photo</button>
                <span class="vdiv"></span>
                <button type="button" onclick="document.getElementById('photo-lib').click()"><i class="i" data-i="photo_on_rectangle"></i>Choose from gallery</button>
            </div>
            <div class="photopreview" id="photopreview" hidden>
                <img id="photoimg" alt="Your photo">
                <button type="button" id="photox" aria-label="Remove the photo"><i class="i" data-i="xmark"></i></button>
            </div>
            <p class="small muted" id="photonote" hidden style="margin:8px 0 0"></p>

            @if ($errors->any())
                <ul class="errors">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            @endif
            <button class="btn blue wide" type="submit" style="margin-top:18px">Post report</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // The picked photo (camera or gallery) is shrunk to 1600 px JPEG in the
        // browser before upload, like the app's photo_web.dart: smaller on
        // mobile data, and the GPS location the camera hid in the file is gone.
        (function () {
            var input = document.getElementById('photo'), box = document.getElementById('photobox'), prev = document.getElementById('photopreview'),
                img = document.getElementById('photoimg'), note = document.getElementById('photonote');
            function clear() { input.value = ''; prev.hidden = true; box.hidden = false; note.hidden = true; img.removeAttribute('src'); }
            function pick(file) {
                if (!file) return;
                createImageBitmap(file).then(function (bmp) {
                    var k = Math.min(1, 1600 / Math.max(bmp.width, bmp.height)), c = document.createElement('canvas');
                    c.width = Math.round(bmp.width * k); c.height = Math.round(bmp.height * k);
                    c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
                    bmp.close && bmp.close();
                    c.toBlob(function (blob) {
                        var dt = new DataTransfer();
                        dt.items.add(new File([blob], 'photo.jpg', { type: 'image/jpeg' }));
                        input.files = dt.files;
                        img.src = URL.createObjectURL(blob);
                        prev.hidden = false; box.hidden = true;
                    }, 'image/jpeg', 0.8);
                }).catch(function () {
                    clear();
                    note.hidden = false;
                    note.textContent = "Couldn't open that photo. Try another one, or a JPEG.";
                });
            }
            ['photo-cam', 'photo-lib'].forEach(function (id) {
                var el = document.getElementById(id);
                el.addEventListener('change', function () { pick(el.files[0]); el.value = ''; });
            });
            document.getElementById('photox').addEventListener('click', clear);
        })();
    </script>
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
