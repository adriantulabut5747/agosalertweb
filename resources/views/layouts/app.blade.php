<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'AgosAlert') · AgosAlert</title>
    <meta name="theme-color" content="#05060c">
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300..800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
    @stack('head')
</head>
<body>
    <div class="night" aria-hidden="true"><i id="stars"></i></div>

    @php
        $tabs = [
            ['home', 'Weather', 'cloud_sun_fill', 'i', 'nav_weather'],
            ['map', 'Map', 'placemark_fill', 'i', 'nav_map'],
            ['reports.index', 'Reports', 'doc_text_fill', 'i', 'nav_reports'],
            ['help', 'Help', 'lifebuoy', 'ai', 'nav_help'],
        ];
        $on = fn ($r) => request()->routeIs($r === 'reports.index' ? 'reports.*' : $r);
    @endphp

    <header class="bar">
        <a class="logo" href="{{ route('home') }}" aria-label="AgosAlert home">
            <img src="{{ asset('img/logo_wordmark_dark.png') }}" alt="agosalert." width="146" height="33">
        </a>
        {{-- Apple segmented control, like the app's tablet/desktop nav. Each
             tab is its own page: a website, so no swiping between them. --}}
        <nav class="seg" aria-label="Main">
            @foreach ($tabs as [$route, $label, $icon, $font])
                <a href="{{ route($route) }}" class="{{ $on($route) ? 'on' : '' }}">
                    <i class="{{ $font }}" data-i="{{ $icon }}"></i>{{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="right">
            <a class="round" href="{{ route('alerts') }}" aria-label="Alerts">
                <i class="i" data-i="bell"></i>
                @if (($alertCount ?? 0) > 0)<span class="badge">{{ $alertCount }}</span>@endif
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
                    <a href="{{ route('alerts') }}"><i class="i" data-i="bell_fill"></i>Alerts</a>
                    @if ($me?->isAdmin())
                        <a href="{{ route('admin.index') }}"><i class="i" data-i="shield_lefthalf_fill"></i>Admin</a>
                    @endif
                    <a href="https://adriantulabut5747.github.io/AgosAlert/privacy.html"><i class="i" data-i="info_circle_fill"></i>Privacy and terms</a>
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

    @if (session('ok') || session('error'))
        <div class="flash">
            @if (session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
            @if (session('error'))<div class="error">{{ session('error') }}</div>@endif
        </div>
    @endif

    <main class="@yield('main_class')">
        @yield('content')
    </main>

    <footer class="foot">
        AgosAlert · Unofficial demo, not from the Mabalacat City government · BSIT capstone project ·
        Weather: Open-Meteo.com · Map: © OpenStreetMap contributors, Esri
    </footer>

    {{-- Phones: the floating glass pill (drawn icons from the app). --}}
    <nav class="pill" aria-label="Main">
        @foreach ($tabs as [$route, $label, , , $img])
            <a href="{{ route($route) }}" class="{{ $on($route) ? 'on' : '' }}" aria-label="{{ $label }}">
                <img src="{{ asset("img/nav/$img.png") }}" alt="">
                @if ($on($route)){{ $label }}@endif
            </a>
        @endforeach
    </nav>

    <script>
        // Still stars, painted once (no animation loop: like the app).
        (function () {
            var w = Math.max(screen.width, innerWidth), h = Math.max(screen.height, innerHeight), n = innerWidth < 600 ? 60 : 160, out = [];
            for (var k = 0; k < n; k++) {
                out.push(Math.round(Math.random() * w) + 'px ' + Math.round(Math.random() * h) + 'px rgba(255,255,255,' + (0.15 + Math.random() * 0.45).toFixed(2) + ')');
            }
            document.getElementById('stars').style.boxShadow = out.join(',');
        })();
        // Close the avatar menu when clicking elsewhere.
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details.menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.open = false; });
        });
    </script>
    @stack('scripts')
</body>
</html>
