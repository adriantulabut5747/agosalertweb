<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AgosAlert') · AgosAlert</title>
    <meta name="theme-color" content="#05060c">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    {{-- The app's own font files (Inter + Inter Display, assets/fonts). --}}
    <link rel="preload" href="{{ asset('fonts/Inter-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="{{ asset('fonts/InterDisplay-Bold.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=8">
    @stack('head')
</head>
<body class="@yield('body_class')">
    <div class="night" aria-hidden="true"><i id="stars"></i></div>

    @php
        $tabs = [
            // route, label, [outline, filled] icon (home_shell.dart _items), font, phone nav image
            ['home', 'Weather', ['cloud_sun', 'cloud_sun_fill'], 'i', 'nav_weather'],
            ['map', 'Map', ['map_pin', 'map_pin_fill'], 'ai', 'nav_map'],
            ['reports.index', 'Reports', ['news', 'news_solid'], 'i', 'nav_reports'],
            ['help', 'Help', ['lifebuoy', 'lifebuoy_fill'], 'ai', 'nav_help'],
        ];
        $on = fn ($r) => request()->routeIs($r === 'reports.index' ? 'reports.*' : $r);
    @endphp

    {{-- Log in / sign up / keep account set @section('bare'): no top bar
         or phone nav there, like the app's full-screen LoginScreen. --}}
    @sectionMissing('bare')
    <header class="bar">
        <a class="logo" href="{{ route('home') }}" aria-label="AgosAlert home">
            <img src="{{ asset('img/logo_wordmark_dark.png') }}" alt="agosalert." width="146" height="33">
        </a>
        {{-- Apple segmented control, like the app's tablet/desktop nav. Each
             tab is its own page: a website, so no swiping between them. --}}
        <nav class="seg" aria-label="Main">
            @foreach ($tabs as [$route, $label, $icon, $font])
                <a href="{{ route($route) }}" class="{{ $on($route) ? 'on' : '' }}">
                    <i class="{{ $font }}" data-i="{{ $icon[$on($route) ? 1 : 0] }}"></i>{{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="right">
            {{-- The bell opens the Notifications sheet; the number = unread alerts. --}}
            <a class="round" href="{{ route('alerts') }}" aria-label="Notifications" data-open-notif>
                <i class="i" data-i="bell"></i>
                <span class="badge" @if (! ($alertCount ?? 0)) hidden @endif>{{ $alertCount ?? 0 }}</span>
            </a>
            <span class="vline"></span>
            <details class="menu">
                <summary>
                    @if ($me)
                        <span class="avatar" style="background: hsl({{ crc32($me->user_id) % 360 }} 65% 50%)">{{ $me->initials() }}</span>
                        <span class="name">{{ $me->full_name }}</span>
                    @else
                        <span class="avatar"><i class="i" data-i="person_fill"></i></span>
                        <span class="name">Guest</span>
                    @endif
                    <i class="i" data-i="chevron_down"></i>
                </summary>
                <div class="panel">
                    <div class="who">
                        @if ($me)
                            <span class="avatar lg" style="background: hsl({{ crc32($me->user_id) % 360 }} 65% 50%)">{{ $me->initials() }}</span>
                            <div class="grow"><b>{{ $me->full_name }}</b><div class="small muted" style="overflow:hidden;text-overflow:ellipsis">{{ $me->email }}</div></div>
                        @else
                            <span class="avatar lg"><i class="i" data-i="person_fill"></i></span>
                            <div class="grow"><b>Guest</b><div class="small muted">Browsing as a guest</div></div>
                        @endif
                    </div>
                    <hr>
                    <a href="{{ route('alerts') }}" data-open-notif><i class="i" data-i="bell_fill"></i>Notifications</a>
                    @if ($me)
                        <a href="{{ route('settings') }}"><i class="i" data-i="gear_alt_fill"></i>Settings</a>
                    @endif
                    @if ($me?->isAdmin())
                        <a href="{{ route('admin.index') }}"><i class="i" data-i="shield_lefthalf_fill"></i>Admin</a>
                    @endif
                    <a href="{{ asset('privacy.html') }}"><i class="i" data-i="info_circle_fill"></i>Privacy and terms</a>
                    <hr>
                    @if ($me)
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="danger" type="submit"><i class="i" data-i="square_arrow_right"></i>Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"><i class="i" data-i="person_crop_circle_fill"></i>Log in</a>
                    @endif
                </div>
            </details>
        </div>
    </header>
    @endif

    {{-- Log in / sign up show errors as Tugak's line instead (auth-hello). --}}
    @if (session('error'))
        @sectionMissing('bare')
        <div class="flash"><div class="error">{{ session('error') }}</div></div>
        @endif
    @endif
    {{-- The app's snack bar: a 400 px pill at the bottom, gone after 4 s
         (8 s with Undo, to give time to tap it). --}}
    @if (session('ok'))
        <div class="toast" id="toast" role="status">
            <span>{{ session('ok') }}</span>
            @if (session('undo'))
                <form method="POST" action="{{ route('reports.restore', session('undo')) }}">
                    @csrf <button type="submit">Undo</button>
                </form>
            @endif
        </div>
        <script>setTimeout(function () { document.getElementById('toast').classList.add('gone'); }, {{ session('undo') ? 8000 : 4000 }});</script>
    @endif

    {{-- The app's confirm sheet (showAppSheet: centered popup on wide
         screens). Any form with data-confirm-title asks here first. --}}
    <dialog class="sheet" id="confirm">
        <span class="badge-lg red"><i class="i" data-i="trash_fill"></i></span>
        <h3 id="confirm-title"></h3>
        <p id="confirm-text"></p>
        <button class="btn red wide" id="confirm-ok" type="button"></button>
        <button class="btn gray wide" type="button" onclick="this.closest('dialog').close()">Cancel</button>
    </dialog>

    <main class="@yield('main_class')">
        @yield('content')
    </main>

    {{-- No site-wide footer: like the app, each page names its own sources
         (Weather: "Weather by Open-Meteo · Unofficial demo", the map: its
         attribution control). --}}

    {{-- Phones: the floating glass pill (drawn icons from the app). --}}
    @sectionMissing('bare')
    <nav class="pill" aria-label="Main">
        @foreach ($tabs as [$route, $label, , , $img])
            <a href="{{ route($route) }}" class="{{ $on($route) ? 'on' : '' }}" aria-label="{{ $label }}">
                <img src="{{ asset("img/nav/$img.png") }}" alt="">
                @if ($on($route)){{ $label }}@endif
            </a>
        @endforeach
    </nav>
    @endif

    <script>
        // Still stars, painted once (no animation loop: like the app).
        (function () {
            var w = Math.max(screen.width, innerWidth), h = Math.max(screen.height, innerHeight), n = innerWidth < 600 ? 60 : 160, out = [];
            for (var k = 0; k < n; k++) {
                out.push(Math.round(Math.random() * w) + 'px ' + Math.round(Math.random() * h) + 'px rgba(255,255,255,' + (0.15 + Math.random() * 0.45).toFixed(2) + ')');
            }
            document.getElementById('stars').style.boxShadow = out.join(',');
        })();
        document.addEventListener('submit', function (e) {
            var f = e.target, d = document.getElementById('confirm');
            if (!f.dataset.confirmTitle || f.dataset.confirmed) return;
            e.preventDefault();
            document.getElementById('confirm-title').textContent = f.dataset.confirmTitle;
            document.getElementById('confirm-text').textContent = f.dataset.confirmText || '';
            var ok = document.getElementById('confirm-ok');
            ok.textContent = f.dataset.confirmButton || 'Delete';
            ok.onclick = function () { f.dataset.confirmed = '1'; d.close(); f.requestSubmit(); };
            d.showModal();
        });
        // Close the avatar menu when clicking elsewhere.
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details.menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.open = false; });
        });
    </script>
    @sectionMissing('bare')
        @include('partials.notifications')
        @include('partials.photo-viewer')
    @endif
    @stack('scripts')
</body>
</html>
