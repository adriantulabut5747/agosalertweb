{{-- _Greeting in weather_tab.dart: date, greeting in English and
     Kapampangan (Manila hours, same as the landing page), and the user's
     barangay chip on the right once one is picked. --}}
<div class="greet">
    <div class="grow">
        <div class="date">{{ strtoupper(now()->format('D, M j')) }}</div>
        <div class="hello">{{ $hello }}</div>
        <div class="kap" title="Kapampangan for &quot;{{ $hello }}&quot;">{{ $kap }}</div>
    </div>
    @if ($myBarangay)
        <span class="brgy-chip" title="My barangay">
            <i class="i" data-i="placemark_fill"></i>Brgy. {{ $myBarangay }}
        </span>
    @endif
</div>
