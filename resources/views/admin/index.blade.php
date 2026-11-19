@extends('layouts.app')
@section('title', 'Admin')

@section('content')
    <h1>Admin</h1>
    <p class="sub">Same database as the app: changes here show in the Flutter app too.</p>

    <nav class="tabs">
        @foreach (['rescue' => 'Rescue requests', 'reports' => 'Flood reports', 'alerts' => 'Alerts', 'centers' => 'Centers', 'users' => 'Users'] as $key => $label)
            <a href="{{ route('admin.index', ['tab' => $key]) }}" class="{{ $tab === $key ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if ($errors->any())
        <ul class="errors" style="margin-bottom:12px">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif

    {{-- ------------------------------------------------ rescue requests --}}
    @if ($tab === 'rescue')
        <p class="small muted">Nobody is on duty yet: the request form tells people so. Guests: call to confirm (Verifying), then Help on the way, then Resolved.</p>
        @forelse ($requests as $r)
            <div class="card" style="margin-bottom:12px">
                <div class="row">
                    <b class="spacer">#{{ $r->request_id }} · Brgy. {{ $r->barangay }} · {{ $r->people_count }} {{ $r->people_count === 1 ? 'person' : 'people' }}</b>
                    <span class="tag {{ ['received' => 'red', 'verifying' => 'amber', 'help_on_the_way' => 'amber', 'resolved' => 'green'][$r->status] ?? '' }}">{{ $r->statusLabel() }}</span>
                </div>
                <div class="small muted">{{ $r->contact_name }} · {{ $r->contact_phone }}{{ $r->user_id ? '' : ' · guest' }} · {{ $r->created_at->diffForHumans() }}</div>
                @if (in_array($r->contact_phone, $flagged))
                    <div style="margin-top:6px"><span class="tag red">Flagged number: possible prank</span></div>
                @endif
                @if ($r->needs())<div class="small" style="margin-top:6px">Needs: {{ implode(', ', $r->needs()) }}</div>@endif
                @if ($r->note)<div class="small" style="margin-top:4px">"{{ $r->note }}"</div>@endif
                <div class="row" style="margin-top:12px">
                    <a class="btn small gray" href="tel:{{ $r->contact_phone }}">Call</a>
                    <form class="inline row" method="POST" action="{{ route('admin.rescue.status', $r) }}">
                        @csrf
                        <select name="status" style="width:auto;padding:7px 10px">
                            @foreach ($statuses as $k => $label)
                                @if ($k !== 'flagged_false')<option value="{{ $k }}" @selected($r->status === $k)>{{ $label }}</option>@endif
                            @endforeach
                        </select>
                        <button class="btn small gray" type="submit">Set status</button>
                    </form>
                    @if ($r->status !== 'flagged_false')
                        <form class="inline" method="POST" action="{{ route('admin.rescue.flag', $r) }}" onsubmit="return confirm('Only when you confirmed it is false. Flag {{ $r->contact_phone }}?')">
                            @csrf
                            <button class="btn small red" type="submit">Flag false</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="card muted">No rescue requests yet.</div>
        @endforelse
    @endif

    {{-- -------------------------------------------------- flood reports --}}
    @if ($tab === 'reports')
        <div class="scroll-x card">
            <table class="list">
                <tr><th>Report</th><th>Poster</th><th>Status</th><th></th></tr>
                @forelse ($reports as $r)
                    <tr>
                        <td>
                            <b>{{ ucfirst($r->incident_type) }}</b> · Brgy. {{ $r->barangay }}
                            @if ($r->depth_cm !== null) · {{ $r->depth_cm }} cm @endif
                            <div class="small muted">{{ $r->created_at->diffForHumans() }}@if ($r->note) · "{{ $r->note }}"@endif</div>
                            @if ($r->photo_url)<a class="small" href="{{ $r->photo_url }}" target="_blank" rel="noopener">Photo</a>@endif
                        </td>
                        <td>{{ $r->user?->full_name ?? 'Deleted account' }}</td>
                        <td><span class="tag {{ ['verified' => 'green', 'removed' => '', 'unverified' => 'amber'][$r->status] }}">{{ $r->status }}</span></td>
                        <td>
                            <div class="row">
                                @foreach (['verified' => 'Verify', 'removed' => 'Hide', 'unverified' => 'Reset'] as $s => $label)
                                    @if ($r->status !== $s)
                                        <form class="inline" method="POST" action="{{ route('admin.reports.status', $r) }}">
                                            @csrf <input type="hidden" name="status" value="{{ $s }}">
                                            <button class="btn small gray" type="submit">{{ $label }}</button>
                                        </form>
                                    @endif
                                @endforeach
                                <form class="inline" method="POST" action="{{ route('admin.reports.delete', $r) }}" onsubmit="return confirm('Delete this report and its photo for good?')">
                                    @csrf @method('DELETE')
                                    <button class="btn small red" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted">No reports right now.</td></tr>
                @endforelse
            </table>
        </div>
    @endif

    {{-- --------------------------------------------------------- alerts --}}
    @if ($tab === 'alerts')
        <div class="grid two">
            <form class="card" method="POST" action="{{ route('admin.alerts.store') }}">
                @csrf
                <h3>Post an alert</h3>
                <p class="small muted">Everyone sees it under the bell in the app, signed "Admin (unofficial demo)".</p>
                <label>Title</label><input type="text" name="title" value="{{ old('title') }}" maxlength="120" required placeholder="e.g. Flooding in Brgy. Dau">
                <label>One-line summary</label><input type="text" name="summary" value="{{ old('summary') }}" maxlength="255" required>
                <label>Full details</label><textarea name="details" required>{{ old('details') }}</textarea>
                <label>Area</label><input type="text" name="area" value="{{ old('area', 'Mabalacat City') }}" required>
                <label>What to do (one step per line, optional)</label><textarea name="actions">{{ old('actions') }}</textarea>
                <div class="row">
                    <div class="spacer"><label>Category</label>
                        <select name="category">@foreach (['flood', 'weather', 'road', 'shelter'] as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach</select></div>
                    <div class="spacer"><label>Severity</label>
                        <select name="severity">@foreach (['normal', 'moderate', 'high'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
                </div>
                <button class="btn blue wide" type="submit" style="margin-top:16px">Post</button>
            </form>
            <div>
                @forelse ($alerts as $a)
                    <div class="card" style="margin-bottom:12px">
                        <div class="row"><b class="spacer">{{ $a->title }}</b><span class="tag {{ ['high' => 'red', 'moderate' => 'amber', 'normal' => 'green'][$a->severity] }}">{{ $a->severity }}</span></div>
                        <div class="small muted">{{ $a->category }} · {{ $a->area }} · {{ $a->created_at->diffForHumans() }}</div>
                        <p class="small" style="margin:6px 0">{{ $a->summary }}</p>
                        <form class="inline" method="POST" action="{{ route('admin.alerts.delete', $a) }}" onsubmit="return confirm('Delete this alert?')">
                            @csrf @method('DELETE')
                            <button class="btn small red" type="submit">Delete</button>
                        </form>
                    </div>
                @empty
                    <div class="card muted">No alerts posted.</div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- -------------------------------------------------------- centers --}}
    @if ($tab === 'centers')
        <div class="grid two">
            <div>
                @foreach ($centers as $c)
                    <div class="card" style="margin-bottom:12px">
                        <div class="row"><b class="spacer">{{ $c->name }}</b>@if ($c->is_sample)<span class="tag amber">Sample</span>@endif</div>
                        <div class="small muted">Brgy. {{ $c->barangay }}</div>
                        <div class="row" style="margin-top:10px">
                            @foreach (['open', 'standby', 'closed'] as $s)
                                <form class="inline" method="POST" action="{{ route('admin.centers.status', $c) }}">
                                    @csrf <input type="hidden" name="status" value="{{ $s }}">
                                    <button class="btn small {{ $c->status === $s ? 'blue' : 'gray' }}" type="submit">{{ ucfirst($s) }}</button>
                                </form>
                            @endforeach
                            <span class="spacer"></span>
                            <form class="inline" method="POST" action="{{ route('admin.centers.delete', $c) }}" onsubmit="return confirm(@js('Delete '.$c->name.'?'))">
                                @csrf @method('DELETE')
                                <button class="btn small red" type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            <form class="card" method="POST" action="{{ route('admin.centers.store') }}" style="align-self:start">
                @csrf
                <h3>Add a center</h3>
                <label>Name</label><input type="text" name="name" value="{{ old('name') }}" required>
                <label>Barangay</label>
                <select name="barangay" required>@foreach ($barangays as $b)<option @selected(old('barangay') === $b)>{{ $b }}</option>@endforeach</select>
                <div class="row">
                    <div class="spacer"><label>Latitude</label><input type="text" name="latitude" value="{{ old('latitude') }}" placeholder="15.2236" required></div>
                    <div class="spacer"><label>Longitude</label><input type="text" name="longitude" value="{{ old('longitude') }}" placeholder="120.5714" required></div>
                </div>
                <p class="small muted" style="margin:6px 0 0">In Google Maps, right-click the building and copy the two numbers.</p>
                <label>Status</label>
                <select name="status">@foreach (['open', 'standby', 'closed'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select>
                <button class="btn blue wide" type="submit" style="margin-top:16px">Add</button>
            </form>
        </div>
    @endif

    {{-- ---------------------------------------------------------- users --}}
    @if ($tab === 'users')
        <p class="small muted">Making someone an admin is done in Supabase's Table Editor (users &gt; role), on purpose.</p>
        <div class="scroll-x card">
            <table class="list">
                <tr><th>Name</th><th>Email</th><th>Barangay</th><th>Role</th><th>Status</th><th></th></tr>
                @foreach ($users as $u)
                    <tr>
                        <td><b>{{ $u->full_name }}</b></td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->barangay }}</td>
                        <td>@if ($u->role === 'admin')<span class="tag blue">Admin</span>@else registered @endif</td>
                        <td><span class="tag {{ $u->account_status === 'active' ? 'green' : 'red' }}">{{ $u->account_status }}</span></td>
                        <td>
                            @if ($u->user_id !== $me->user_id)
                                <form class="inline" method="POST" action="{{ route('admin.users.status', $u) }}"
                                      @if ($u->account_status === 'active') onsubmit="return confirm(@js('Suspend '.$u->full_name.'?'))" @endif>
                                    @csrf
                                    <button class="btn small {{ $u->account_status === 'active' ? 'red' : 'gray' }}" type="submit">{{ $u->account_status === 'active' ? 'Suspend' : 'Reactivate' }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
@endsection
