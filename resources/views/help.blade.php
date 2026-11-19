@extends('layouts.app')
@section('title', 'Help')

@push('head')
<style>
    dialog.sheet { width: min(560px, calc(100vw - 32px)); max-height: 90vh; padding: 0; border: 1px solid var(--edge-lit);
        border-radius: 28px; background: #12131b; color: var(--text); }
    dialog.sheet::backdrop { background: rgba(0, 0, 0, .55); backdrop-filter: blur(4px); }
    dialog.sheet .in { padding: 24px 24px 22px; }
    dialog.sheet .x { position: absolute; right: 16px; top: 14px; background: none; border: 0; color: var(--muted); font-size: 20px; cursor: pointer; }
</style>
@endpush

@section('content')
    <h1 class="page-title">Help</h1>
    <p class="page-sub">Get help fast, and be ready before the rain</p>

    @if ($mine)
        <div class="card row" style="margin-bottom:20px">
            <span class="tile red"><i class="ai" data-i="boat"></i></span>
            <div class="grow"><b>Rescue request #{{ $mine->request_id }}: {{ $mine->statusLabel() }}</b>
                <div class="small muted">Demo: no one is watching yet. In danger? Call 911. Updated {{ $mine->updated_at->diffForHumans() }}.</div></div>
        </div>
    @endif

    <div class="help">
        {{-- ONE focal point: the SOS, the only red on the page. --}}
        <div class="card sos">
            <a class="btnsos" href="tel:911" onclick="return confirm('Call 911 now?')">SOS</a>
            <h3>Tap to call 911</h3>
            <p class="small muted" style="margin:0">For danger to life. We ask before calling.</p>
            <div class="pair">
                <button class="btn gray" type="button" onclick="imSafe()"><i class="i" data-i="checkmark_shield_fill" style="color:var(--safe)"></i> I'm safe</button>
                <button class="btn gray" type="button" onclick="document.getElementById('rescue').showModal()"><i class="ai" data-i="boat"></i> Request rescue</button>
            </div>
        </div>

        <div>
            <div class="section"><h2>Hotlines</h2></div>
            <div class="card list" style="padding:6px 18px">
                @foreach ($hotlines as [$name, $number, $kind])
                    <div class="it">
                        <span class="tile {{ $number === '911' ? 'red' : 'gray' }}"><i class="i" data-i="shield_fill"></i></span>
                        <div class="grow"><b>{{ $name }}</b><span class="small muted">{{ $number }}</span></div>
                        <a class="call" href="tel:{{ preg_replace('/[^0-9+]/', '', $number) }}" onclick="return confirm(@js('Call '.$name.' ('.$number.')?'))" aria-label="Call {{ $name }}"><i class="i" data-i="phone_fill"></i></a>
                    </div>
                @endforeach
            </div>

            <div class="section" style="margin-top:22px"><h2>Evacuation centers</h2>@if ($sample)<span class="sample">SAMPLE</span>@endif</div>
            <div class="card list" style="padding:6px 18px">
                @forelse ($centers as $c)
                    <div class="it">
                        <span class="tile {{ $c->status === 'open' ? 'indigo' : 'gray' }}"><i class="i" data-i="house_alt_fill"></i></span>
                        <div class="grow"><b>{{ $c->name }}</b><span class="small muted">Brgy. {{ $c->barangay }} · {{ ucfirst($c->status) }}</span></div>
                        @if ($c->status === 'open')
                            <a class="btn gray sm" target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination={{ $c->latitude }},{{ $c->longitude }}">
                                <i class="i" data-i="arrow_up_right_diamond_fill" style="color:var(--blue-text)"></i> Directions</a>
                        @endif
                    </div>
                @empty
                    <p class="muted">No evacuation centers listed.</p>
                @endforelse
            </div>
            <p class="tiny muted" style="margin-top:14px">Hotline numbers are from Mabalacat City government channels. Check them locally before you rely on them.</p>
        </div>
    </div>

    <div class="section" style="margin-top:34px"><h2>Be ready</h2></div>
    <div class="intro" style="align-items:center">
        <img src="{{ asset('img/tugak/handset.webp') }}" alt="" style="width:90px">
        <div class="say grow"><div class="who">TUGAK</div><p>Do the Before list today, while it's dry. Once the water rises, there's no time to read.</p></div>
    </div>
    <div class="card list" style="padding:6px 18px">
        @foreach ([
            ['phone_fill', 'Save the hotlines now', 'Put CDRRMO and your barangay hall in your contacts before the rain starts.'],
            ['house_alt_fill', 'Know your evacuation center', 'Find the nearest one and walk the route once while the roads are dry.'],
            ['umbrella_fill', 'Pack a go-bag', 'Water, food for 3 days, flashlight, power bank, medicines, copies of IDs, in a waterproof bag.'],
            ['exclamationmark_triangle_fill', 'Turn around, don\'t drown', 'Never walk or drive through flood water: 15 cm of moving water can knock you down.'],
        ] as [$icon, $title, $text])
            <div class="it"><span class="tile gray"><i class="i" data-i="{{ $icon }}"></i></span>
                <div class="grow"><b>{{ $title }}</b><span class="small muted">{{ $text }}</span></div></div>
        @endforeach
    </div>

    {{-- The rescue request "sheet" (FR-G8). --}}
    <dialog class="sheet" id="rescue">
        <button class="x" type="button" onclick="this.closest('dialog').close()" aria-label="Close"><i class="i" data-i="xmark"></i></button>
        <form class="in" method="POST" action="{{ route('rescue.store') }}">
            @csrf
            <div class="row"><span class="tile red" style="width:48px;height:48px;font-size:24px"><i class="ai" data-i="boat"></i></span>
                <h3 class="page-title" style="font-size:20px">Request rescue</h3></div>
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
    // "I'm safe": the share menu on phones, else copy (like check_in.dart).
    function imSafe() {
        var who = @js($me ? $me->full_name.', Brgy. '.$me->barangay : 'Sent from AgosAlert');
        var text = "I'm safe. — " + who + " · " + new Date().toLocaleString();
        if (navigator.share) { navigator.share({ text: text }).catch(function () {}); }
        else { navigator.clipboard.writeText(text).then(function () { alert('Copied. Paste it to your family:\n\n' + text); }); }
    }
</script>
@endpush
