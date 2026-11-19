@extends('layouts.app')
@section('title', 'Reports')
@use('App\Support\AgosData')

{{-- The Flutter Reports tab (lib/screens/reports_tab.dart): one 640 px
     feed column; Tugak + the report button; search + Filter (sort, risk,
     type, my barangay, verified), all instant like the app; Facebook-style
     posts (ReportPost). --}}
@php
    // UserAvatar: a color from the user id, lighter at the top.
    $avatar = ['#0a84ff', '#5e5ce6', '#32ade6', '#af52de', '#a2845e', '#64d2ff'];
    $riskWord = ['normal' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'];
    $riskColor = ['normal' => '#30d158', 'moderate' => '#ff9f0a', 'high' => '#ff453a'];
    $myBrgy = $me?->barangay;
@endphp

@section('content')
    <div class="feedcol">
        <h1 class="page-title">Reports</h1>
        <p class="page-sub">What people in Mabalacat are seeing</p>

        <div class="tugak-beside reports-intro">
            <img src="{{ asset('img/tugak/phone.webp') }}" alt="">
            <div>
                <div class="say"><div class="who">TUGAK</div><p>See water on a street? Post one photo and pin the spot. That's all I need.</p></div>
                {{-- Guests get the same button; it asks them to log in. --}}
                <a class="btn white report-btn" href="{{ $me ? route('reports.create') : route('login') }}">Report flooding <i class="i" data-i="plus"></i></a>
            </div>
        </div>

        <div class="searchrow">
            <div class="field"><i class="i" data-i="search"></i>
                <input type="search" id="q" placeholder="Search names, notes, barangays" autocomplete="off"></div>
            <button class="filterbtn" type="button" id="filterbtn" aria-expanded="false"><i class="i" data-i="slider_horizontal_3"></i><span>Filter</span></button>
        </div>
        <div class="filters" id="filters" hidden>
            <div class="fieldlabel">Sort by</div>
            <div class="chips" data-group="sort">
                @foreach (['newest' => 'Newest', 'oldest' => 'Oldest', 'liked' => 'Most liked', 'disliked' => 'Most disliked', 'deepest' => 'Deepest'] as $k => $l)
                    <button type="button" class="selchip {{ $k === 'newest' ? 'on' : '' }}" data-v="{{ $k }}">{{ $l }}</button>
                @endforeach
            </div>
            <div class="fieldlabel">Risk</div>
            <div class="chips" data-group="risk">
                <button type="button" class="selchip on" data-v="">Any</button>
                @foreach (['high' => 'High', 'moderate' => 'Moderate', 'normal' => 'Low'] as $k => $l)
                    <button type="button" class="selchip" data-v="{{ $k }}"><span class="dot {{ $k }}"></span>{{ $l }}</button>
                @endforeach
            </div>
            <div class="fieldlabel">What happened</div>
            <div class="chips" data-group="type">
                <button type="button" class="selchip on" data-v="">Anything</button>
                @foreach (array_diff($types, ['landslide']) as $t) {{-- no Landslide chip: nobody has posted one --}}
                    @php [$f, $ic] = AgosData::typeIcon($t); @endphp
                    <button type="button" class="selchip" data-v="{{ $t }}"><i class="{{ $f }}" data-i="{{ $ic }}"></i>{{ ucfirst($t) }}</button>
                @endforeach
            </div>
            <div class="fieldlabel">Show only</div>
            <div class="chips">
                @if ($myBrgy)
                    <button type="button" class="selchip toggle" data-flag="mine"><i class="i" data-i="placemark_fill"></i>Brgy. {{ $myBrgy }}</button>
                @endif
                <button type="button" class="selchip toggle" data-flag="verified"><i class="i" data-i="checkmark_seal_fill"></i>Verified by Admin</button>
            </div>
        </div>

        <div class="countrow"><span id="count">{{ $reports->count() }} {{ Str::plural('report', $reports->count()) }} · newest first</span>
            <button type="button" class="clearall" id="clearall" hidden>Clear all</button></div>

        @if ($failed)
            <div class="emptybox"><h3>Couldn't load the reports</h3><p>Check your connection.</p><a class="btn tint sm" href="{{ route('reports.index') }}">Retry</a></div>
        @endif
        <div class="emptybox" id="empty" @if ($failed || $reports->isNotEmpty()) hidden @endif>
            <h3 id="empty-title">No reports yet</h3><p id="empty-text">Dry streets, or nobody has posted yet.</p>
            <button class="btn tint sm" type="button" id="empty-clear" hidden>Clear all</button>
        </div>

        <a class="fab" href="{{ $me ? route('reports.create') : route('login') }}" aria-label="Report flooding"><i class="i" data-i="plus"></i></a>
        <div class="posts" id="posts">
            @foreach ($reports as $r)
                @php
                    [$f, $ic] = AgosData::typeIcon($r->incident_type);
                    $risk = $r->risk();
                    $col = $riskColor[$risk];
                    $mine = $me && $me->user_id === $r->user_id;
                    $name = $r->user?->full_name ?? 'Deleted account';
                    $depth = $r->depth_cm;
                    $level = $depth === null ? '' : ($depth < 5 ? '' : ucfirst(AgosData::depthLabel($depth)));
                    $depthText = $depth === null ? '' : ($depth < 5 ? 'Dry' : $depth.' cm');
                    $vehicle = $depth === null ? '' : ($depth <= 20 ? 'All vehicles can pass' : ($depth <= 45 ? 'Too deep for cars' : 'No vehicles should pass'));
                @endphp
                <article class="card tight post" data-t="{{ $r->created_at->timestamp }}" data-up="{{ $r->up_votes }}" data-down="{{ $r->down_votes }}"
                         data-depth="{{ $depth ?? -1 }}" data-risk="{{ $risk }}" data-type="{{ $r->incident_type }}" data-brgy="{{ $r->barangay }}"
                         data-verified="{{ $r->status === 'verified' ? 1 : 0 }}"
                         data-text="{{ Str::lower($name.' '.$r->note.' '.$r->barangay.' '.$r->incident_type) }}">
                    <div class="top">
                        @php $ac = $avatar[abs(crc32((string) $r->user_id)) % count($avatar)]; @endphp
                        <span class="uavatar" style="background:linear-gradient(180deg,color-mix(in srgb,{{ $ac }},#fff 22%),{{ $ac }})">{{ $r->user?->initials() ?? '?' }}</span>
                        <div class="grow">
                            <b>{{ $name }}</b>
                            <span class="small muted">{{ AgosData::timeAgo($r->created_at) }} · Brgy. {{ $r->barangay }}
                                <i class="i st {{ $r->status === 'verified' ? 'ok' : '' }}" data-i="{{ $r->status === 'verified' ? 'checkmark_seal_fill' : 'clock' }}"
                                   title="{{ $r->status === 'verified' ? 'Verified by Admin' : 'Not verified yet' }}"></i></span>
                        </div>
                        @if ($mine)
                            <form class="inline" method="POST" action="{{ route('reports.destroy', $r) }}" data-confirm-title="Delete your report?"
                                  data-confirm-text="It disappears from the map and the feed for everyone. You can restore it from Settings > Trash for 30 days.">
                                @csrf @method('DELETE')
                                <button class="iconbtn" type="submit" title="Delete your report"><i class="i" data-i="trash"></i></button>
                            </form>
                        @endif
                    </div>
                    @if ($r->note)<p class="note">{{ $r->note }}</p>@endif
                    <div class="tags">
                        <span class="chip"><i class="{{ $f }}" data-i="{{ $ic }}"></i>{{ ucfirst($r->incident_type) }}</span>
                        <span class="chip" style="background:{{ $col }}2e;color:{{ $col }}"><span class="dot {{ $risk }}"></span>{{ $riskWord[$risk] }} risk</span>
                    </div>
                    @if ($r->photo_url)
                        <div class="photo">
                            <img src="{{ $r->photo_url }}" alt="Flood photo in Brgy. {{ $r->barangay }}" loading="lazy" tabindex="0"
                                 data-zoom data-name="{{ $name }}" data-when="Photo {{ AgosData::timeAgo($r->photo_taken_at) }} · Brgy. {{ $r->barangay }}">
                            <span class="age">Photo {{ AgosData::timeAgo($r->photo_taken_at) }}</span>
                            @if ($depth !== null)
                                <div class="shade"></div>
                                <div class="headline on-photo"><b>{{ $depthText }}</b><span>{{ $level }}</span></div>
                            @endif
                        </div>
                    @endif
                    @if ($depth !== null)
                        <div class="depthinfo">
                            @if (! $r->photo_url)<div class="headline"><b>{{ $depthText }}</b><span>{{ $level }}</span></div>@endif
                            {{-- _DepthBar: 0-150 cm, body marks as ticks. --}}
                            <div class="depthbar"><span class="fill" style="width:{{ max(4, min(100, $depth / 150 * 100)) }}%;background:{{ $col }}"></span>
                                @foreach ([12 => 'Ankle', 48 => 'Knee', 92 => 'Waist', 122 => 'Chest'] as $at => $mark)
                                    <span class="mark {{ $depth >= $at ? 'on' : '' }}" style="left:{{ $at / 150 * 100 }}%"><em>{{ $mark }}</em></span>
                                @endforeach
                            </div>
                            <div class="vehicle"><i class="i" data-i="car_detailed"></i>{{ $vehicle }}</div>
                        </div>
                    @endif
                    {{-- "Still flooded?": only your own answer, kept for this visit (memory only, like the app). --}}
                    <div class="still" data-guest="{{ $me ? 0 : 1 }}">
                        <span class="q">{{ $depth !== null ? 'Still flooded?' : 'Still there?' }}</span>
                        <button type="button" data-a="yes">Yes</button><button type="button" data-a="no">No</button>
                        <span class="done" hidden><i class="i" data-i="checkmark_circle_fill"></i><span></span><button type="button" class="undo">Undo</button></span>
                    </div>
                    <div class="bottom">
                        @unless ($mine)
                            @foreach ([1 => ['hand_thumbsup_fill', $r->up_votes], -1 => ['hand_thumbsdown_fill', $r->down_votes]] as $v => [$icon, $n])
                                <form class="inline" method="POST" action="{{ $me ? route('reports.vote', $r) : route('login') }}">
                                    @csrf
                                    <input type="hidden" name="vote" value="{{ $v }}">
                                    <button class="vote {{ (int) ($myVotes[$r->report_id] ?? 0) === $v ? 'on' : '' }}" type="{{ $me ? 'submit' : 'button' }}"
                                            @if (! $me) onclick="location.href='{{ route('login') }}'" title="Log in to vote" @endif>
                                        <i class="i" data-i="{{ $icon }}"></i> {{ $n }}
                                    </button>
                                </form>
                            @endforeach
                        @endunless
                        <a class="mapbtn" href="{{ route('map') }}#report-{{ $r->report_id }}"><i class="ai" data-i="map_pin_fill"></i>Map</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var posts = Array.prototype.slice.call(document.querySelectorAll('.post')), box = document.getElementById('posts');
        var st = { sort: 'newest', risk: '', type: '', mine: false, verified: false, q: '' }, myBrgy = @js($myBrgy);
        var filters = document.getElementById('filters'), fbtn = document.getElementById('filterbtn');
        function active() { return (st.sort !== 'newest') + !!st.risk + !!st.type + st.mine + st.verified; }
        function apply() {
            var q = st.q.trim().toLowerCase(), shown = posts.filter(function (p) {
                var d = p.dataset;
                return (!q || d.text.indexOf(q) >= 0) && (!st.risk || d.risk === st.risk) && (!st.type || d.type === st.type)
                    && (!st.mine || d.brgy === myBrgy) && (!st.verified || d.verified === '1');
            });
            var key = { newest: function (p) { return -p.dataset.t; }, oldest: function (p) { return +p.dataset.t; },
                liked: function (p) { return -p.dataset.up; }, disliked: function (p) { return -p.dataset.down; },
                deepest: function (p) { return -p.dataset.depth; } }[st.sort];
            shown.sort(function (a, b) { return key(a) - key(b) || b.dataset.t - a.dataset.t; });
            posts.forEach(function (p) { p.hidden = shown.indexOf(p) < 0; });
            shown.forEach(function (p) { box.appendChild(p); });
            var n = active(), filtered = n > 0 || !!q;
            fbtn.classList.toggle('on', n > 0 || !filters.hidden);
            fbtn.querySelector('span').textContent = n > 0 ? 'Filter · ' + n : 'Filter';
            document.getElementById('count').textContent = shown.length + (shown.length === 1 ? ' report' : ' reports') + (filtered ? ' found' : ' · newest first');
            document.getElementById('clearall').hidden = !filtered;
            var empty = document.getElementById('empty');
            if (empty) {
                empty.hidden = shown.length > 0 @if ($failed) || true @endif;
                document.getElementById('empty-title').textContent = filtered ? 'Nothing matches' : 'No reports yet';
                document.getElementById('empty-text').textContent = filtered ? 'Try other words, or clear the filters.' : 'Dry streets, or nobody has posted yet.';
                document.getElementById('empty-clear').hidden = !filtered;
            }
        }
        function clear() {
            st = { sort: 'newest', risk: '', type: '', mine: false, verified: false, q: '' };
            document.getElementById('q').value = '';
            document.querySelectorAll('.chips[data-group] .selchip').forEach(function (c) { c.classList.toggle('on', c.dataset.v === (c.parentNode.dataset.group === 'sort' ? 'newest' : '')); });
            document.querySelectorAll('.selchip.toggle').forEach(function (c) { c.classList.remove('on'); });
            apply();
        }
        fbtn.addEventListener('click', function () { filters.hidden = !filters.hidden; fbtn.setAttribute('aria-expanded', !filters.hidden); apply(); });
        document.getElementById('q').addEventListener('input', function (e) { st.q = e.target.value; apply(); });
        document.querySelectorAll('.chips[data-group]').forEach(function (g) {
            g.addEventListener('click', function (e) {
                var c = e.target.closest('.selchip'); if (!c) return;
                var k = g.dataset.group, v = c.dataset.v;
                if (k !== 'sort' && st[k] === v && v) v = ''; // tap again = off
                st[k] = v;
                g.querySelectorAll('.selchip').forEach(function (x) { x.classList.toggle('on', x.dataset.v === v); });
                apply();
            });
        });
        document.querySelectorAll('.selchip.toggle').forEach(function (c) {
            c.addEventListener('click', function () { st[c.dataset.flag] = c.classList.toggle('on'); apply(); });
        });
        document.getElementById('clearall').addEventListener('click', clear);
        document.getElementById('empty-clear').addEventListener('click', clear);

        // Still flooded? Yes / No
        document.querySelectorAll('.still').forEach(function (s) {
            var done = s.querySelector('.done');
            function show(a) {
                s.classList.toggle('answered', !!a);
                done.hidden = !a;
                if (a) done.querySelector('span').textContent = a === 'yes' ? "You said it's still flooded. Thanks." : "You said the water's gone. Thanks.";
            }
            s.addEventListener('click', function (e) {
                var b = e.target.closest('button'); if (!b) return;
                if (s.dataset.guest === '1') { location.href = @js(route('login')); return; }
                show(b.classList.contains('undo') ? null : b.dataset.a);
            });
        });
    })();
</script>
@endpush
