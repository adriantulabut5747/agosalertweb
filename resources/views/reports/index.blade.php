@extends('layouts.app')
@section('title', 'Reports')
@section('main_class', 'narrow')
@use('App\Support\AgosData')

@section('content')
    <h1 class="page-title">Reports</h1>
    <p class="page-sub">What people in Mabalacat are seeing</p>

    {{-- Tugak (phone pose) + his bubble + the report button, like the app. --}}
    <div class="intro">
        <img src="{{ asset('img/tugak/phone.webp') }}" alt="Tugak holding a phone">
        <div class="grow">
            <div class="say" style="margin-bottom:12px"><div class="who">TUGAK</div>
                <p>See water on a street? Post one photo and pin the spot. That's all I need.</p></div>
            @if ($me)
                <a class="btn" href="{{ route('reports.create') }}">Report flooding <i class="i" data-i="plus"></i></a>
            @else
                <a class="btn" href="{{ route('login') }}">Log in to report <i class="i" data-i="arrow_right"></i></a>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('reports.index') }}">
        <div class="searchrow">
            <div class="field"><i class="i" data-i="search"></i>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search notes, barangays, types"></div>
            <button class="btn gray" type="button" onclick="document.getElementById('filters').classList.toggle('open')">
                <i class="i" data-i="slider_horizontal_3"></i> Filter</button>
        </div>
        <div id="filters" class="filters {{ request()->hasAny(['barangay', 'type']) ? 'open' : '' }}">
            <select name="barangay" onchange="this.form.submit()">
                <option value="">All barangays</option>
                @foreach ($barangays as $b)<option @selected(request('barangay') === $b)>{{ $b }}</option>@endforeach
            </select>
            <select name="type" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach ($types as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>@endforeach
            </select>
        </div>
    </form>

    @if ($failed)
        <div class="card empty">Couldn't load the reports. Refresh in a minute.</div>
    @else
        <p class="small muted" style="margin:4px 0 12px">{{ $reports->count() }} {{ Str::plural('report', $reports->count()) }} · newest first</p>
        @if ($reports->isEmpty())
            <div class="card empty">
                <img src="{{ asset('img/tugak/think.webp') }}" alt="" width="90"><br>
                No reports{{ request()->hasAny(['q', 'barangay', 'type']) ? ' match that' : ' right now' }}.
            </div>
        @endif
    @endif

    <div class="stack">
        @foreach ($reports as $r)
            @php [$f, $ic] = AgosData::typeIcon($r->incident_type); $risk = $r->risk(); @endphp
            <article class="card tight post">
                <div class="top">
                    <span class="avatar lg" style="background: hsl({{ crc32((string) $r->user_id) % 360 }} 65% 50%)">{{ $r->user?->initials() ?? '?' }}</span>
                    <div class="grow">
                        <b>{{ $r->user?->full_name ?? 'Deleted account' }}</b>
                        <span class="small muted">{{ $r->created_at->shortRelativeDiffForHumans() }} · Brgy. {{ $r->barangay }}</span>
                    </div>
                    @if ($r->status === 'verified')<span class="chip green"><i class="i" data-i="checkmark_seal_fill"></i> Verified</span>@endif
                </div>
                @if ($r->note)<p class="note">{{ $r->note }}</p>@endif
                <div class="tags">
                    <span class="chip"><i class="{{ $f }}" data-i="{{ $ic }}"></i> {{ ucfirst($r->incident_type) }}</span>
                    <span class="chip {{ ['normal' => 'green', 'moderate' => 'amber', 'high' => 'red'][$risk] }}"><span class="dot {{ $risk }}"></span> {{ ucfirst($risk) }} risk</span>
                    @if ($r->depth_cm !== null)<span class="chip">{{ $r->depth_cm }} cm, {{ AgosData::depthLabel($r->depth_cm) }}</span>@endif
                </div>
                @if ($r->photo_url)
                    <div class="photo">
                        <img src="{{ $r->photo_url }}" alt="Flood photo in Brgy. {{ $r->barangay }}" loading="lazy">
                        <span class="age">Photo {{ $r->photo_taken_at->shortRelativeDiffForHumans() }}</span>
                    </div>
                @endif
                <div class="bottom">
                    @if ($me && $me->user_id === $r->user_id)
                        <form class="inline" method="POST" action="{{ route('reports.destroy', $r) }}" onsubmit="return confirm('Delete your report?')">
                            @csrf @method('DELETE')
                            <button class="vote" type="submit"><i class="i" data-i="trash"></i> Delete</button>
                        </form>
                        <span class="small muted">{{ $r->up_votes }} up · {{ $r->down_votes }} down</span>
                    @else
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
                    @endif
                    <a class="vote" style="margin-left:auto" href="{{ route('map') }}#report-{{ $r->report_id }}"><i class="i" data-i="map_fill"></i> Map</a>
                </div>
            </article>
        @endforeach
    </div>
@endsection
