@extends('layouts.app')
@section('title', 'Settings')
@section('main_class', 'set')

{{-- The app's Settings > Account (settings_screen.dart): iOS rows in one
     card, each opening a sheet (here a <dialog>, the app's centered popup
     on wide screens). The app's appearance / notification toggles are
     previews that reset on reload, so this site leaves them out. --}}
@section('content')
    <a class="back" href="{{ route('home') }}"><i class="i" data-i="chevron_left"></i> Settings</a>

    <div class="fieldlabel">Account</div>
    <div class="card rows">
        <button type="button" class="setrow" onclick="document.getElementById('name').showModal()">
            <span class="sq"><i class="i" data-i="person_fill"></i></span><b>Name</b>
            <span class="val">{{ $me->full_name }}</span><i class="i chev" data-i="chevron_right"></i>
        </button>
        <div class="setrow">
            <span class="sq"><i class="i" data-i="mail_solid"></i></span><b>Email</b>
            <span class="val">{{ $me->email }}</span>
        </div>
        <button type="button" class="setrow" onclick="document.getElementById('password').showModal()">
            <span class="sq"><i class="i" data-i="lock_fill"></i></span><b>Password</b>
            <span class="val">Change</span><i class="i chev" data-i="chevron_right"></i>
        </button>
        <button type="button" class="setrow" onclick="document.getElementById('trash').showModal()">
            <span class="sq"><i class="i" data-i="trash_fill"></i></span><b>Trash</b>
            <span class="val">{{ $trash->count() ?: '' }}</span><i class="i chev" data-i="chevron_right"></i>
        </button>
    </div>

    <div class="card rows" style="margin-top:24px">
        <button type="button" class="setrow danger" onclick="document.getElementById('delete').showModal()">
            <span class="sq"><i class="i" data-i="trash_fill"></i></span><b>Delete account</b>
        </button>
    </div>

    {{-- ------------------------------------------------------- name --}}
    <dialog class="sheet" id="name">
        <form method="POST" action="{{ route('settings.name') }}">
            @csrf
            <h3>Your name</h3>
            <p>Everyone sees it on your flood reports.</p>
            <input type="text" name="full_name" value="{{ old('full_name', $me->full_name) }}" maxlength="100" placeholder="Full name" required>
            @error('full_name')<p class="err">{{ $message }}</p>@enderror
            <button class="btn white wide" type="submit">Save</button>
            <button class="btn gray wide" type="button" onclick="this.closest('dialog').close()">Cancel</button>
        </form>
    </dialog>

    {{-- --------------------------------------------------- password --}}
    <dialog class="sheet" id="password">
        <form method="POST" action="{{ route('settings.password') }}">
            @csrf
            <h3>Change password</h3>
            <p>At least 6 characters.</p>
            <div class="stackfields">
                <input type="password" name="current" placeholder="Current password" autocomplete="current-password" required>
                <input type="password" name="password" placeholder="New password" autocomplete="new-password" minlength="6" required>
                <input type="password" name="password_confirmation" placeholder="New password again" autocomplete="new-password" required>
            </div>
            @if ($errors->hasAny(['current', 'password']))<p class="err">{{ $errors->first('current') ?: $errors->first('password') }}</p>@endif
            <button class="btn white wide" type="submit">Save</button>
            <button class="btn gray wide" type="button" onclick="this.closest('dialog').close()">Cancel</button>
        </form>
    </dialog>

    {{-- ------------------------------------------------------ trash --}}
    <dialog class="sheet wide" id="trash">
        <h3>Trash</h3>
        <p>Reports you deleted, and ones past their 14 days. Each is erased with its photo {{ \App\Models\FloodReport::TRASH_DAYS }} days after it got here.</p>
        @forelse ($trash as $r)
            <div class="card trashitem">
                <b>{{ ucfirst($r->incident_type) }} · Brgy. {{ $r->barangay }}</b>
                <div class="small muted">
                    {{ $r->expired() && ! $r->deleted_at ? 'Expired' : 'Deleted' }} {{ $r->trashedAt()->format('M j') }} · erased {{ $r->erasesAt()->format('M j') }}{{ $r->canRestore($me) || $r->expired() ? '' : ' · removed by an admin' }}
                </div>
                <div class="row" style="margin-top:10px;gap:8px">
                    @if ($r->canRestore($me))
                        <form class="inline" method="POST" action="{{ route('reports.restore', $r) }}">
                            @csrf <input type="hidden" name="open" value="trash">
                            <button class="btn small tint" type="submit"><i class="i" data-i="arrow_uturn_left"></i> Restore</button>
                        </form>
                    @endif
                    <form class="inline" method="POST" action="{{ route('reports.forever', $r) }}">
                        @csrf @method('DELETE') <input type="hidden" name="open" value="trash">
                        <button class="btn small tint" type="submit"><i class="i" data-i="trash_fill"></i> Delete now</button>
                    </form>
                </div>
            </div>
        @empty
            <p style="color:var(--muted);font-size:15px;margin:8px 0 16px">Nothing here.</p>
        @endforelse
        <button class="btn gray wide" type="button" onclick="this.closest('dialog').close()">Done</button>
    </dialog>

    {{-- ----------------------------------------------- delete account --}}
    <dialog class="sheet" id="delete">
        <form method="POST" action="{{ route('settings.delete') }}">
            @csrf
            <span class="badge-lg red"><i class="i" data-i="trash_fill"></i></span>
            <h3>Delete your account?</h3>
            <p>You're logged out everywhere and your reports are hidden. In {{ \App\Models\AppUser::DELETE_DAYS }} days your account, reports, photos and votes are deleted for good. Log in before then to keep it.</p>
            <button class="btn red wide" type="submit">Delete my account</button>
            <button class="btn gray wide" type="button" onclick="this.closest('dialog').close()">Cancel</button>
        </form>
    </dialog>

    {{-- Reopen the sheet a form came from (a mistake, or a trash action). --}}
    @php
        $open = session('open') ?? ($errors->has('full_name') ? 'name' : ($errors->hasAny(['current', 'password']) ? 'password' : null));
    @endphp
    @if ($open)
        <script>document.getElementById(@json($open)).showModal();</script>
    @endif
@endsection
