@extends('layouts.app')
@section('title', 'Help')

{{-- The Flutter Help tab (lib/screens/assistance_tab.dart). Desktop (>= 1024):
     two bands, SOS | hotlines + open shelter at equal height, then Be ready
     across the width. Phones / tablets: a compact SOS, every hotline and
     every center listed (no "All" tap). ONE focal point: the SOS, the only
     red; every other icon is the neutral glyph. --}}
@php
    // kHotlines: icon + the color its call sheet uses.
    $hotIcons = ['911' => ['phone_fill', '#ff453a'], '0998-999-4357' => ['exclamationmark_shield_fill', '#32ade6'],
        '0933-990-9960' => ['flame_fill', '#af52de'], '0998-598-5458' => ['shield_fill', '#5e5ce6'], '(045) 649-8620' => ['building_2_fill', '#0a84ff']];
    $phases = [
        ['Before', 'Rain is on the way', [
            ['i', 'phone_fill', 'Save the hotlines now', 'Put CDRRMO and your barangay hall in your contacts before the rain starts.'],
            ['i', 'house_alt_fill', 'Know your evacuation center', 'Find the nearest one and walk the route once while the roads are dry.'],
        ]],
        ['During', 'Water is rising', [
            ['ai', 'landslide', 'Go to higher ground', "Move early, before the water rises. Don't wait for it to reach your door."],
            ['i', 'bolt_slash_fill', 'Switch off the power', 'Turn off the main breaker if water may enter your home.'],
            ['i', 'nosign', "Don't walk in floodwater", 'It can hide open drains, sharp debris, strong currents, and live wires.'],
        ]],
        ['After', 'Water is going down', [
            ['i', 'checkmark_shield_fill', "Go home only when it's safe", 'Wait until local officials say your area is clear.'],
            ['i', 'bolt_fill', 'Stay away from fallen wires', 'Report them to the barangay or the power company. Never touch them.'],
            ['i', 'drop_fill', 'Boil water before drinking', 'Floodwater can get into the water supply. Clean anything it touched.'],
        ]],
    ];
    $goBag = [
        ['Drinking water (3 days)', 'At least 1 gallon per person per day'], ['Ready-to-eat food', 'Canned goods and biscuits'],
        ['Flashlight & batteries', ''], ['Power bank', 'Fully charged'], ['First aid kit & medicines', 'Include maintenance meds'],
        ['IDs & documents', 'In a waterproof pouch'], ['Cash', 'Small bills'], ['Whistle', 'To signal for help'], ['Extra clothes & raincoat', ''],
    ];
    $open = $centers->firstWhere('status', 'open');
@endphp

