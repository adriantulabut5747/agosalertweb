@extends('layouts.app')
@section('title', 'Notifications')

{{-- The app has no Notifications page, only the bell's sheet: this address
     (old links, the avatar menu without JavaScript) opens that sheet. --}}
@section('content')
    <div class="feed">
        <h1>Notifications</h1>
        <p class="sub">Posted by AgosAlert admins (unofficial demo). Official warnings come from PAGASA and the city.</p>
        @if ($failed)
            <div class="card muted">Couldn't load the alerts. Refresh in a minute.</div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>document.getElementById('notif').showModal();</script>
@endpush
