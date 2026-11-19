@extends('layouts.app')
@section('title', 'Alerts')

@section('content')
    <div class="feed">
        <h1>Alerts</h1>
        <p class="sub">Posted by AgosAlert admins (unofficial demo). Official warnings come from PAGASA and the city.</p>

        @if ($failed)
            <div class="card muted">Couldn't load the alerts. Refresh in a minute.</div>
        @elseif ($alerts->isEmpty())
            <div class="card row">
                <img src="{{ asset('img/tugak/think.webp') }}" alt="" width="80">
                <span class="muted">No alerts right now. Quiet is good.</span>
            </div>
        @endif

        @foreach ($alerts as $a)
            <article class="card">
                <div class="row">
                    <span class="tag {{ ['high' => 'red', 'moderate' => 'amber', 'normal' => 'green'][$a->severity] }}">{{ $a->severity }}</span>
                    <span class="tag blue">{{ $a->category }}</span>
                    <span class="spacer"></span>
                    <span class="small muted">{{ $a->created_at->diffForHumans() }}</span>
                </div>
                <h3 style="margin-top:10px">{{ $a->title }}</h3>
                <div class="small muted">{{ $a->area }} · Admin (unofficial demo)</div>
                <p style="margin:10px 0 0"><b>{{ $a->summary }}</b></p>
                <p class="muted" style="margin:6px 0 0">{{ $a->details }}</p>
                @if ($a->steps())
                    <label>What to do</label>
                    <ol style="margin:0;padding-left:20px">@foreach ($a->steps() as $s)<li>{{ $s }}</li>@endforeach</ol>
                @endif
            </article>
        @endforeach
    </div>
@endsection