@section('content')
    <h1 class="page-title">Help</h1>
    <p class="page-sub">Get help fast, and be ready before the rain</p>

    <div class="help">
        {{-- SOS: hold to call 911 (a keyboard press still asks first). --}}
        <div class="card sos">
            <div class="sos-main">
                <button class="sos-btn" type="button" id="sos" aria-label="SOS, call 911">
                    <span class="pulse"></span><span class="pulse two"></span>
                    <svg class="ring" viewBox="0 0 140 140" aria-hidden="true"><circle cx="70" cy="70" r="67.5" pathLength="100"/></svg>
                    <span class="core">SOS</span>
                </button>
                <div class="sos-text">
                    <h3 id="sos-title">Hold to call 911</h3>
                    <p>For danger to life. We ask before calling.</p>
                </div>
            </div>
            <div class="pair">
                <button class="pillbtn" type="button" onclick="document.getElementById('safe').showModal()"><i class="i" data-i="checkmark_shield_fill" style="color:var(--safe)"></i>I'm safe</button>
                <button class="pillbtn" type="button" onclick="document.getElementById('rescue').showModal()"><i class="ai" data-i="boat"></i>Request rescue</button>
            </div>
        </div>

        <div class="help-side">
            @if ($mine)
                {{-- MyRescueCard: this browser's last request. --}}
                <div class="card row myrescue">
                    <span class="glyph-tile"><i class="ai" data-i="boat"></i></span>
                    <div class="grow"><b>Rescue request #{{ $mine->request_id }}: {{ $mine->statusLabel() }}</b>
                        <div class="small muted">Demo: no one is watching yet. In danger? Call 911. Updated {{ $mine->updated_at->diffForHumans() }}.</div></div>
                </div>
            @endif

            <div class="section"><h2>Hotlines</h2><a class="more desk-only" href="#" onclick="document.getElementById('hotlines').showModal();return false">All {{ count($hotlines) }} <i class="i" data-i="chevron_right"></i></a></div>
            <div class="card list hot">
                @foreach ($hotlines as [$name, $number, $kind])
                    <button type="button" class="it {{ in_array($kind, ['CDRRMO', 'Police']) ? '' : 'extra' }}" data-call="{{ $name }}" data-number="{{ $number }}" data-color="{{ $hotIcons[$number][1] ?? '#ff453a' }}">
                        <span class="glyph-tile"><i class="i" data-i="{{ $hotIcons[$number][0] ?? 'phone_fill' }}"></i></span>
                        <span class="grow"><b>{{ $name }}</b><span class="small muted">{{ $number }}</span></span>
                        <i class="i call" data-i="phone_fill"></i>
                    </button>
                @endforeach
            </div>

            {{-- Desktop: the open shelter, one row. Phones / tablets: every center. --}}
            <div class="desk-only">
                <div class="section shelter-head"><h2>Open shelter</h2>@if ($sample)<span class="sample">SAMPLE</span>@endif
                    <a class="more" href="{{ route('map') }}">All centers <i class="i" data-i="chevron_right"></i></a></div>
                @if ($open)
                    <div class="card">@include('partials.shelter-row', ['c' => $open])</div>
                @else
                    <p class="small muted" style="margin:0">No evacuation center is open right now.</p>
                @endif
            </div>
            <div class="narrow-only">
                <div class="section shelter-head"><h2>Evacuation centers</h2>@if ($sample)<span class="sample">SAMPLE</span>@endif
                    <a class="more" href="{{ route('map') }}">Details <i class="i" data-i="chevron_right"></i></a></div>
                <div class="card list">
                    @forelse ($centers as $c)
                        <div class="it">@include('partials.shelter-row', ['c' => $c])</div>
                    @empty
                        <p class="muted">No evacuation centers listed.</p>
                    @endforelse
                </div>
            </div>
            <p class="note desk-only">Hotline numbers are from Mabalacat City government channels. Check them locally before you rely on them.</p>
        </div>
    </div>

    {{-- Be ready: Before -> During -> After. --}}
    <div class="ready">
        <div class="section"><h2>Be ready</h2></div>
        <div class="tugak-beside">
            <img src="{{ asset('img/tugak/handset.webp') }}" alt="">
            <div class="say tail-left"><div class="who">TUGAK</div><p>Do the Before list today, while it's dry. Once the water rises, there's no time to read.</p></div>
        </div>
        <div class="timeline" id="timeline" style="--n:3">
            <span class="track"></span><span class="fill"></span>
            @foreach ($phases as $i => [$name, $hint])
                <button type="button" class="step {{ $i === 0 ? 'on' : '' }}" data-phase="{{ $i }}"><span class="tdot"></span><b>{{ $name }}</b><span class="small muted">{{ $hint }}</span></button>
            @endforeach
        </div>
        @foreach ($phases as $i => [$name, $hint, $tips])
            <div class="phase" data-phase="{{ $i }}" @if ($i) hidden @endif>
                <div class="card tips">
                    @foreach ($tips as [$font, $icon, $title, $body])
                        <div class="tip"><span class="glyph-tile"><i class="{{ $font }}" data-i="{{ $icon }}"></i></span>
                            <div class="grow"><b>{{ $title }}</b><span>{{ $body }}</span></div></div>
                    @endforeach
                </div>
                @if ($i === 0)
                    <div class="card gobag" id="gobag">
                        <div class="row"><h3 class="grow" id="gb-title">Pack a go-bag</h3><span class="count" id="gb-count">0/{{ count($goBag) }}</span></div>
                        <p class="small muted" style="margin:2px 0 12px">Keep it by the door. Ticks are saved on this device.</p>
                        <div class="segs">@foreach ($goBag as $g)<i></i>@endforeach</div>
                        <div class="items">
                            @foreach ($goBag as [$item, $note])
                                <button type="button" class="check" data-item="{{ $item }}" role="checkbox" aria-checked="false">
                                    <span class="box"><i class="i" data-i="checkmark"></i></span>
                                    <span class="grow"><b>{{ $item }}</b>@if ($note)<span>{{ $note }}</span>@endif</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @elseif ($i === 1)
                    {{-- _DepthDiagram: NWS "Turn Around, Don't Drown". --}}
                    <div class="card depth">
                        <h3>Moving water is stronger than it looks</h3>
                        <p class="small muted" style="margin:2px 0 16px">If it is flowing, don't walk or drive through it.</p>
                        <div class="tanks">
                            @foreach ([[15, '6 in', 'Can knock an adult off their feet'], [30, '1 ft', 'Can carry away most cars'], [60, '2 ft', 'Can carry away SUVs and trucks']] as [$cm, $imp, $what])
                                <div><div class="tank"><i style="height:{{ $cm / 75 * 100 }}%"></i></div>
                                    <div class="cm"><b>{{ $cm }} cm</b><span class="muted">&nbsp; {{ $imp }}</span></div>
                                    <div class="small muted">{{ $what }}</div></div>
                            @endforeach
                        </div>
                        <p class="tiny muted" style="margin:14px 0 0">Source: US National Weather Service, "Turn Around, Don't Drown"</p>
                    </div>
                @endif
            </div>
        @endforeach
        <p class="note narrow-only">Hotline numbers are from Mabalacat City government channels. Check them locally before you rely on them.</p>
    </div>

    {{-- confirmCall: asks before calling, so nobody dials by accident. --}}
    <dialog class="sheet" id="call">
        <span class="badge-lg" id="call-badge"><i class="i" data-i="phone_fill"></i></span>
        <div class="call-name" id="call-name"></div>
        <div class="call-number" id="call-number"></div>
        <a class="btn white wide" id="call-go" href="#" style="margin-top:20px">Call now <i class="i" data-i="phone_fill"></i></a>
        <button class="btn gray wide" type="button" onclick="this.closest('dialog').close()">Cancel</button>
    </dialog>

    {{-- Every hotline (desktop's "All 5"). --}}
    <dialog class="sheet" id="hotlines" style="text-align:left">
        <h3 style="font-size:22px">Hotlines</h3>
        <p style="margin-bottom:10px">Tap a number to call. We ask first.</p>
        <div class="list hot">
            @foreach ($hotlines as [$name, $number])
                <button type="button" class="it" data-call="{{ $name }}" data-number="{{ $number }}" data-color="{{ $hotIcons[$number][1] ?? '#ff453a' }}">
                    <span class="glyph-tile"><i class="i" data-i="{{ $hotIcons[$number][0] ?? 'phone_fill' }}"></i></span>
                    <span class="grow"><b>{{ $name }}</b><span class="small muted">{{ $number }}</span></span>
                    <i class="i call" data-i="phone_fill"></i>
                </button>
            @endforeach
        </div>
    </dialog>

    {{-- "I'm safe" (check_in.dart): Tugak tips his hat, then share or copy. --}}
    <dialog class="sheet safe-sheet" id="safe">
        <img src="{{ asset('img/tugak/tip.webp') }}" alt="" style="height:110px">
        <h3>Tell people you're safe</h3>
        <p>One message to family and friends, so nobody has to guess.</p>
        <div class="bubble" id="safe-text"></div>
        <button class="btn green wide" type="button" onclick="imSafe()">Share "I'm safe"</button>
    </dialog>

    {{-- The rescue request sheet (FR-G8). --}}
    <dialog class="sheet wide rescue-sheet" id="rescue">
        <button class="x" type="button" onclick="this.closest('dialog').close()" aria-label="Close"><i class="i" data-i="xmark"></i></button>
        <form method="POST" action="{{ route('rescue.store') }}">
            @csrf
            <div class="row"><span class="tile red" style="width:48px;height:48px;font-size:24px"><i class="ai" data-i="boat"></i></span>
                <h3 style="margin:0">Request rescue</h3></div>
            {{-- Saved, but nobody watches them yet: keep this until real
                 responders use the admin dashboard. --}}
            <div class="warn">
                <b>Demo only: no rescuer will come</b><br>
                <span class="small">AgosAlert is a school project. Requests are saved, but no one is watching them yet. If you're in danger, call 911 now.</span><br>
                <a class="btn red sm" href="tel:911" style="margin-top:10px"><i class="i" data-i="phone_fill"></i> Call 911</a>
            </div>
            <label class="lbl">Who should the admins call?</label>
            <input type="text" name="contact_name" value="{{ old('contact_name', $me?->full_name) }}" placeholder="Full name" required>
            <input type="tel" name="contact_phone" value="{{ old('contact_phone', $me?->phone_number) }}" placeholder="Phone number, e.g. 0917 123 4567" required style="margin-top:10px">
            <label class="lbl">Where is help needed?</label>
            <select name="barangay" required>
                <option value="">Pick a barangay</option>
                @foreach ($barangays as $b)<option @selected(old('barangay', $me?->barangay) === $b)>{{ $b }}</option>@endforeach
            </select>
            <label class="lbl">How many people?</label>
            <input type="number" name="people_count" min="1" max="99" value="{{ old('people_count', 1) }}" required>
            <label class="lbl">Anyone who needs extra help?</label>
            <div class="checks">
                @foreach ($needs as $key => $label)
                    <label><input type="checkbox" name="special_needs[]" value="{{ $key }}" @checked(in_array($key, old('special_needs', [])))> {{ $label }}</label>
                @endforeach
            </div>
            <label class="lbl">Notes (optional)</label>
            <textarea name="note" maxlength="500" placeholder="e.g. We are on the 2nd floor, water is rising">{{ old('note') }}</textarea>
            @if ($errors->any())<ul class="errors">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
            <button class="btn red wide" type="submit" style="margin-top:18px"><i class="i" data-i="paperplane_fill"></i> Send rescue request</button>
            <p class="tiny muted" style="margin:10px 0 0">The name, number, place and needs go to AgosAlert's admins. See the Privacy Policy.</p>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
    // Reopen the form with its errors after a failed send.
    @if ($errors->any()) document.getElementById('rescue').showModal(); @endif

    // confirmCall
    function askCall(name, number, color) {
        var d = document.getElementById('call');
        document.querySelectorAll('dialog[open]').forEach(function (x) { if (x !== d) x.close(); });
        d.querySelector('#call-name').textContent = name;
        var n = d.querySelector('#call-number'); n.textContent = number; n.style.color = color;
        var b = d.querySelector('#call-badge'); b.style.color = color; b.style.background = color + '29';
        d.querySelector('#call-go').href = 'tel:' + number.replace(/[^0-9+]/g, '');
        d.querySelector('#call-go').onclick = function () { d.close(); };
        d.showModal();
    }
    document.querySelectorAll('[data-call]').forEach(function (b) {
        b.addEventListener('click', function () { askCall(b.dataset.call, b.dataset.number, b.dataset.color); });
    });

    // SOS: hold 1.4 s (the ring fills), then the dialer at once (the hold is the confirmation).
    (function () {
        var b = document.getElementById('sos'), title = document.getElementById('sos-title'), t0 = 0, raf = 0, ring = b.querySelector('.ring circle');
        function set(v) { ring.style.strokeDashoffset = 100 - v * 100; b.classList.toggle('holding', v > 0); b.style.setProperty('--hold', v);
            title.textContent = v > 0 ? 'Keep holding…' : 'Hold to call 911'; }
        function tick() {
            var v = Math.min(1, (performance.now() - t0) / 1400);
            set(v);
            if (v >= 1) { set(0); t0 = 0; location.href = 'tel:911'; return; }
            raf = requestAnimationFrame(tick);
        }
        function down(e) { e.preventDefault(); t0 = performance.now(); raf = requestAnimationFrame(tick); }
        function up() { if (!t0) return; cancelAnimationFrame(raf); t0 = 0; set(0); }
        b.addEventListener('pointerdown', down);
        ['pointerup', 'pointerleave', 'pointercancel'].forEach(function (ev) { b.addEventListener(ev, up); });
        // Keyboard / screen readers: Enter opens the sheet (it still asks).
        b.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); askCall('National Emergency Hotline', '911', '#ff453a'); } });
    })();

    // Before / During / After
    document.querySelectorAll('#timeline .step').forEach(function (s) {
        s.addEventListener('click', function () {
            var i = +s.dataset.phase;
            document.querySelectorAll('#timeline .step').forEach(function (x) { x.classList.toggle('on', +x.dataset.phase === i); x.classList.toggle('passed', +x.dataset.phase < i); });
            document.getElementById('timeline').style.setProperty('--sel', i);
            document.querySelectorAll('.phase').forEach(function (p) { p.hidden = +p.dataset.phase !== i; });
        });
    });

    // Go-bag ticks: saved by name in this browser (like shared_preferences).
    (function () {
        var key = 'go_bag_packed', packed = [];
        try { packed = JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) {}
        var checks = document.querySelectorAll('#gobag .check'), segs = document.querySelectorAll('#gobag .segs i');
        function draw() {
            var done = 0;
            checks.forEach(function (c) { var on = packed.indexOf(c.dataset.item) >= 0; c.classList.toggle('on', on); c.setAttribute('aria-checked', on); if (on) done++; });
            var ready = done === checks.length;
            segs.forEach(function (s, i) { s.className = i < done ? (ready ? 'ready' : 'on') : ''; });
            document.getElementById('gb-count').textContent = done + '/' + checks.length;
            document.getElementById('gb-count').classList.toggle('ready', ready);
            document.getElementById('gb-title').textContent = ready ? 'Your go-bag is ready' : 'Pack a go-bag';
        }
        checks.forEach(function (c) {
            c.addEventListener('click', function () {
                var i = packed.indexOf(c.dataset.item);
                if (i >= 0) packed.splice(i, 1); else packed.push(c.dataset.item);
                try { localStorage.setItem(key, JSON.stringify(packed)); } catch (e) {}
                draw();
            });
        });
        draw();
    })();

    // "I'm safe" (imSafeMessage): the share menu on phones, else copy.
    var who = @js(collect([$me?->full_name, $me?->barangay ? 'Brgy. '.$me->barangay : null])->filter()->join(', '));
    function safeText() {
        var d = new Date(), wd = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        return "I'm safe." + (who ? ' — ' + who : '') + ' · ' + wd + ', ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
            + '\nSent with AgosAlert, flood alerts for Mabalacat City.';
    }
    document.querySelector('.pillbtn').addEventListener('click', function () { document.getElementById('safe-text').textContent = safeText(); });
    function imSafe() {
        var text = safeText(), d = document.getElementById('safe');
        function copied() {
            d.close();
            var t = document.createElement('div');
            t.className = 'toast'; t.setAttribute('role', 'status'); t.innerHTML = '<span>Copied. Paste it into any chat.</span>';
            document.body.appendChild(t);
            setTimeout(function () { t.classList.add('gone'); }, 4000);
        }
        if (navigator.share) { navigator.share({ text: text }).then(function () { d.close(); }).catch(function () {}); }
        else if (navigator.clipboard) { navigator.clipboard.writeText(text).then(copied); }
    }
</script>
@endpush
