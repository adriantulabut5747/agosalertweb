{{-- _shelterRow in assistance_tab.dart: icon, name, barangay + status,
     and Directions for open centers only. --}}
<div class="shelter">
    @php $km = \App\Support\AgosData::distanceKm($me?->barangay, $c->latitude, $c->longitude); @endphp
    <span class="glyph-tile"><i class="i" data-i="{{ \App\Support\AgosData::centerIcon($c->name) }}"></i></span>
    <span class="grow"><b>{{ $c->name }}</b><span class="small muted">Brgy. {{ $c->barangay }} · {{ ucfirst($c->status) }}@if ($km !== null) · {{ number_format($km, 1) }} km @endif</span></span>
    @if ($c->status === 'open')
        <a class="dir" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $c->latitude }},{{ $c->longitude }}">
            <i class="i" data-i="arrow_up_right_diamond_fill"></i>Directions</a>
    @endif
</div>
