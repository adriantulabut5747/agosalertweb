{{-- The bell's sheet (lib/screens/notifications.dart): "Notifications",
     unread count + Mark all read, category chips, Today / Earlier wells;
     tap an alert = its details (and it's marked read). --}}
@php
    $cats = ['flood' => ['Flood', 'ai', 'flood'], 'weather' => ['Weather', 'i', 'cloud_bolt_rain_fill'],
        'road' => ['Road', 'i', 'car_fill'], 'shelter' => ['Shelter', 'i', 'house_alt_fill']];
    $sevColor = ['high' => '#ff453a', 'moderate' => '#ff9f0a', 'normal' => '#30d158'];
    $sevWord = ['high' => 'High', 'moderate' => 'Moderate', 'normal' => 'Normal'];
    $unread = $alerts->whereNotIn('alert_id', $readAlerts)->count();
@endphp
<dialog class="sheet wide notif" id="notif">
    <div class="nlist" id="nlist">
        <div class="nhead">
            <div class="grow"><h3>Notifications</h3>
                <p id="nsub">{{ $unread ? $unread.' unread · Mabalacat City' : "You're all caught up" }}</p></div>
            <button type="button" class="linkbtn" id="nall" @if (! $unread) hidden @endif>Mark all read</button>
        </div>
        @if ($alerts->isEmpty())
            <div class="nnothing">
                <div class="say"><div class="who">TUGAK</div><b>No alerts right now</b><p>When the Admin posts a flood, weather, road or shelter alert, it shows up here.</p></div>
                <img src="{{ asset('img/tugak/hero.webp') }}" alt="" width="110">
            </div>
        @else
            <div class="nchips">
                <button type="button" class="selchip on" data-cat="">All&nbsp; {{ $alerts->count() }}</button>
                @foreach ($cats as $k => [$label, $font, $icon])
                    <button type="button" class="selchip" data-cat="{{ $k }}"><i class="{{ $font }}" data-i="{{ $icon }}"></i>{{ $label }}&nbsp; {{ $alerts->where('category', $k)->count() }}</button>
                @endforeach
            </div>
            <p class="nnone" hidden>No notifications here.</p>
            @foreach (['Today' => $alerts->filter(fn ($a) => $a->created_at->gt(now()->subDay())), 'Earlier' => $alerts->filter(fn ($a) => $a->created_at->lte(now()->subDay()))] as $label => $group)
                @if ($group->isNotEmpty())
                    <div class="ngroup">
                        <div class="fieldlabel">{{ $label }}</div>
                        <div class="nwell">
                            @foreach ($group as $a)
                                @php [, $font, $icon] = $cats[$a->category] ?? $cats['flood']; $col = $sevColor[$a->severity] ?? '#8e8e93'; @endphp
                                <button type="button" class="nrow {{ in_array($a->alert_id, $readAlerts) ? '' : 'unread' }}" data-id="{{ $a->alert_id }}" data-cat="{{ $a->category }}">
                                    <span class="sq" style="background:{{ $col }}2e;color:{{ $col }}"><i class="{{ $font }}" data-i="{{ $icon }}"></i></span>
                                    <span class="grow">
                                        <span class="t"><b>{{ $a->title }}</b><i class="udot"></i><small>{{ \App\Support\AgosData::timeAgo($a->created_at) }}</small></span>
                                        <span class="s">{{ $a->summary }}</span>
                                        <span class="m"><b style="color:{{ $col }}">{{ $sevWord[$a->severity] ?? '' }}</b>&nbsp; · &nbsp;{{ $a->area }}
                                            @if ($me?->barangay && str_ends_with($a->area, $me->barangay))<span class="chip blue">Your area</span>@endif</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
    @foreach ($alerts as $a)
        @php [$catLabel] = $cats[$a->category] ?? $cats['flood']; $col = $sevColor[$a->severity] ?? '#8e8e93'; @endphp
        <div class="ndetail" data-id="{{ $a->alert_id }}" hidden>
            <button type="button" class="linkbtn nback"><i class="i" data-i="chevron_left"></i>All notifications</button>
            <div class="sev" style="color:{{ $col }}"><i style="background:{{ $col }}"></i>{{ $sevWord[$a->severity] ?? '' }} severity · {{ $catLabel }}</div>
            <h3>{{ $a->title }}</h3>
            <div class="meta"><span><i class="i" data-i="placemark"></i>{{ $a->area }}</span><span><i class="i" data-i="clock"></i>{{ \App\Support\AgosData::timeAgo($a->created_at) }}</span>
                <span><i class="i" data-i="checkmark_seal"></i>Admin (unofficial demo)</span></div>
            <p class="details">{{ $a->details }}</p>
            @if ($a->steps())
                <div class="fieldlabel">What to do</div>
                @foreach ($a->steps() as $i => $step)
                    <div class="step"><span style="background:{{ $col }}26;color:{{ $col }}">{{ $i + 1 }}</span><p>{{ $step }}</p></div>
                @endforeach
            @endif
            <a class="btn white wide" href="{{ route('map') }}" style="margin-top:14px">View on map <i class="ai" data-i="map_pin_fill"></i></a>
        </div>
    @endforeach
</dialog>
<script>
    (function () {
        var d = document.getElementById('notif'), list = document.getElementById('nlist'), token = @js(csrf_token());
        function count() {
            var n = d.querySelectorAll('.nrow.unread').length, badge = document.querySelector('.round .badge');
            document.getElementById('nsub').textContent = n ? n + ' unread · Mabalacat City' : "You're all caught up";
            document.getElementById('nall').hidden = !n;
            if (badge) { badge.textContent = n; badge.hidden = !n; }
        }
        function markRead(ids) {
            if (!ids.length) return;
            ids.forEach(function (id) { var r = d.querySelector('.nrow[data-id="' + id + '"]'); if (r) r.classList.remove('unread'); });
            count();
            fetch(@js(route('alerts.read')), { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ ids: ids }) }).catch(function () {});
        }
        function show(id) {
            list.hidden = !!id;
            d.querySelectorAll('.ndetail').forEach(function (x) { x.hidden = x.dataset.id !== String(id); });
        }
        d.addEventListener('click', function (e) {
            if (e.target === d) return d.close();
            var row = e.target.closest('.nrow');
            if (row) { markRead([+row.dataset.id]); show(row.dataset.id); return; }
            if (e.target.closest('.nback')) return show(null);
            if (e.target.closest('#nall')) return markRead(Array.prototype.map.call(d.querySelectorAll('.nrow.unread'), function (r) { return +r.dataset.id; }));
            var chip = e.target.closest('.nchips .selchip');
            if (chip) {
                d.querySelectorAll('.nchips .selchip').forEach(function (c) { c.classList.toggle('on', c === chip); });
                var shown = 0;
                d.querySelectorAll('.nrow').forEach(function (r) { r.hidden = chip.dataset.cat && r.dataset.cat !== chip.dataset.cat; if (!r.hidden) shown++; });
                d.querySelectorAll('.ngroup').forEach(function (g) { g.hidden = !g.querySelector('.nrow:not([hidden])'); });
                d.querySelector('.nnone').hidden = shown > 0;
            }
        });
        document.querySelectorAll('[data-open-notif]').forEach(function (b) {
            b.addEventListener('click', function (e) { e.preventDefault(); show(null); d.showModal(); });
        });
    })();
</script>
